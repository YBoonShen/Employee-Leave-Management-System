<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $db = get_db_connection();

    // 1. Total users
    $uTotal = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 2. Pending requests
    $pTotal = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();

    // 3. Approved this month
    $month = date('Y-m');
    $aMonth = $db->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'Approved' AND created_at LIKE :m");
    $aMonth->execute([':m' => "$month%"]);
    $aTotal = $aMonth->fetchColumn();

    // 4. Team on Leave TODAY
    $today = date('Y-m-d');
    $onLeave = $db->prepare("SELECT COUNT(DISTINCT user_id) FROM leave_requests WHERE status = 'Approved' AND :t BETWEEN start_date AND end_date");
    $onLeave->execute([':t' => $today]);
    $onLeaveTotal = $onLeave->fetchColumn();

    // 5. Leave distribution
    $dist = $db->query("SELECT type, COUNT(*) as count FROM leave_requests GROUP BY type")->fetchAll();

    echo json_encode([
        'totalUsers' => (int)$uTotal,
        'pendingCount' => (int)$pTotal,
        'approvedMonth' => (int)$aTotal,
        'onLeaveToday' => (int)$onLeaveTotal,
        'distribution' => $dist
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
