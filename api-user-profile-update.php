<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Read JSON input
$payload = json_decode(file_get_contents('php://input'), true);

$name = trim($payload['name'] ?? '');
$department = trim($payload['department'] ?? '');
$location = trim($payload['location'] ?? '');
$phone = trim($payload['phone'] ?? '');
$job_title = trim($payload['job_title'] ?? '');
$email = trim($payload['email'] ?? '');
$employee_id = trim($payload['employee_id'] ?? '');

try {
    $db = get_db_connection();
    
    // First, let's make sure the columns exist (dynamic migration for local dev)
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20), 
               ADD COLUMN IF NOT EXISTS job_title VARCHAR(100), 
               ADD COLUMN IF NOT EXISTS location VARCHAR(150)");

    $stmt = $db->prepare('UPDATE users SET name = :name, department = :dept, phone = :phone, job_title = :job, location = :loc, email = :email, employee_id = :eid WHERE id = :id');
    $stmt->execute([
        ':name' => $name !== '' ? $name : $_SESSION['name'],
        ':dept' => $department,
        ':phone' => $phone,
        ':job' => $job_title,
        ':loc' => $location,
        ':email' => $email !== '' ? $email : $_SESSION['email'],
        ':eid' => $employee_id !== '' ? $employee_id : $_SESSION['employee_id'],
        ':id'   => $_SESSION['user_id'],
    ]);

    // Update Session for real-time consistency
    if ($name !== '') $_SESSION['name'] = $name;
    if ($email !== '') $_SESSION['email'] = $email;
    if ($employee_id !== '') $_SESSION['employee_id'] = $employee_id;
    
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

