<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$notifId = $payload['id'] ?? null;
$clearAllRead = $payload['all_read'] ?? false;

try {
    $db = get_db_connection();
    
    if ($clearAllRead) {
        $stmt = $db->prepare('DELETE FROM notifications WHERE user_id = :uid AND is_read = 1');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
    } else if ($notifId) {
        $stmt = $db->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => $notifId, ':uid' => $_SESSION['user_id']]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
