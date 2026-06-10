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
    <link rel="stylesheet" href="styles.css?v=19">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <div class="auth-card">

            <div class="auth-logo-bar">
                <i class="fas fa-leaf"></i>
                <span>NexusLeave</span>
            </div>

            <!-- ── View 1: Login ── -->
            <div id="login-view">
                <h1>Welcome back</h1>
                <p class="text-muted">Sign in to manage your leave requests.</p>

                <?php if (!empty($_GET['just_signed_up'])): ?>
                    <div class="auth-success">Account created. Please sign in.</div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div class="auth-error">Invalid email or password.</div>
                <?php endif; ?>
                <?php if (!empty($_GET['password_reset'])): ?>
                    <div class="auth-success">Password updated. You can sign in now.</div>
                <?php endif; ?>

                <form action="auth-login-process.php" method="post" class="auth-form">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="email@example.com" required>
                    <div class="auth-password-row">
                        <label>Password</label>
                        <button type="button" class="auth-forgot-link" onclick="showStep('forgot-step1')">Forgot password?</button>
                    </div>
                    <div class="pw-input-wrap">
                        <input type="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                    <button type="submit" class="btn btn-primary full-width">Sign In</button>
                </form>

                <p class="auth-switch">
                    New here? <a href="page-register.php">Create an account</a>
                </p>
            </div>

            <!-- ── View 2: Reset — Step 1: Enter Email ── -->
            <div id="forgot-step1" class="forgot-view" style="display:none;">
                <button type="button" class="auth-back-btn" onclick="showStep('login-view')">
                    <i class="fas fa-arrow-left"></i> Back to Sign In
                </button>
                <h1>Reset Password</h1>
                <p class="text-muted">Enter your registered email to continue.</p>

                <div id="step1-error" class="auth-error" style="display:none;">No account found with that email.</div>

                <form class="auth-form" onsubmit="handleVerifyEmail(event)">
                    <label>Email Address</label>
                    <input type="email" id="reset-email" placeholder="email@example.com" required>
                    <button type="submit" class="btn btn-primary full-width" id="step1-btn">
                        <span id="step1-btn-text">Continue <i class="fas fa-arrow-right"></i></span>
                        <span id="step1-btn-loading" style="display:none;"><i class="fas fa-circle-notch fa-spin"></i></span>
                    </button>
                </form>
            </div>

            <!-- ── View 3: Reset — Step 2: Set New Password ── -->
            <div id="forgot-step2" class="forgot-view" style="display:none;">
                <button type="button" class="auth-back-btn" onclick="showStep('forgot-step1')">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h1>Set New Password</h1>
                <p class="text-muted">Choose a new password for <strong id="reset-email-display"></strong></p>

                <div id="step2-error" class="auth-error" style="display:none;"></div>

                <form class="auth-form" onsubmit="handleResetPassword(event)">
                    <label>New Password</label>
                    <div class="pw-input-wrap">
                        <input type="password" id="new-password" placeholder="Min 6 chars, letters + numbers + symbol" required>
                        <button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                    <div class="password-strength" id="reset-pw-strength"></div>
                    <label>Confirm Password</label>
                    <div class="pw-input-wrap">
                        <input type="password" id="confirm-password" placeholder="Repeat new password" required>
                        <button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                    </div>
                    <button type="submit" class="btn btn-primary full-width" id="step2-btn">
                        <span id="step2-btn-text"><i class="fas fa-lock"></i> Reset Password</span>
                        <span id="step2-btn-loading" style="display:none;"><i class="fas fa-circle-notch fa-spin"></i></span>
                    </button>
                </form>
            </div>

            <!-- ── View 4: Reset — Done ── -->
            <div id="forgot-done" class="forgot-view" style="display:none;">
                <div class="auth-reset-done">
                    <div class="auth-forgot-icon success"><i class="fas fa-check"></i></div>
                    <h2>Password Updated!</h2>
                    <p>Your password has been changed. You can now sign in with your new password.</p>
                    <button type="button" class="btn btn-primary full-width" onclick="showStep('login-view')">
                        <i class="fas fa-sign-in-alt"></i> Go to Sign In
                    </button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function togglePw(btn) {
            const input = btn.closest('.pw-input-wrap').querySelector('input');
            const icon  = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Returns an error string if password is weak, or null if it passes
        function checkPassword(pw) {
            if (pw.length < 6)            return 'Password must be at least 6 characters.';
            if (!/[A-Za-z]/.test(pw))     return 'Password must include at least one letter.';
            if (!/[0-9]/.test(pw))        return 'Password must include at least one number.';
            if (!/[^A-Za-z0-9]/.test(pw)) return 'Password must include at least one symbol (e.g. @, #, !).';
            return null;
        }

        function getStrengthBar(pw) {
            if (!pw) return '';
            let score = 0;
            if (pw.length >= 6)           score++;
            if (/[A-Za-z]/.test(pw))      score++;
            if (/[0-9]/.test(pw))         score++;
            if (/[^A-Za-z0-9]/.test(pw))  score++;
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
            return `<div class="pw-strength-bar">
                      <div style="width:${score*25}%; background:${colors[score]}; height:4px; border-radius:4px; transition:all 0.3s;"></div>
                    </div>
                    <span class="pw-strength-label" style="color:${colors[score]};">${labels[score]}</span>`;
        }

        document.getElementById('new-password').addEventListener('input', function () {
            document.getElementById('reset-pw-strength').innerHTML = getStrengthBar(this.value);
        });

        function showStep(id) {
            ['login-view','forgot-step1','forgot-step2','forgot-done'].forEach(v => {
                document.getElementById(v).style.display = 'none';
            });
            document.getElementById(id).style.display = 'block';
            ['step1-error','step2-error'].forEach(e => {
                const el = document.getElementById(e);
                if (el) el.style.display = 'none';
            });
        }

        async function handleVerifyEmail(e) {
            e.preventDefault();
            const email = document.getElementById('reset-email').value.trim();
            const btn = document.getElementById('step1-btn');
            document.getElementById('step1-btn-text').style.display = 'none';
            document.getElementById('step1-btn-loading').style.display = 'inline';
            btn.disabled = true;
            document.getElementById('step1-error').style.display = 'none';

            try {
                const res = await fetch('auth-forgot-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ step: 'verify', email })
                });
                const data = await res.json();

                if (data.found) {
                    document.getElementById('reset-email-display').textContent = email;
                    showStep('forgot-step2');
                } else {
                    document.getElementById('step1-error').style.display = 'block';
                }
            } catch {
                document.getElementById('step1-error').textContent = 'Something went wrong. Please try again.';
                document.getElementById('step1-error').style.display = 'block';
            }

            document.getElementById('step1-btn-text').style.display = 'inline';
            document.getElementById('step1-btn-loading').style.display = 'none';
            btn.disabled = false;
        }

        async function handleResetPassword(e) {
            e.preventDefault();
            const email = document.getElementById('reset-email').value.trim();
            const password = document.getElementById('new-password').value;
            const confirm = document.getElementById('confirm-password').value;
            const errEl = document.getElementById('step2-error');
            errEl.style.display = 'none';

            const pwErr = checkPassword(password);
            if (pwErr) {
                errEl.textContent = pwErr;
                errEl.style.display = 'block';
                return;
            }

            if (password !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.style.display = 'block';
                return;
            }

            const btn = document.getElementById('step2-btn');
            document.getElementById('step2-btn-text').style.display = 'none';
            document.getElementById('step2-btn-loading').style.display = 'inline';
            btn.disabled = true;

            try {
                const res = await fetch('auth-forgot-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ step: 'reset', email, password })
                });
                const data = await res.json();

                if (data.ok) {
                    showStep('forgot-done');
                } else {
                    errEl.textContent = data.error || 'Failed to update password.';
                    errEl.style.display = 'block';
                }
            } catch {
                errEl.textContent = 'Something went wrong. Please try again.';
                errEl.style.display = 'block';
            }

            document.getElementById('step2-btn-text').style.display = 'inline';
            document.getElementById('step2-btn-loading').style.display = 'none';
            btn.disabled = false;
        }
    </script>
</body>
</html>
