<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$current = $payload['current'] ?? '';
$new = $payload['new'] ?? '';

if (strlen($new) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'New password too short']);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password incorrect']);
        exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $upd->execute([':hash' => $newHash, ':id' => $_SESSION['user_id']]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
