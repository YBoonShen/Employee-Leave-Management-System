<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

// Malaysia Employment Act 1955 tiers; Part-Time pro-rated at 50%
function calculateAllowanceByService(string $etype, string $join_date): int {
    $join   = new DateTime($join_date);
    $now    = new DateTime();
    $months = ($now->format('Y') - $join->format('Y')) * 12 + ($now->format('n') - $join->format('n'));
    if ($months >= 60)     $days = 16;
    elseif ($months >= 24) $days = 12;
    else                   $days = 8;
    return $etype === 'Part-Time' ? (int)ceil($days / 2) : $days;
}

if (empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Read JSON input
$payload = json_decode(file_get_contents('php://input'), true);

$name        = trim($payload['name']        ?? '');
$department  = trim($payload['department']  ?? '');
$location    = trim($payload['location']    ?? '');
$phone       = trim($payload['phone']       ?? '');
$job_title   = trim($payload['job_title']   ?? '');
$email       = trim($payload['email']       ?? '');
$employee_id = trim($payload['employee_id'] ?? '');

// Phone must start with +60 if provided
if ($phone !== '' && !preg_match('/^\+60[\d\s\-]{7,13}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number must start with +60']);
    exit;
}

// Validate employment type against allowed values
$allowed_types   = ['Permanent', 'Contract', 'Part-Time'];
$employment_type = in_array($payload['employment_type'] ?? '', $allowed_types)
    ? $payload['employment_type']
    : null;

// join_date may only be changed by managers or admins
$is_manager    = in_array($_SESSION['role'] ?? '', ['manager', 'admin']);
$join_date_raw = $is_manager ? trim($payload['join_date'] ?? '') : '';
$join_date     = null;
if ($join_date_raw !== '') {
    $parsed = DateTime::createFromFormat('Y-m-d', $join_date_raw);
    if ($parsed && $parsed->format('Y-m-d') === $join_date_raw && $parsed <= new DateTime()) {
        $join_date = $join_date_raw;
    }
}

try {
    $db = get_db_connection();

    // Ensure all optional columns exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone           VARCHAR(20),
               ADD COLUMN IF NOT EXISTS job_title       VARCHAR(100),
               ADD COLUMN IF NOT EXISTS location        VARCHAR(150)");

    // Add optional columns if missing (MySQL 5.7 compatible)
    $etCols = $db->query("SHOW COLUMNS FROM users LIKE 'employment_type'")->fetchAll();
    if (empty($etCols)) $db->exec("ALTER TABLE users ADD COLUMN employment_type ENUM('Permanent','Contract','Part-Time') DEFAULT 'Permanent'");
    $jdCols = $db->query("SHOW COLUMNS FROM users LIKE 'join_date'")->fetchAll();
    if (empty($jdCols)) $db->exec("ALTER TABLE users ADD COLUMN join_date DATE NULL");

    // Build SET clause dynamically — only update employment_type when provided
    $params = [
        ':name' => $name !== '' ? $name : $_SESSION['name'],
        ':dept' => $department,
        ':phone' => $phone,
        ':job'  => $job_title,
        ':loc'  => $location,
        ':email' => $email !== '' ? $email : $_SESSION['email'],
        ':eid'  => $employee_id !== '' ? $employee_id : $_SESSION['employee_id'],
        ':id'   => $_SESSION['user_id'],
    ];

    $extra = '';
    if ($employment_type !== null) {
        $extra .= ', employment_type = :etype';
        $params[':etype'] = $employment_type;
    }
    if ($join_date !== null) {
        $extra .= ', join_date = :jdate';
        $params[':jdate'] = $join_date;

        // Recalculate allowance based on new join date + effective employment type
        $effectiveEtype = $employment_type
            ?? $db->query("SELECT employment_type FROM users WHERE id = " . (int)$_SESSION['user_id'])->fetchColumn()
            ?: 'Permanent';
        $extra .= ', allowance = :recalc_allowance';
        $params[':recalc_allowance'] = calculateAllowanceByService($effectiveEtype, $join_date);
    }

    $stmt = $db->prepare("UPDATE users SET name = :name, department = :dept, phone = :phone, job_title = :job, location = :loc, email = :email, employee_id = :eid{$extra} WHERE id = :id");
    $stmt->execute($params);

    // Update session for real-time consistency
    if ($name !== '')        $_SESSION['name']        = $name;
    if ($email !== '')       $_SESSION['email']       = $email;
    if ($employee_id !== '') $_SESSION['employee_id'] = $employee_id;

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
