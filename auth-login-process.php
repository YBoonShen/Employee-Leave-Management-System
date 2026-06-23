<?php
// Validates login credentials and starts the session.
// Also seeds the manager account on first run if it doesn't exist yet.
session_start();
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: page-login.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

try {
    $db = get_db_connection();

    // Ensure columns exist
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20), 
               ADD COLUMN IF NOT EXISTS job_title VARCHAR(100), 
               ADD COLUMN IF NOT EXISTS location VARCHAR(150)");

    // SEED: Create the manager account on first run only
    $mCheck = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $mCheck->execute([':email' => 'boonshen1159@gmail.com']);
    if (!$mCheck->fetch()) {
        $mHash = password_hash('Nexus@2024', PASSWORD_DEFAULT);
        $db->prepare('INSERT INTO users (employee_id, name, email, password_hash, role, department, job_title)
                      VALUES ("MGR001", "Boon Shen", "boonshen1159@gmail.com", :h, "manager", "Management", "General Manager")')
           ->execute([':h' => $mHash]);
    } else {
        // Only enforce manager role — never overwrite the name (allows profile edits to persist)
        $db->prepare('UPDATE users SET role = "manager" WHERE email = "boonshen1159@gmail.com"')->execute();
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        header('Location: page-login.php?error=1');
        exit;
    }

    // Regenerate session ID after login to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    header('Location: page-main.php');
} catch (Throwable $e) {
    header('Location: page-login.php?error=1');
}

