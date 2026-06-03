<?php
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$file = basename($_GET['f'] ?? '');
if (!$file) {
    http_response_code(400);
    exit('Missing file');
}

$path = __DIR__ . '/uploads/' . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('File not found');
}

// Only managers and the owning employee can download
$db  = get_db_connection();
$role = $_SESSION['role'] ?? 'employee';
$uid  = $_SESSION['user_id'];

if ($role !== 'manager' && $role !== 'admin') {
    // Verify the file belongs to this employee
    $stmt = $db->prepare("SELECT id FROM leave_requests WHERE user_id = :uid AND proof_files LIKE :f");
    $stmt->execute([':uid' => $uid, ':f' => '%' . $file . '%']);
    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$mime = mime_content_type($path);
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache');
readfile($path);
