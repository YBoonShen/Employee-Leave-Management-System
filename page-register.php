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
    <link rel="stylesheet" href="styles.css?v=24">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-layout">
        <div class="auth-card auth-card-wide">

            <div class="auth-logo-bar">
                <i class="fas fa-leaf"></i>
                <span>NexusLeave</span>
            </div>

            <h1>Create account</h1>
            <p class="text-muted">Fill in your details to get started.</p>

            <?php if (!empty($_GET['error'])): ?>
                <div class="auth-error">Registration failed. Email or employee ID may already be in use.</div>
            <?php endif; ?>

            <div id="reg-error" class="auth-error" style="display:none;"></div>

            <form action="auth-register-process.php" method="post" class="auth-form" onsubmit="return validateRegister(event)">
                <div class="form-row">
                    <div class="form-col">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="e.g. Ali Bin Ahmad" required>
                    </div>
                    <div class="form-col">
                        <label>Employee ID</label>
                        <!-- Must start with EMP followed by digits, e.g. EMP001 -->
                        <input type="text" name="employee_id" id="reg-emp-id"
                               placeholder="EMP001" pattern="EMP\d+" title="Must start with EMP followed by numbers (e.g. EMP001)" required>
                        <span class="field-hint">Format: EMP + numbers (e.g. EMP001)</span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="ali@company.com" required>
                    </div>
                    <div class="form-col">
                        <label>Password</label>
                        <div class="pw-input-wrap">
                            <input type="password" name="password" id="reg-password" placeholder="Min 6 chars, letters + numbers + symbol" required>
                            <button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="password-strength" id="reg-pw-strength"></div>
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

                <div class="form-row">
                    <div class="form-col">
                        <label>Join Date</label>
                        <!-- Used to calculate leave tier under Malaysia Employment Act 1955 -->
                        <input type="date" name="join_date" id="reg-join-date" max="<?= date('Y-m-d') ?>" required>
                        <span class="field-hint">Your first official day of employment</span>
                    </div>
                    <div class="form-col"></div>
                </div>

                <!-- Employment type card selector -->
                <div class="form-group-full">
                    <label class="emp-type-label">Employment Type</label>
                    <p class="emp-type-sublabel">Select your employment status. This determines your annual leave entitlement.</p>
                    <div class="emp-type-grid">

                        <input type="radio" name="employment_type" id="etype-permanent" value="Permanent" checked>
                        <label for="etype-permanent" class="emp-type-card">
                            <div class="emp-type-icon-wrap emp-type-blue">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="emp-type-body">
                                <span class="emp-type-name">Permanent</span>
                                <span class="emp-type-desc">Full-time, indefinite employment</span>
                            </div>
                            <span class="emp-type-badge emp-badge-blue">8 – 16 days / year</span>
                        </label>

                        <input type="radio" name="employment_type" id="etype-contract" value="Contract">
                        <label for="etype-contract" class="emp-type-card">
                            <div class="emp-type-icon-wrap emp-type-amber">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="emp-type-body">
                                <span class="emp-type-name">Contract</span>
                                <span class="emp-type-desc">Fixed-term employment agreement</span>
                            </div>
                            <span class="emp-type-badge emp-badge-amber">8 – 16 days / year</span>
                        </label>

                        <input type="radio" name="employment_type" id="etype-parttime" value="Part-Time">
                        <label for="etype-parttime" class="emp-type-card">
                            <div class="emp-type-icon-wrap emp-type-purple">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="emp-type-body">
                                <span class="emp-type-name">Part-Time</span>
                                <span class="emp-type-desc">Reduced hours, flexible schedule</span>
                            </div>
                            <span class="emp-type-badge emp-badge-purple">4 – 8 days / year</span>
                        </label>

                    </div>
                </div>

                <button type="submit" class="btn btn-primary full-width">Create Account</button>
            </form>

            <p class="auth-switch">
                Already have an account? <a href="page-login.php">Sign in</a>
            </p>
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

        // Show live password strength feedback
        document.getElementById('reg-password').addEventListener('input', function () {
            document.getElementById('reg-pw-strength').innerHTML = getStrengthBar(this.value);
        });

        function validateRegister(e) {
            const empId = document.getElementById('reg-emp-id').value.trim();
            const pw    = document.getElementById('reg-password').value;
            const errEl = document.getElementById('reg-error');

            // Employee ID must be EMP + digits
            if (!/^EMP\d+$/.test(empId)) {
                errEl.textContent = 'Employee ID must start with "EMP" followed by numbers (e.g. EMP001).';
                errEl.style.display = 'block';
                e.preventDefault();
                return false;
            }

            const pwErr = checkPassword(pw);
            if (pwErr) {
                errEl.textContent = pwErr;
                errEl.style.display = 'block';
                e.preventDefault();
                return false;
            }

            errEl.style.display = 'none';
            return true;
        }

        // Returns an error message if the password is too weak, or null if it passes
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
            if (pw.length >= 6)            score++;
            if (/[A-Za-z]/.test(pw))       score++;
            if (/[0-9]/.test(pw))          score++;
            if (/[^A-Za-z0-9]/.test(pw))   score++;

            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
            return `<div class="pw-strength-bar">
                      <div style="width:${score * 25}%; background:${colors[score]}; height:4px; border-radius:4px; transition:all 0.3s;"></div>
                    </div>
                    <span class="pw-strength-label" style="color:${colors[score]};">${labels[score]}</span>`;
        }
    </script>
</body>
</html>
