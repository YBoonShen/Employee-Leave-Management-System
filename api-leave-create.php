<?php
session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_POST)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $db = get_db_connection();
    $userId = $_SESSION['user_id'];

    $start    = $_POST['start']    ?? '';
    $end      = $_POST['end']      ?? '';
    $duration = (int)($_POST['duration'] ?? 1);
    $type     = $_POST['type']     ?? 'Annual';

    // Handle file uploads
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $savedFiles   = [];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    if (!empty($_FILES['proof_files']['name'][0])) {
        foreach ($_FILES['proof_files']['tmp_name'] as $i => $tmpFile) {
            if ($_FILES['proof_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $mime = mime_content_type($tmpFile);
            if (!in_array($mime, $allowedMimes)) continue;
            $ext      = strtolower(pathinfo($_FILES['proof_files']['name'][$i], PATHINFO_EXTENSION));
            $filename = uniqid('proof_', true) . '.' . $ext;
            move_uploaded_file($tmpFile, $uploadDir . $filename);
            $savedFiles[] = $filename;
        }
    }
    $proofFiles = !empty($savedFiles) ? json_encode($savedFiles) : null;

    // 1. Duplicate check (overlapping dates for same user)
    $dupStmt = $db->prepare('SELECT id FROM leave_requests WHERE user_id = :uid AND status != "Rejected" AND ((start_date <= :end AND end_date >= :start))');
    $dupStmt->execute([':uid' => $userId, ':start' => $start, ':end' => $end]);
    if ($dupStmt->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['error' => 'You already have a leave request during this period.']);
        exit;
    }

    // 2. Balance check
    $userStmt = $db->prepare('SELECT allowance FROM users WHERE id = :uid');
    $userStmt->execute([':uid' => $userId]);
    $allowance = (int)$userStmt->fetchColumn();

    $takenStmt = $db->prepare('SELECT SUM(duration_days) FROM leave_requests WHERE user_id = :uid AND status = "Approved"');
    $takenStmt->execute([':uid' => $userId]);
    $taken = (int)$takenStmt->fetchColumn();

    if (($taken + $duration) > $allowance) {
        http_response_code(400);
        echo json_encode(['error' => 'Insufficient leave balance. You only have ' . ($allowance - $taken) . ' days left.']);
        exit;
    }

    $stmt = $db->prepare('INSERT INTO leave_requests (user_id, type, start_date, end_date, duration_days, reason, proof_files)
                          VALUES (:uid, :type, :start, :end, :duration, :reason, :proof)');
    $stmt->execute([
        ':uid'    => $userId,
        ':type'   => $type,
        ':start'  => $start,
        ':end'    => $end,
        ':duration' => $duration,
        ':reason' => $_POST['reason'] ?? null,
        ':proof'  => $proofFiles,
    ]);

    $id = $db->lastInsertId();

    // Notify Manager
    $mgrId = $db->query("SELECT id FROM users WHERE role = 'manager' LIMIT 1")->fetchColumn();
    if ($mgrId) {
        $msg = "New {$type} leave request from {$_SESSION['name']}";
        $notif = $db->prepare('INSERT INTO notifications (user_id, message, type, request_id) VALUES (:uid, :msg, "request", :rid)');
        $notif->execute([':uid' => $mgrId, ':msg' => $msg, ':rid' => $id]);
    }

    echo json_encode([
        'id' => (int)$id,
        'status' => 'Pending',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

