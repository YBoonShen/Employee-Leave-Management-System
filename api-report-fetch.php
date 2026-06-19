<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

require __DIR__ . '/config.php';

$pdo = get_db_connection();

$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type']   ?? '';

// ── Summary counts ────────────────────────────────────────
$summary = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Pending')  AS pending,
        SUM(status = 'Rejected') AS rejected
    FROM leave_requests
")->fetch(PDO::FETCH_ASSOC);

// ── Leave by type ─────────────────────────────────────────
$byType = $pdo->query("
    SELECT type AS leave_type, COUNT(*) AS cnt, SUM(duration_days) AS days
    FROM leave_requests
    GROUP BY type
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Leave by department ───────────────────────────────────
$byDept = $pdo->query("
    SELECT u.department, COUNT(*) AS cnt, SUM(lr.duration_days) AS days
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE u.department IS NOT NULL AND u.department != ''
    GROUP BY u.department
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// ── All records (filtered) ────────────────────────────────
$where  = [];
$params = [];

if ($filterStatus) { $where[] = 'lr.status = ?';     $params[] = $filterStatus; }
if ($filterType)   { $where[] = 'lr.type = ?'; $params[] = $filterType;   }

$sql = "
    SELECT lr.id, u.name, u.employee_id, u.department,
           lr.type AS leave_type, lr.start_date, lr.end_date, lr.duration_days AS duration, lr.status, lr.created_at
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY lr.created_at DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'summary' => $summary,
    'byType'  => $byType,
    'byDept'  => $byDept,
    'records' => $records,
]);
