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
$requestId = (int)($payload['id'] ?? 0);

try {
    $db = get_db_connection();
    
    // Safety: Only delete if it's yours AND it's still Pending
    $check = $db->prepare('SELECT status FROM leave_requests WHERE id = :id AND user_id = :uid');
    $check->execute([':id' => $requestId, ':uid' => $_SESSION['user_id']]);
    $request = $check->fetch();

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found or unauthorized']);
        exit;
    }

    if ($request['status'] !== 'Pending') {
        http_response_code(400);
        echo json_encode(['error' => 'You can only cancel pending requests']);
        exit;
    }

    $stmt = $db->prepare('DELETE FROM leave_requests WHERE id = :id');
    $stmt->execute([':id' => $requestId]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
