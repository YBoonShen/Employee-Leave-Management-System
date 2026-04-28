<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

// Only allow Managers to delete users
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Manager access required']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$targetUserId = (int)($payload['id'] ?? 0);

if ($targetUserId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid User ID']);
    exit;
}

// Prevent manager from deleting themselves!
if ($targetUserId === (int)$_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot delete your own account!']);
    exit;
}

try {
    $db = get_db_connection();
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $targetUserId]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
