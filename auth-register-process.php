<?php
// Creates a new employee account. All new sign-ups get the 'employee' role.
session_start();
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: page-register.php');
    exit;
}

$name            = trim($_POST['name'] ?? '');
$employeeId      = trim($_POST['employee_id'] ?? '');
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$role            = 'employee';
$department      = trim($_POST['department'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$job_title       = trim($_POST['job_title'] ?? '');
$location        = trim($_POST['location'] ?? '');

// Validate employment type
$allowed_types   = ['Permanent', 'Contract', 'Part-Time'];
$employment_type = in_array($_POST['employment_type'] ?? '', $allowed_types)
    ? $_POST['employment_type']
    : 'Permanent';

// Validate join date — must be a real past/present date
$join_date_raw = trim($_POST['join_date'] ?? '');
$join_date     = null;
if ($join_date_raw !== '') {
    $parsed = DateTime::createFromFormat('Y-m-d', $join_date_raw);
    if ($parsed && $parsed->format('Y-m-d') === $join_date_raw && $parsed <= new DateTime()) {
        $join_date = $join_date_raw;
    }
}

// Calculate initial allowance based on Malaysia Employment Act 1955
// Tiers: <2 years = 8 days, 2-5 years = 12 days, >=5 years = 16 days
// Part-Time is pro-rated at 50%
function calculateAllowanceByService(string $employment_type, ?string $join_date): int {
    if (!$join_date) return 8;

    $years = (int)(new DateTime())->diff(new DateTime($join_date))->y;

    if ($years >= 5)     $base = 16;
    elseif ($years >= 2) $base = 12;
    else                 $base = 8;

    return $employment_type === 'Part-Time' ? (int)ceil($base / 2) : $base;
}

$allowance = calculateAllowanceByService($employment_type, $join_date);

if ($name === '' || $employeeId === '' || $email === '' || $password === '') {
    header('Location: page-register.php?error=1');
    exit;
}

try {
    $db = get_db_connection();

    // Add optional columns if missing (MySQL 5.7 compatible)
    $existing = array_column($db->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
    if (!in_array('phone',           $existing)) $db->exec("ALTER TABLE users ADD COLUMN phone           VARCHAR(20)");
    if (!in_array('job_title',       $existing)) $db->exec("ALTER TABLE users ADD COLUMN job_title       VARCHAR(100)");
    if (!in_array('location',        $existing)) $db->exec("ALTER TABLE users ADD COLUMN location        VARCHAR(150)");
    if (!in_array('allowance',       $existing)) $db->exec("ALTER TABLE users ADD COLUMN allowance       INT DEFAULT 8");
    if (!in_array('employment_type', $existing)) $db->exec("ALTER TABLE users ADD COLUMN employment_type ENUM('Permanent','Contract','Part-Time') DEFAULT 'Permanent'");
    if (!in_array('join_date',       $existing)) $db->exec("ALTER TABLE users ADD COLUMN join_date       DATE NULL");

    // Prevent duplicate email or employee ID
    $checkStmt = $db->prepare('SELECT id FROM users WHERE email = :email OR employee_id = :emp LIMIT 1');
    $checkStmt->execute([':email' => $email, ':emp' => $employeeId]);
    if ($checkStmt->fetch()) {
        header('Location: page-register.php?error=exists');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users
        (employee_id, name, email, password_hash, role, department, phone, job_title, location, employment_type, join_date, allowance)
        VALUES
        (:emp, :name, :email, :hash, :role, :dept, :phone, :job, :loc, :etype, :jdate, :allowance)');
    $stmt->execute([
        ':emp'       => $employeeId,
        ':name'      => $name,
        ':email'     => $email,
        ':hash'      => $hash,
        ':role'      => $role,
        ':dept'      => $department,
        ':phone'     => $phone,
        ':job'       => $job_title,
        ':loc'       => $location,
        ':etype'     => $employment_type,
        ':jdate'     => $join_date,
        ':allowance' => $allowance,
    ]);

    header('Location: page-login.php?just_signed_up=1');
} catch (Throwable $e) {
    header('Location: page-register.php?error=1');
}
