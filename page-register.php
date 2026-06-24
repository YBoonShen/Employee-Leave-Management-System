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

            <?php if (!empty($_GET['error'])):
                $errMsgs = [
                    'invalid_name'     => 'Full Name must contain letters, not numbers only.',
                    'invalid_empid'    => 'Employee ID must be EMP followed by exactly 3 digits (EMP001 to EMP999).',
                    'invalid_email'    => 'Please enter a valid email address.',
                    'weak_password'    => 'Password must be at least 8 characters and include uppercase, lowercase, a number and a symbol.',
                    'invalid_dept'     => 'Please select a valid department from the list.',
                    'invalid_jobtitle' => 'Please select a valid job title from the list.',
                    'invalid_location' => 'Please select a valid Malaysian state from the list.',
                    'name_reserved'    => 'That name is already in use by a manager account and cannot be registered.',
                    'exists'           => 'Registration failed. Email or Employee ID is already in use.',
                ];
                $code = $_GET['error'];
                $msg  = $errMsgs[$code] ?? 'Registration failed. Please check your details and try again.';
            ?>
                <div class="auth-error"><?= htmlspecialchars($msg) ?></div>
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
                               placeholder="EMP001" pattern="EMP[0-9]{3}" title="Must be EMP followed by exactly 3 digits, 001 to 999 (e.g. EMP001)" required>
                        <span class="field-hint">Format: EMP + 3 digits (EMP001 to EMP999)</span>
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
                            <input type="password" name="password" id="reg-password" placeholder="Min 8 chars, upper + lower + number + symbol" required>
                            <button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="password-strength" id="reg-pw-strength"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="" disabled selected>-- Select Department --</option>
                            <option>Administration</option>
                            <option>Business Development</option>
                            <option>Customer Service</option>
                            <option>Engineering</option>
                            <option>Finance &amp; Accounting</option>
                            <option>Human Resources</option>
                            <option>Information Technology</option>
                            <option>Legal &amp; Compliance</option>
                            <option>Logistics &amp; Supply Chain</option>
                            <option>Management</option>
                            <option>Marketing</option>
                            <option>Operations</option>
                            <option>Procurement</option>
                            <option>Quality Assurance</option>
                            <option>Research &amp; Development</option>
                            <option>Sales</option>
                        </select>
                    </div>
                    <div class="form-col">
                        <label>Job Title</label>
                        <select name="job_title" required>
                            <option value="" disabled selected>-- Select Job Title --</option>
                            <optgroup label="Management">
                                <option>Chief Executive Officer</option>
                                <option>Chief Operating Officer</option>
                                <option>Chief Financial Officer</option>
                                <option>Chief Technology Officer</option>
                                <option>Director</option>
                                <option>Senior Manager</option>
                                <option>Manager</option>
                                <option>Assistant Manager</option>
                            </optgroup>
                            <optgroup label="Professional">
                                <option>Senior Engineer</option>
                                <option>Engineer</option>
                                <option>Senior Developer</option>
                                <option>Developer</option>
                                <option>Senior Analyst</option>
                                <option>Analyst</option>
                                <option>Consultant</option>
                                <option>Specialist</option>
                                <option>Supervisor</option>
                            </optgroup>
                            <optgroup label="Support">
                                <option>Senior Executive</option>
                                <option>Executive</option>
                                <option>Coordinator</option>
                                <option>Administrator</option>
                                <option>Officer</option>
                                <option>Clerk</option>
                                <option>Intern</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <label>Phone Number (Malaysia)</label>
                        <input type="text" name="phone" value="+60" placeholder="+60 12-345 6789" required
                               oninput="enforcePhonePrefix(this)"
                               onkeydown="blockPhonePrefixDelete(this, event)">
                    </div>
                    <div class="form-col">
                        <label>Location</label>
                        <select name="location" required>
                            <option value="" disabled selected>-- Select State --</option>
                            <option>Johor</option>
                            <option>Kedah</option>
                            <option>Kelantan</option>
                            <option>Kuala Lumpur</option>
                            <option>Labuan</option>
                            <option>Melaka</option>
                            <option>Negeri Sembilan</option>
                            <option>Pahang</option>
                            <option>Penang</option>
                            <option>Perak</option>
                            <option>Perlis</option>
                            <option>Putrajaya</option>
                            <option>Sabah</option>
                            <option>Sarawak</option>
                            <option>Selangor</option>
                            <option>Terengganu</option>
                        </select>
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

        document.getElementById('reg-password').addEventListener('input', function () {
            document.getElementById('reg-pw-strength').innerHTML = getStrengthBar(this.value);
        });

        function enforcePhonePrefix(input) {
            if (!input.value.startsWith('+60')) {
                const stripped = input.value.replace(/^\+?6?0?/, '');
                input.value = '+60' + stripped;
            }
        }

        function blockPhonePrefixDelete(input, e) {
            const sel = input.selectionStart;
            if ((e.key === 'Backspace' || e.key === 'Delete') && sel <= 3) {
                e.preventDefault();
            }
        }

        function validateRegister(e) {
            const name  = document.querySelector('input[name="name"]').value.trim();
            const empId = document.getElementById('reg-emp-id').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const pw    = document.getElementById('reg-password').value;
            const errEl = document.getElementById('reg-error');

            function fail(msg) {
                errEl.textContent = msg;
                errEl.style.display = 'block';
                e.preventDefault();
                return false;
            }

            // Full name must contain at least one letter (not purely numeric)
            if (!/[A-Za-z]/.test(name)) {
                return fail('Full Name must contain letters, not numbers only.');
            }

            // Employee ID: exactly EMP + 3 digits, 001-999
            if (!/^EMP[0-9]{3}$/.test(empId)) {
                return fail('Employee ID must be EMP followed by exactly 3 digits (e.g. EMP001).');
            }
            const empNum = parseInt(empId.slice(3), 10);
            if (empNum < 1 || empNum > 999) {
                return fail('Employee ID number must be between 001 and 999.');
            }

            // Email must contain @ and a domain
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                return fail('Please enter a valid email address (e.g. ali@company.com).');
            }

            // Phone must start with +60
            const phone = document.querySelector('input[name="phone"]').value.trim();
            if (!/^\+60[\d\s\-]{7,13}$/.test(phone)) {
                return fail('Phone number must start with +60 and be a valid Malaysian number (e.g. +60 12-345 6789).');
            }

            // Password must be Strong (all 4 criteria)
            const pwErr = checkPassword(pw);
            if (pwErr) {
                return fail(pwErr);
            }

            errEl.style.display = 'none';
            return true;
        }

        // All 4 criteria required; returns error string or null
        function checkPassword(pw) {
            if (pw.length < 8)            return 'Password must be at least 8 characters.';
            if (!/[A-Z]/.test(pw))        return 'Password must include at least one uppercase letter.';
            if (!/[a-z]/.test(pw))        return 'Password must include at least one lowercase letter.';
            if (!/[0-9]/.test(pw))        return 'Password must include at least one number.';
            if (!/[^A-Za-z0-9]/.test(pw)) return 'Password must include at least one symbol (e.g. @, #, !).';
            return null;
        }

        function getStrengthBar(pw) {
            if (!pw) return '';
            let score = 0;
            if (pw.length >= 8)            score++;
            if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
            if (/[0-9]/.test(pw))          score++;
            if (/[^A-Za-z0-9]/.test(pw))  score++;

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
