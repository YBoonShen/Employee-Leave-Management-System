<?php
// Two-step password reset (no email needed — local system only).
// step=verify: checks if the email exists.
// step=reset:  updates the password if email is valid.
session_start();
require __DIR__ . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$step  = $input['step'] ?? '';
$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

try {
    $db = get_db_connection();

    // ── Step 1: Check if email exists ──
    if ($step === 'verify') {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        echo json_encode(['found' => (bool)$user]);
        exit;
    }

    // ── Step 2: Update the password ──
    if ($step === 'reset') {
        $password = $input['password'] ?? '';

        if (strlen($password) < 6) {
            echo json_encode(['error' => 'Password must be at least 6 characters.']);
            exit;
        }

        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['error' => 'Account not found.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd  = $db->prepare('UPDATE users SET password_hash = :hash WHERE email = :email');
        $upd->execute([':hash' => $hash, ':email' => $email]);

        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown step']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again.']);
}
