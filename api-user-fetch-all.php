<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Manager access required']);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->query("SELECT id, name, employee_id, role, email, department, job_title, created_at FROM users ORDER BY name ASC");
    $users = $stmt->fetchAll();

    echo json_encode(['users' => $users]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
