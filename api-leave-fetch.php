<?php
// Returns the current user's data and leave requests.
// Managers can pass ?user_id=X to fetch any employee's records.
session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = get_db_connection();

    // MIGRATION: Ensure columns exist (compatible with MySQL 5.7+)
    $cols = $db->query("SHOW COLUMNS FROM users LIKE 'allowance'")->fetchAll();
    if (empty($cols)) $db->exec("ALTER TABLE users ADD COLUMN allowance INT DEFAULT 21");
    $cols2 = $db->query("SHOW COLUMNS FROM leave_requests LIKE 'proof_files'")->fetchAll();
    if (empty($cols2)) $db->exec("ALTER TABLE leave_requests ADD COLUMN proof_files TEXT NULL");
>>>>>>>>> Temporary merge branch 2
    
    $currentId = $_SESSION['user_id'];
    $currentRole = $_SESSION['role'] ?? 'employee';

    // If a specific user_id is requested (for manager view)
    $targetId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentId;

    // Security: Only managers/admins can view other users' data
    if ($targetId !== $currentId && $currentRole !== 'manager' && $currentRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }

    // Get the target user info
    $userStmt = $db->prepare('SELECT id, name, employee_id, role, department, phone, job_title, location, email, allowance, employment_type, join_date FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $targetId]);
    $user = $userStmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Target user not found']);
        exit;
    }

    // Fetch requests for the target user (or all if requested without user_id by a manager)
    if (!isset($_GET['user_id']) && ($currentRole === 'manager' || $currentRole === 'admin')) {
        // Manager Dashboard view: all requests
        $stmt = $db->prepare('SELECT lr.*, u.name AS empName, u.employee_id AS empId
                              FROM leave_requests lr
                              JOIN users u ON lr.user_id = u.id
                              ORDER BY lr.created_at DESC');
        $stmt->execute();
    } else {
        // Specific user profile view or personal view
        $stmt = $db->prepare('SELECT lr.*, u.name AS empName, u.employee_id AS empId
                              FROM leave_requests lr
                              JOIN users u ON lr.user_id = u.id
                              WHERE lr.user_id = :uid
                              ORDER BY lr.created_at DESC');
        $stmt->execute([':uid' => $targetId]);
    }
    
    $rows = $stmt->fetchAll();

    $requests = array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'empName' => $r['empName'],
            'empId' => $r['empId'],
            'type' => $r['type'],
            'start' => $r['start_date'],
            'end' => $r['end_date'],
            'duration' => (int)$r['duration'],
            'reason' => $r['reason'],
            'status' => $r['status'],
            'comment' => $r['manager_comment'] ?? '',
            'date' => substr($r['created_at'], 0, 10),
            'proofFiles' => !empty($r['proof_files']) ? json_decode($r['proof_files'], true) : [],
        ];
    }, $rows);

    echo json_encode([
        'user' => $user,
        'requests' => $requests,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

