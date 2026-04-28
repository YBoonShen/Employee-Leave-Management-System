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
    <title>NexusLeave - Sign Up</title>
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
            <h1>Create account</h1>
            <?php if (!empty($_GET['error'])): ?>
                <div class="auth-error">Registration failed. Email or employee ID may already be in use.</div>
            <?php endif; ?>
            <form action="auth-register-process.php" method="post" class="auth-form">
                <div class="form-row">
                    <div class="form-col">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="e.g. Ali Bin Ahmad" required>
                    </div>
                    <div class="form-col">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" placeholder="EMP001" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="ali@company.com" required>
                    </div>
                    <div class="form-col">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Department</label>
                        <input type="text" name="department" placeholder="e.g. IT, HR, Marketing" required>
                    </div>
                    <div class="form-col">
                        <label>Job Title</label>
                        <input type="text" name="job_title" placeholder="e.g. Software Engineer" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Phone Number (Malaysia)</label>
                        <input type="text" name="phone" value="+60" placeholder="+60 12-345 6789" required>
                    </div>
                    <div class="form-col">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Kuala Lumpur, Malaysia" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary full-width" style="margin-top: 20px;">Sign up</button>
            </form>
            <p class="auth-switch">
                Already have an account? <a href="page-login.php">Login</a>
            </p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
