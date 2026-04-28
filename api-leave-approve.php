<?php
session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Manager role required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['id'], $payload['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

try {
    $db = get_db_connection();

    // Get the user ID of the request owner to notify them and check for self-approval
    $ownerStmt = $db->prepare('SELECT user_id, type FROM leave_requests WHERE id = :id');
    $ownerStmt->execute([':id' => (int)$payload['id']]);
    $owner = $ownerStmt->fetch();

    if (!$owner) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit;
    }

    // Prevent self-approval
    if ($owner['user_id'] == $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'You cannot approve or reject your own leave requests.']);
        exit;
    }

    // Ensure manager_comment column exists
    $db->exec("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS manager_comment TEXT");

    $stmt = $db->prepare('UPDATE leave_requests SET status = :status, manager_comment = :comment, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':status' => $payload['status'],
        ':comment' => $payload['comment'] ?? null,
        ':id' => (int)$payload['id'],
    ]);

    $managerName = $_SESSION['name'] ?? 'Manager';
    $msg = "Your {$owner['type']} leave request has been {$payload['status']} by {$managerName}.";
    $type = strtolower($payload['status']); // 'approved' or 'rejected'
    $notif = $db->prepare('INSERT INTO notifications (user_id, message, type, request_id) VALUES (:uid, :msg, :type, :rid)');
    $notif->execute([':uid' => $owner['user_id'], ':msg' => $msg, ':type' => $type, ':rid' => (int)$payload['id']]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

