<?php
// Manager-only: set an employee's join date and recalculate their allowance.
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

// Malaysia Employment Act 1955 tiers; Part-Time pro-rated at 50%
function calculateAllowanceByService(string $etype, string $join_date): int {
    $join   = new DateTime($join_date);
    $now    = new DateTime();
    $months = ($now->format('Y') - $join->format('Y')) * 12 + ($now->format('n') - $join->format('n'));
    if ($months >= 60)     $days = 16;
    elseif ($months >= 24) $days = 12;
    else                   $days = 8;
    return $etype === 'Part-Time' ? (int)ceil($days / 2) : $days;
}

$payload  = json_decode(file_get_contents('php://input'), true);
$userId   = (int)($payload['user_id'] ?? 0);
$joinDate = trim($payload['join_date'] ?? '');

$parsed = DateTime::createFromFormat('Y-m-d', $joinDate);
if ($userId <= 0 || !$parsed || $parsed->format('Y-m-d') !== $joinDate || $parsed > new DateTime()) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user_id or join_date (must be Y-m-d, not in the future)']);
    exit;
}

try {
    $db = get_db_connection();

    $etypeStmt = $db->prepare('SELECT employment_type FROM users WHERE id = :id');
    $etypeStmt->execute([':id' => $userId]);
    $etype = $etypeStmt->fetchColumn();
    if ($etype === false) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    $etype = $etype ?: 'Permanent';

    $allowance = calculateAllowanceByService($etype, $joinDate);

    $stmt = $db->prepare('UPDATE users SET join_date = :jdate, allowance = :allowance WHERE id = :id');
    $stmt->execute([':jdate' => $joinDate, ':allowance' => $allowance, ':id' => $userId]);

    echo json_encode(['ok' => true, 'allowance' => $allowance]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
