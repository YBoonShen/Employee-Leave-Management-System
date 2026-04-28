<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusLeave - Enterprise Leave Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=12">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="role-employee">
    <div class="layout">
        <aside class="sidebar">
            <div class="logo"><i class="fas fa-shield-alt"></i><span>NexusLeave</span></div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-target="dashboard"><i class="fas fa-chart-pie"></i> Dashboard</a>
                <div class="nav-group employee-only">
                    <span class="nav-label">Employee</span>
                    <a href="#" class="nav-item" data-target="apply-leave"><i class="fas fa-paper-plane"></i> Apply Leave</a>
                    <a href="#" class="nav-item" data-target="leave-status"><i class="fas fa-list-check"></i> Leave Status</a>
                    <a href="#" class="nav-item" data-target="my-history"><i class="fas fa-history"></i> Leave History</a>
                </div>
                <div class="nav-group manager-only">
                    <span class="nav-label">Management</span>
                    <a href="#" class="nav-item" data-target="manager-approvals"><i class="fas fa-tasks"></i> Approvals</a>
                    <a href="#" class="nav-item" data-target="team-overview"><i class="fas fa-users"></i> Employee Management</a>
                </div>
                <div class="nav-group"><span class="nav-label">Alerts</span><a href="#" class="nav-item" data-target="notifications"><i class="fas fa-bell"></i> Notifications</a></div>
                <div class="nav-group">
                    <span class="nav-label">Account</span>
                    <a href="#" class="nav-item" data-target="profile"><i class="fas fa-user-circle"></i> My Profile</a>
                    <a href="auth-logout-process.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
            <div class="sidebar-footer"><div class="user-profile"><div class="avatar" id="user-avatar">??</div><div class="user-details"><span class="user-name" id="user-name">Loading...</span><span class="user-role" id="user-display-role">Employee</span></div></div></div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title"><h1 id="page-heading">Dashboard</h1><p id="page-subheading">Leave made simple.</p></div>
                <div class="topbar-actions">
                    <button class="icon-btn" id="btn-notifications"><i class="fas fa-bell"></i><span class="dot-indicator"></span></button>
                    <button class="btn btn-primary employee-only" id="btn-quick-apply">New Request</button>
                    <div class="profile-menu" id="profile-menu">
                        <div class="profile-info"><span class="profile-name" id="profile-name">Employee</span></div>
                        <div class="profile-avatar-small" id="profile-avatar">E</div>
                        <div class="profile-dropdown" id="profile-dropdown">
                            <button class="dropdown-item" onclick="app.switchTab('profile', 'My Profile')"><i class="fas fa-user-circle"></i> My Profile</button>
                            <a class="dropdown-item muted" href="auth-logout-process.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <section id="dashboard" class="page-section active">
                    <!-- Onboarding Stepper (Employee Only) -->
                    <div class="onboarding-header employee-only" style="margin-bottom: 24px;">
                        <h2 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--text-main);">Just follow the three steps below:</h2>
                        <div class="horizontal-stepper">
                            <div class="step-node active">
                                <span class="step-label">1. Check Balance</span>
                                <span class="step-caption">Review your available days</span>
                            </div>
                            <div class="step-connector"></div>
                            <div class="step-node">
                                <span class="step-label">2. Submit Request</span>
                                <span class="step-caption">Fill in the leave form</span>
                            </div>
                            <div class="step-connector"></div>
                            <div class="step-node">
                                <span class="step-label">3. Wait Approval</span>
                                <span class="step-caption">Get notified on status</span>
                            </div>
                        </div>
                    </div>

                    <!-- Manager Dashboard Stats -->
                    <div class="stats-grid manager-only">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fas fa-tasks"></i></div>
                            <div class="stat-info">
                                <h3>Pending Actions</h3>
                                <p class="stat-value" id="stat-mgr-pending">0</p>
                                <p class="stat-micro">Requests needing review</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fas fa-user-clock"></i></div>
                            <div class="stat-info">
                                <h3>Team on Leave</h3>
                                <p class="stat-value" id="stat-mgr-away">0</p>
                                <p class="stat-micro">Employees currently away</p>
                            </div>
                        </div>
                    </div>

                    <div class="manager-only" style="margin-bottom: 30px;">
                        <div class="table-card">
                            <div class="card-header"><h2>Who's Away Today</h2></div>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead><tr><th>Employee</th><th>Type</th><th>Until</th></tr></thead>
                                    <tbody id="mgr-dash-away"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Dashboard Stats -->
                    <div class="stats-grid employee-only">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-info">
                                <h3>Annual Allowance</h3>
                                <p class="stat-value" id="stat-total">21 Days</p>
                                <div class="progress-rail"><div class="progress-fill" style="width: 100%"></div></div>
                                <p class="stat-micro">Total entitlement for 2024</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fas fa-umbrella-beach"></i></div>
                            <div class="stat-info">
                                <h3>Leave Taken</h3>
                                <p class="stat-value" id="stat-taken">0 Days</p>
                                <div class="progress-rail"><div class="progress-fill" id="progress-taken" style="width: 0%"></div></div>
                                <p class="stat-micro">Approved leave requests</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon purple"><i class="fas fa-hourglass-half"></i></div>
                            <div class="stat-info">
                                <h3>Balance</h3>
                                <p class="stat-value highlight" id="stat-balance">21 Days</p>
                                <div class="progress-rail"><div class="progress-fill" id="progress-balance" style="width: 100%"></div></div>
                                <p class="stat-micro">Days remaining to use</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-card manager-only" style="margin-top: 24px;">
                        <div class="card-header">
                            <h2>Recent Employee Activity</h2>
                            <button class="btn btn-outline btn-sm" onclick="app.switchTab('manager-approvals', 'Approvals')">View All Approvals</button>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Period</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="mgr-dashboard-activity">
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="table-card employee-only">
                        <div class="card-header">
                            <h2>Latest Activity</h2>
                            <button class="btn btn-outline btn-sm" onclick="app.switchTab('leave-status', 'Leave Status')">View All</button>
                        </div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Period</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboard-requests-list">
                                    <!-- Dynamic content -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="apply-leave" class="page-section">
                    <div class="glass-form-container">
                        <div id="apply-leave-form-container">
                            <div class="form-header"><h2>Submit Request</h2></div>
                            <form id="leave-request-form" onsubmit="app.handleApplicationSubmit(event)">
                                <div class="form-grid">
                                    <div class="form-group full-width"><label>Leave Type</label>
                                        <div class="type-selector">
                                            <input type="radio" name="leave-type" id="t-annual" value="Annual" checked><label for="t-annual">Annual</label>
                                            <input type="radio" name="leave-type" id="t-sick" value="Sick"><label for="t-sick">Sick</label>
                                            <input type="radio" name="leave-type" id="t-unpaid" value="Unpaid"><label for="t-unpaid">Unpaid</label>
                                        </div>
                                    </div>
                                    <div class="form-group"><label>Start Date</label><input type="date" id="start-date" required></div>
                                    <div class="form-group"><label>End Date</label><input type="date" id="end-date" required></div>
                                    <div class="form-group full-width"><label>Reason</label><textarea id="reason" rows="3"></textarea></div>
                                </div>
                                <div class="form-footer"><button type="submit" class="btn btn-primary">Submit Application</button></div>
                            </form>
                        </div>
                        <div id="apply-leave-success" class="form-success-state" style="display:none"><h2>Submitted!</h2><button class="btn btn-primary" onclick="app.switchTab('dashboard', 'Dashboard')">Back</button></div>
                    </div>
                </section>

                <section id="leave-status" class="page-section">
                    <div class="table-card">
                        <div class="card-header"><h2>Leave Status</h2></div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th style="width:40px"></th><th>Type</th><th>Period</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody id="status-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="my-history" class="page-section">
                    <div class="table-card">
                        <div class="card-header"><h2>Leave History</h2></div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead><tr><th style="width:40px"></th><th>Date</th><th>Type</th><th>Period</th><th>Duration</th><th>Status</th></tr></thead>
                                <tbody id="history-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="manager-approvals" class="page-section">
                    <div class="table-card"><div class="card-header"><h2>Approvals</h2></div>
                        <div class="table-container"><table class="data-table"><thead><tr><th>Employee</th><th>Type</th><th>Duration</th><th>Action</th></tr></thead><tbody id="manager-table-body"></tbody></table></div>
                    </div>
                </section>
                
                <section id="team-overview" class="page-section">
                    <div class="table-card"><div class="card-header"><h2>Users</h2></div><div class="table-container"><table class="data-table"><thead><tr><th>Name</th><th>ID</th><th>Role</th><th>Dept</th><th>Action</th></tr></thead><tbody id="user-management-table-body"></tbody></table></div></div>
                </section>

                <section id="notifications" class="page-section">
                    <div class="table-card"><div class="card-header"><h2>Notifications</h2></div><div class="notification-list" id="notification-list"></div></div>
                </section>

                <section id="profile" class="page-section">
                    <div id="profile-content">
                        <!-- Profile content rendered by JS -->
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div id="detail-modal" class="modal-overlay"><div class="modal-card"><div class="modal-header"><h3>Details</h3><button onclick="app.closeModal()">&times;</button></div><div class="modal-body" id="modal-content"></div></div></div>
    <div id="toast" class="toast"><div class="toast-content"><div class="message"><span class="toast-title" id="toast-title"></span><span class="toast-desc" id="toast-desc"></span></div></div></div>
    
    <script src="script.js?v=12"></script>
</body>
</html>
