<?php
// Manager-only: manually override an employee's annual leave allowance.
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Manager access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$payload   = json_decode(file_get_contents('php://input'), true);
$userId    = (int)($payload['user_id']   ?? 0);
$allowance = (int)($payload['allowance'] ?? -1);

if ($userId <= 0 || $allowance < 0 || $allowance > 365) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id or allowance value (0–365)']);
    exit;
}

try {
    $db = get_db_connection();

    // Reject if new allowance is less than days already approved
    $takenStmt = $db->prepare(
        "SELECT COALESCE(SUM(duration), 0) FROM leave_requests WHERE user_id = :uid AND status = 'Approved'"
    );
    $takenStmt->execute([':uid' => $userId]);
    $taken = (int)$takenStmt->fetchColumn();

    if ($allowance < $taken) {
        http_response_code(400);
        echo json_encode(['error' => "Cannot set allowance below days already taken ({$taken} days approved)"]);
        exit;
    }

    $stmt = $db->prepare('UPDATE users SET allowance = :a WHERE id = :id');
    $stmt->execute([':a' => $allowance, ':id' => $userId]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
