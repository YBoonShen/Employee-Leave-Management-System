<?php
session_start();
require __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$requestId = (int)($_POST['id'] ?? 0);

try {
    $db = get_db_connection();

    $userId   = $_SESSION['user_id'];
    $duration = (int)($_POST['duration'] ?? 0);
    $start    = $_POST['start'] ?? '';
    $end      = $_POST['end']   ?? '';

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

    // Handle replacement file uploads
    $uploadDir  = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $proofFiles = null;
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

    if (!empty($_FILES['proof_files']['name'][0])) {
        // Delete old files for this request
        $old = $db->prepare('SELECT proof_files FROM leave_requests WHERE id = :id');
        $old->execute([':id' => $requestId]);
        $oldProof = $old->fetchColumn();
        if ($oldProof) {
            foreach (json_decode($oldProof, true) as $f) {
                $path = $uploadDir . basename($f);
                if (file_exists($path)) unlink($path);
            }
        }
        $savedFiles = [];
        foreach ($_FILES['proof_files']['tmp_name'] as $i => $tmpFile) {
            if ($_FILES['proof_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $mime = mime_content_type($tmpFile);
            if (!in_array($mime, $allowedMimes)) continue;
            $ext      = strtolower(pathinfo($_FILES['proof_files']['name'][$i], PATHINFO_EXTENSION));
            $filename = uniqid('proof_', true) . '.' . $ext;
            move_uploaded_file($tmpFile, $uploadDir . $filename);
            $savedFiles[] = $filename;
        }
        $proofFiles = !empty($savedFiles) ? json_encode($savedFiles) : null;

        $stmt = $db->prepare('UPDATE leave_requests SET type = :type, start_date = :start, end_date = :end, duration_days = :duration, reason = :reason, proof_files = :proof, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':type'     => $_POST['type'] ?? 'Annual',
            ':start'    => $start,
            ':end'      => $end,
            ':duration' => $duration,
            ':reason'   => $_POST['reason'] ?? null,
            ':proof'    => $proofFiles,
            ':id'       => $requestId,
        ]);
    } else {
        $stmt = $db->prepare('UPDATE leave_requests SET type = :type, start_date = :start, end_date = :end, duration_days = :duration, reason = :reason, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':type'     => $_POST['type'] ?? 'Annual',
            ':start'    => $start,
            ':end'      => $end,
            ':duration' => $duration,
            ':reason'   => $_POST['reason'] ?? null,
            ':id'       => $requestId,
        ]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
