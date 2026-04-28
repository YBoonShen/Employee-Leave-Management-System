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
    
    $userId = $_SESSION['user_id'];
    $duration = (int)$payload['duration'];
    $start = $payload['start'];
    $end = $payload['end'];

    // 1. Check if it belongs to user and is still Pending
    $check = $db->prepare('SELECT status, duration_days FROM leave_requests WHERE id = :id AND user_id = :uid');
    $check->execute([':id' => $requestId, ':uid' => $userId]);
    $req = $check->fetch();

    if (!$req || $req['status'] !== 'Pending') {
        http_response_code(403);
        echo json_encode(['error' => 'You can only edit pending requests.']);
        exit;
    }

    // 2. Overlap check (excluding the current request)
    $dupStmt = $db->prepare('SELECT id FROM leave_requests WHERE user_id = :uid AND id != :id AND status != "Rejected" AND ((start_date <= :end AND end_date >= :start))');
    $dupStmt->execute([':uid' => $userId, ':id' => $requestId, ':start' => $start, ':end' => $end]);
    if ($dupStmt->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['error' => 'Overlapping leave period with another request.']);
        exit;
    }

    // 3. Balance check
    $userStmt = $db->prepare('SELECT allowance FROM users WHERE id = :uid');
    $userStmt->execute([':uid' => $userId]);
    $allowance = (int)$userStmt->fetchColumn();

    $takenStmt = $db->prepare('SELECT SUM(duration_days) FROM leave_requests WHERE user_id = :uid AND status = "Approved"');
    $takenStmt->execute([':uid' => $userId]);
    $taken = (int)$takenStmt->fetchColumn();

    if (($taken + $duration) > $allowance) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient leave balance.']);
        exit;
    }

    $stmt = $db->prepare('UPDATE leave_requests SET type = :type, start_date = :start, end_date = :end, duration_days = :duration, reason = :reason, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':type' => $payload['type'],
        ':start' => $start,
        ':end' => $end,
        ':duration' => $duration,
        ':reason' => $payload['reason'],
        ':id' => $requestId
    ]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
