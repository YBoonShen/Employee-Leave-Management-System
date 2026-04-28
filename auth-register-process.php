<?php
session_start();
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: page-register.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$employeeId = trim($_POST['employee_id'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = 'employee'; // Default role for all signups
$department = trim($_POST['department'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$job_title = trim($_POST['job_title'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($name === '' || $employeeId === '' || $email === '' || $password === '') {
    header('Location: page-register.php?error=1');
    exit;
}

try {
    $db = get_db_connection();

    // Ensure columns exist (migration for local dev)
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20), 
               ADD COLUMN IF NOT EXISTS job_title VARCHAR(100), 
               ADD COLUMN IF NOT EXISTS location VARCHAR(150)");
    
    // Check if email or employee_id already exists
    $checkStmt = $db->prepare('SELECT id FROM users WHERE email = :email OR employee_id = :emp LIMIT 1');
    $checkStmt->execute([':email' => $email, ':emp' => $employeeId]);
    if ($checkStmt->fetch()) {
        header('Location: page-register.php?error=exists');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (employee_id, name, email, password_hash, role, department, phone, job_title, location) 
                          VALUES (:emp, :name, :email, :hash, :role, :dept, :phone, :job, :loc)');
    $stmt->execute([
        ':emp' => $employeeId,
        ':name' => $name,
        ':email' => $email,
        ':hash' => $hash,
        ':role' => $role,
        ':dept' => $department,
        ':phone' => $phone,
        ':job' => $job_title,
        ':loc' => $location,
    ]);
    // registration success -> back to login page
    header('Location: page-login.php?just_signed_up=1');
} catch (Throwable $e) {
    header('Location: page-register.php?error=1');
}

