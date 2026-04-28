<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: page-main.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusLeave - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <div class="auth-card">
            <div class="logo auth-logo">
                <i class="fas fa-shield-alt"></i>
                <span>NexusLeave</span>
            </div>
            <h1>Welcome back</h1>
            <p class="text-muted">Sign in to manage your leave requests.</p>
            <?php if (!empty($_GET['just_signed_up'])): ?>
                <div class="auth-success">Account created. Please sign in.</div>
            <?php endif; ?>
            <?php if (!empty($_GET['error'])): ?>
                <div class="auth-error">Invalid email or password.</div>
            <?php endif; ?>
            <form action="auth-login-process.php" method="post" class="auth-form">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="email@example.com" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
                <button type="submit" class="btn btn-primary full-width" style="margin-top: 12px;">Login</button>
            </form>
            <p class="auth-switch">
                New here? <a href="page-register.php">Create an account</a>
            </p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
