/**
 * NexusLeave - Core Logic
 */

const API = {
  GET_REQUESTS: 'api-leave-fetch.php',
  GET_ANALYTICS: 'api-stats-fetch.php',
  GET_USERS: 'api-user-fetch-all.php',
  GET_NOTIFICATIONS: 'api-notify-fetch.php',
  DELETE_USER: 'api-user-delete.php',
  CREATE_REQUEST: 'api-leave-create.php',
  EDIT_REQUEST: 'api-leave-edit.php',
  DELETE_REQUEST: 'api-leave-cancel.php',
  UPDATE_STATUS: 'api-leave-approve.php',
  MARK_READ: 'api-notify-read.php',
  DELETE_NOTIFICATION: 'api-notify-delete.php',
  UPDATE_PROFILE: 'api-user-profile-update.php',
  CHANGE_PASSWORD: 'api-user-password-change.php',
};

const STATUS = { APPROVED: 'Approved', REJECTED: 'Rejected', PENDING: 'Pending' };

const app = {
  state: {
    role: 'employee',
    currentUser: { name: '', id: '', allowance: 21, employee_id: '' },
    requests: [],
    editingRequestId: null,
    uploadedFiles: [],
  },

  async init() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start-date')?.setAttribute('min', today);
    document.getElementById('end-date')?.setAttribute('min', today);

    if (window.NEXUS_USER) {
      this.state.currentUser.name = window.NEXUS_USER.name;
      this.state.currentUser.employee_id = window.NEXUS_USER.id;
      this.state.role = window.NEXUS_USER.role;
      document.body.className = `role-${this.state.role}`;
    }
    await this.loadFromServer();
    this.bindEvents();
    this.updateUI();
  },

  async fetchAPI(endpoint, method = 'GET', payload = null) {
    try {
      const opts = { method };
      if (payload) opts.body = JSON.stringify(payload);
      const res = await fetch(endpoint, opts);
      return await res.json();
    } catch (e) { return null; }
  },

  async loadFromServer() {
    const data = await this.fetchAPI(API.GET_REQUESTS);
    if (!data) return;
    if (data.user) {
      this.state.currentUser = { ...this.state.currentUser, ...data.user };
      this.state.role = data.user.role;
    }
    if (Array.isArray(data.requests)) this.state.requests = data.requests;
  },

  getInitials(name) { return name ? name.split(' ').map(p => p[0]).join('').toUpperCase() : '??'; },

  formatDate(d) { return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); },

  // New: Calculate duration excluding weekends
  calculateWorkDays(start, end) {
    const startDate = new Date(start);
    const endDate = new Date(end);
    let count = 0;
    const curDate = new Date(startDate.getTime());
    while (curDate <= endDate) {
      const dayOfWeek = curDate.getDay();
      if (dayOfWeek !== 0 && dayOfWeek !== 6) count++;
      curDate.setDate(curDate.getDate() + 1);
    }
    return count;
  },

  async refreshAndRender() { await this.loadFromServer(); this.renderTables(); this.updateUI(); },

  renderTables() {
    const myId = this.state.currentUser.employee_id;
    const today = new Date().toISOString().split('T')[0];
    
    // 1. Dashboard Table (Universal)
    const dash = document.getElementById('dashboard-requests-list');
    if (dash && this.state.role === 'employee') {
      const list = this.state.requests.filter(r => r.empId === myId).slice(0, 5);
      if (list.length === 0) {
        dash.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 20px;">No recent activity.</td></tr>`;
      } else {
        dash.innerHTML = list.map(r => `<tr onclick="app.viewRequestDetails(${r.id})" style="cursor:pointer">
          <td><div class="employee-cell"><div class="employee-avatar">${this.getInitials(r.empName)}</div>
          <div class="employee-meta"><span class="employee-name">${r.empName}</span><span class="employee-id">${r.empId}</span></div></div></td>
          <td><strong>${r.type}</strong></td><td>${this.formatDate(r.start)}</td><td>${r.duration}d</td>
          <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td></tr>`).join('');
      }
    }

    // 1b. Manager Specific Dashboard Tables
    if (this.state.role === 'manager') {
      const pendingList = this.state.requests.filter(r => r.status === 'Pending');
      const awayList = this.state.requests.filter(r => r.status === 'Approved' && today >= r.start && today <= r.end);
      const allActivity = [...this.state.requests].sort((a, b) => b.id - a.id);

      if (document.getElementById('stat-mgr-pending')) document.getElementById('stat-mgr-pending').innerText = pendingList.length;
      if (document.getElementById('stat-mgr-away')) document.getElementById('stat-mgr-away').innerText = awayList.length;

      const mgrAwayTable = document.getElementById('mgr-dash-away');
      if (mgrAwayTable) {
        if (awayList.length === 0) {
          mgrAwayTable.innerHTML = `<tr><td colspan="3" style="text-align:center; padding: 30px; color: var(--success); font-weight: 500;"><i class="fas fa-check-circle"></i> Everyone is in office today!</td></tr>`;
        } else {
          mgrAwayTable.innerHTML = awayList.slice(0, 5).map(r => `<tr>
            <td><div class="employee-cell"><span class="employee-name">${r.empName}</span></div></td><td>${r.type}</td><td>Until ${this.formatDate(r.end)}</td></tr>`).join('');
        }
      }

      const mgrActivityTable = document.getElementById('mgr-dashboard-activity');
      if (mgrActivityTable) {
        if (allActivity.length === 0) {
          mgrActivityTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 20px;">No employee activity found.</td></tr>`;
        } else {
          mgrActivityTable.innerHTML = allActivity.slice(0, 5).map(r => `<tr onclick="app.viewRequestDetails(${r.id})" style="cursor:pointer">
            <td><div class="employee-cell"><div class="employee-avatar">${this.getInitials(r.empName)}</div>
            <div class="employee-meta"><span class="employee-name">${r.empName}</span><span class="employee-id">${r.empId}</span></div></div></td>
            <td><strong>${r.type}</strong></td><td>${this.formatDate(r.start)}</td><td>${r.duration}d</td>
            <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td></tr>`).join('');
        }
      }
    }

    // 2. Leave Status Table (Now ONLY showing Pending requests)
    const statusTable = document.getElementById('status-table-body');
    if (statusTable) {
      // Logic: Only show what is still waiting for manager's action
      const list = this.state.requests.filter(r => r.empId === myId && r.status === 'Pending');
      if (list.length === 0) {
        statusTable.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-muted);"><i class="fas fa-check-circle" style="display:block; font-size: 2rem; margin-bottom: 10px; color: var(--success);"></i>No active requests. All caught up!</td></tr>`;
      } else {
        statusTable.innerHTML = list.map(r => `
          <tr id="row-${r.id}">
            <td><button class="expand-toggle" onclick="app.toggleRow(${r.id})">+</button></td>
            <td><strong>${r.type}</strong></td>
            <td>${this.formatDate(r.start)} - ${this.formatDate(r.end)}</td>
            <td>${r.duration}d</td>
            <td><span class="badge status-pending">Waiting Manager</span></td>
            <td>
              <div class="action-group" style="gap: 5px;">
                <button class="btn btn-sm" onclick="app.editRequest(${r.id})"><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-sm btn-reject" onclick="app.cancelRequest(${r.id})"><i class="fas fa-times"></i> Cancel</button>
              </div>
            </td>
          </tr>
        `).join('');
      }
    }

    // 3. Leave History Table (Now showing ALL Resolved or Past requests)
    const historyTable = document.getElementById('history-table-body');
    if (historyTable) {
      // Logic: Show everything that is NOT pending anymore (Approved/Rejected)
      const list = this.state.requests.filter(r => r.empId === myId && r.status !== 'Pending');
      if (list.length === 0) {
        historyTable.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px; color: var(--text-muted);">No leave history yet.</td></tr>`;
      } else {
        historyTable.innerHTML = list.map(r => `
          <tr id="row-hist-${r.id}">
            <td><button class="expand-toggle" onclick="app.toggleRow(${r.id}, 'hist')">+</button></td>
            <td>${r.date}</td>
            <td><strong>${r.type}</strong></td>
            <td>${this.formatDate(r.start)} - ${this.formatDate(r.end)}</td>
            <td>${r.duration}d</td>
            <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
          </tr>
        `).join('');
      }
    }

    // 4. Manager Approval Table
    const mgrTable = document.getElementById('manager-table-body');
    if (mgrTable) {
      const list = this.state.requests.filter(r => r.status === 'Pending');
      if (list.length === 0) {
        mgrTable.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No pending requests.</td></tr>`;
      } else {
        mgrTable.innerHTML = list.map(r => `<tr>
          <td><div class="employee-cell"><span class="employee-name">${r.empName}</span></div></td>
          <td>${r.type}</td>
          <td>${r.duration}d</td>
          <td>
            ${r.proofFiles && r.proofFiles.length > 0
              ? `<span class="proof-badge" title="${r.proofFiles.length} file(s) attached"><i class="fas fa-paperclip"></i> ${r.proofFiles.length}</span>`
              : `<span style="color:#cbd5e1; font-size:0.8rem;">—</span>`}
          </td>
          <td>
            <div class="action-group">
              <button class="btn btn-sm btn-outline" onclick="app.viewRequestDetails(${r.id})" title="View Details &amp; Files"><i class="fas fa-eye"></i></button>
              <button class="btn btn-sm btn-approve" onclick="app.approveRequest(${r.id})">Approve</button>
              <button class="btn btn-sm btn-reject" onclick="app.rejectRequest(${r.id})">Reject</button>
            </div>
          </td>
        </tr>`).join('');
      }
    }
  },

  toggleRow(id, prefix = '') {
    const rowId = prefix ? `row-${prefix}-${id}` : `row-${id}`;
    const row = document.getElementById(rowId);
    const btn = row.querySelector('.expand-toggle');
    const nextRow = row.nextElementSibling;

    if (nextRow && nextRow.classList.contains('expanded-row')) {
      nextRow.remove();
      btn.innerText = '+';
      return;
    }

    const req = this.state.requests.find(r => r.id == id);
    const expandedHTML = `
      <tr class="expanded-row">
        <td colspan="6">
          <div class="expanded-content">
            <div class="grid-2">
              <div>
                <span class="reason-detail-label">My Reason</span>
                <p class="reason-detail-text">${req.reason || 'No reason provided.'}</p>
              </div>
              <div>
                <span class="reason-detail-label">Manager's Feedback</span>
                <p class="reason-detail-text">${req.comment || 'Waiting for review...'}</p>
              </div>
            </div>
          </div>
        </td>
      </tr>
    `;
    row.insertAdjacentHTML('afterend', expandedHTML);
    btn.innerText = '-';
  },

  async switchTab(id, title) {
    // Hide all sections, show the active one
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const targetSection = document.getElementById(id);
    if (targetSection) targetSection.classList.add('active');

    // Sync Sidebar Active State
    document.querySelectorAll('.nav-item').forEach(n => {
      n.classList.remove('active');
      if (n.getAttribute('data-target') === id) {
        n.classList.add('active');
      }
    });

    // Update Page Header
    if (document.getElementById('page-heading')) {
      document.getElementById('page-heading').innerText = title;
    }
    
    // Load Specific Section Data
    if (id === 'profile') this.renderProfile();
    if (id === 'team-overview') this.loadUsers();
    if (id === 'notifications') this.loadNotifications();
    
    // Global data refresh for current state
    this.renderTables();
  },

  async loadUsers() {
    if (this.state.role !== 'manager') return;
    const data = await this.fetchAPI(API.GET_USERS);
    const tbody = document.getElementById('user-management-table-body');
    if (!tbody || !data || data.error) return;

    const userList = data.users || [];

    if (userList.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No employees found.</td></tr>`;
      return;
    }

    tbody.innerHTML = userList.map(u => `
      <tr>
        <td>
          <div class="employee-cell clickable" onclick="app.viewEmployeeProfile(${u.id})">
            <div class="employee-avatar">${this.getInitials(u.name)}</div>
            <div class="employee-meta"><span class="employee-name">${u.name}</span></div>
          </div>
        </td>
        <td>${u.employee_id}</td>
        <td>${u.role}</td>
        <td>${u.department || '-'}</td>
        <td>
          <div class="action-group" style="gap:5px;">
            <button class="btn btn-sm btn-outline" onclick="app.viewEmployeeProfile(${u.id})" title="View Profile"><i class="fas fa-id-card"></i></button>
            ${u.id != this.state.currentUser.id ? 
              `<button class="btn btn-sm btn-reject" onclick="app.deleteUser(${u.id})" title="Delete"><i class="fas fa-trash"></i></button>` : 
              `<span class="badge status-approved">You</span>`}
          </div>
        </td>
      </tr>
    `).join('');
  },

  async viewEmployeeProfile(userId) {
    // Show a small loader in the modal first
    document.getElementById('modal-content').innerHTML = `
      <div style="text-align:center; padding: 50px; color: var(--text-sub);">
        <i class="fas fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 15px;"></i>
        <p>Fetching employee records...</p>
      </div>
    `;
    document.querySelector('.modal-header h3').innerText = 'Employee Profile';
    document.getElementById('detail-modal').classList.add('open');

    const data = await this.fetchAPI(`${API.GET_REQUESTS}?user_id=${userId}`);
    if (!data || !data.user) {
      document.getElementById('modal-content').innerHTML = '<p style="padding:20px; text-align:center;">Error loading employee data.</p>';
      return;
    }

    const u = data.user; // Correctly use the user object returned from API
    const total = u.allowance || 21;
    const taken = data.requests
      .filter(r => r.status === 'Approved')
      .reduce((sum, r) => sum + Number(r.duration), 0);
    const balance = total - taken;

    const content = `
      <div class="employee-profile-split" style="display: flex; gap: 24px; align-items: flex-start;">
        <!-- Left Column: Identity & Contact -->
        <div class="profile-side-col" style="flex: 0 0 320px; background: #f8fafc; border-radius: 16px; padding: 24px; border: 1px solid #edf2f7;">
          <div style="text-align: center; margin-bottom: 24px;">
            <div class="profile-avatar-large" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 16px; border: 5px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">${this.getInitials(u.name)}</div>
            <h2 style="font-size: 1.3rem; color: var(--text-main); margin: 0;">${u.name}</h2>
            <span class="job-badge" style="display: inline-block; margin-top: 8px; background: var(--primary); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px;">${u.role.toUpperCase()}</span>
          </div>

          <div class="info-list" style="display: flex; flex-direction: column; gap: 16px;">
            <div class="info-item-small"><i class="fas fa-id-card" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Employee ID</span><strong>${u.employee_id}</strong></div></div>
            <div class="info-item-small"><i class="fas fa-envelope" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Email</span><strong>${u.email}</strong></div></div>
            <div class="info-item-small"><i class="fas fa-phone" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Phone</span><strong>${u.phone || 'N/A'}</strong></div></div>
            <div class="info-item-small"><i class="fas fa-building" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Department</span><strong>${u.department || 'N/A'}</strong></div></div>
            <div class="info-item-small"><i class="fas fa-briefcase" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Job Title</span><strong>${u.job_title || 'N/A'}</strong></div></div>
            <div class="info-item-small"><i class="fas fa-map-marker-alt" style="width:20px; color: var(--primary);"></i><div style="flex:1;"><span style="display:block; font-size: 0.7rem; color: var(--text-sub); text-transform: uppercase;">Location</span><strong>${u.location || 'N/A'}</strong></div></div>
          </div>
        </div>

        <!-- Right Column: Stats & History -->
        <div class="profile-main-col" style="flex: 1;">
          <!-- Stats Row -->
          <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
            <div class="stat-card" style="background: #fff; border: 1px solid #edf2f7; padding: 20px; text-align: center; border-radius: 12px;">
              <h3 style="font-size: 0.8rem; color: var(--text-sub); margin-bottom: 8px;">Allowance</h3>
              <p style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">${total}</p>
            </div>
            <div class="stat-card" style="background: #fff; border: 1px solid #edf2f7; padding: 20px; text-align: center; border-radius: 12px;">
              <h3 style="font-size: 0.8rem; color: var(--text-sub); margin-bottom: 8px;">Taken</h3>
              <p style="font-size: 1.5rem; font-weight: 700; color: #ef4444;">${taken}</p>
            </div>
            <div class="stat-card" style="background: #fff; border: 1px solid #edf2f7; padding: 20px; text-align: center; border-radius: 12px;">
              <h3 style="font-size: 0.8rem; color: var(--text-sub); margin-bottom: 8px;">Balance</h3>
              <p style="font-size: 1.5rem; font-weight: 700; color: var(--success);">${balance}</p>
            </div>
          </div>

          <!-- History Table -->
          <div class="table-card" style="box-shadow: none; border: 1px solid #edf2f7; border-radius: 12px; overflow: hidden;">
            <div class="card-header" style="padding: 16px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;">
              <i class="fas fa-history" style="color: var(--primary);"></i>
              <h4 style="font-size: 1rem; color: var(--text-main); margin:0;">Leave History</h4>
            </div>
            <div class="table-container" style="max-height: 400px; overflow-y: auto;">
              <table class="data-table">
                <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                  <tr><th>Type</th><th>Start Date</th><th>Days</th><th>Status</th></tr>
                </thead>
                <tbody>
                  ${data.requests.map(r => `
                    <tr>
                      <td><span style="font-weight:600; color: var(--text-main);">${r.type}</span></td>
                      <td>${this.formatDate(r.start)}</td>
                      <td>${r.duration}d</td>
                      <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
                    </tr>
                  `).join('') || '<tr><td colspan="4" style="text-align:center; padding:40px; color:var(--text-muted);">No records found.</td></tr>'}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <style>
        .info-item-small { display: flex; align-items: center; gap: 12px; }
        .info-item-small strong { font-size: 0.9rem; color: var(--text-main); }
      </style>
    `;
    document.getElementById('modal-content').innerHTML = content;
  },

  async deleteUser(id) {
    if (!confirm('CRITICAL WARNING: Are you absolutely sure you want to permanently delete this employee? This will wipe all their leave records.')) return;
    const res = await this.fetchAPI(API.DELETE_USER, 'POST', { id });
    if (res && !res.error) {
      this.showToast('Deleted', 'Employee account has been removed.');
      this.loadUsers();
    } else {
      this.showToast('Error', res?.error || 'Failed to delete user.', 'danger');
    }
  },

  async loadNotifications() {
    const data = await this.fetchAPI(API.GET_NOTIFICATIONS);
    const list = document.getElementById('notification-list');
    if (!list || !data || data.error) return;

    const notifs = data.notifications || [];

    if (notifs.length === 0) {
      list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted);">You have no notifications.</div>`;
      document.querySelector('.dot-indicator').style.display = 'none';
      return;
    }

    const unread = notifs.some(n => n.is_read == 0);
    document.querySelector('.dot-indicator').style.display = unread ? 'block' : 'none';

    list.innerHTML = notifs.map(n => `
      <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: ${n.is_read == 0 ? 'var(--primary-light)' : 'transparent'};">
        <div>
          <div style="font-weight: 600; margin-bottom: 5px;">
            <i class="fas ${n.type === 'request' ? 'fa-paper-plane' : 'fa-bell'}" style="font-size: 0.8rem; margin-right: 5px; color: var(--primary);"></i>
            ${n.title || (n.type === 'request' ? 'Leave Request' : 'Status Update')}
          </div>
          <div style="color: var(--text-sub); font-size: 0.9rem;">${n.message}</div>
          <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 5px;">${this.formatDate(n.created_at)}</div>
        </div>
        ${n.is_read == 0 ? `<button class="btn btn-sm btn-outline" onclick="app.markNotificationRead(${n.id})">Mark Read</button>` : `<i class="fas fa-check text-muted"></i>`}
      </div>
    `).join('');
  },

  async markNotificationRead(id) {
    await this.fetchAPI(API.MARK_READ, 'POST', { id });
    this.loadNotifications();
  },

  updateUI() {
    const u = this.state.currentUser;
    const total = u.allowance || 21;
    const taken = this.state.requests
      .filter(r => r.empId === u.employee_id && r.status === 'Approved')
      .reduce((sum, r) => sum + Number(r.duration), 0);
    const balance = total - taken;

    document.getElementById('user-name').innerText = u.name;
    document.getElementById('profile-name').innerText = u.name;
    document.getElementById('profile-avatar').innerText = this.getInitials(u.name);
    document.getElementById('user-avatar').innerText = this.getInitials(u.name);
    
    // Dynamic Role Label
    const roleLabel = u.role.charAt(0).toUpperCase() + u.role.slice(1);
    if (document.getElementById('user-display-role')) {
      document.getElementById('user-display-role').innerText = roleLabel;
    }
    if (document.getElementById('stat-total')) document.getElementById('stat-total').innerText = `${total} Days`;
    if (document.getElementById('stat-taken')) document.getElementById('stat-taken').innerText = `${taken} Days`;
    if (document.getElementById('stat-balance')) document.getElementById('stat-balance').innerText = `${balance} Days`;

    // Update Progress Bars
    const takenPct = (taken / total) * 100;
    const balancePct = (balance / total) * 100;
    if (document.getElementById('progress-taken')) document.getElementById('progress-taken').style.width = `${takenPct}%`;
    if (document.getElementById('progress-balance')) document.getElementById('progress-balance').style.width = `${balancePct}%`;

    this.renderTables();
  },

  renderProfile() {
    const u = this.state.currentUser;
    const container = document.getElementById('profile-content');
    if (!container) return;
    
    container.innerHTML = `
      <div class="profile-stack" style="display: flex; flex-direction: column; gap: 30px; max-width: 900px; margin: 0 auto;">
        <!-- Top: Info Card -->
        <div class="profile-card">
          <div class="profile-header-bg"></div>
          <div class="profile-main-info">
            <div class="profile-avatar-large">${this.getInitials(u.name)}</div>
            <div class="profile-title-group">
              <h2>${u.name}</h2>
              <span class="job-badge">${u.role.toUpperCase()}</span>
            </div>
          </div>
          <div class="profile-info-sections">
            <div class="info-block">
              <span class="section-label">Employment & Contact Details</span>
              <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="info-item"><i class="fas fa-user"></i><div class="info-content"><span>Full Name</span><strong>${u.name}</strong></div></div>
                <div class="info-item"><i class="fas fa-envelope"></i><div class="info-content"><span>Work Email</span><strong>${u.email}</strong></div></div>
                <div class="info-item"><i class="fas fa-id-card"></i><div class="info-content"><span>Employee ID</span><strong>${u.employee_id}</strong></div></div>
                <div class="info-item"><i class="fas fa-building"></i><div class="info-content"><span>Department</span><strong>${u.department || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-phone"></i><div class="info-content"><span>Phone Number</span><strong>${u.phone || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-briefcase"></i><div class="info-content"><span>Job Title</span><strong>${u.job_title || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-map-marker-alt"></i><div class="info-content"><span>Location</span><strong>${u.location || 'N/A'}</strong></div></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom: Full Edit Form -->
        <div class="profile-edit-card">
          <div class="form-header align-left">
            <h2>Manage Profile</h2>
            <p class="text-muted">Update all personal and employment information.</p>
          </div>
          <form id="profile-edit-form" onsubmit="app.handleProfileUpdate(event)">
            <div class="edit-form-grid">
              <div class="form-group"><label><i class="fas fa-user"></i> Full Name</label><input type="text" name="name" value="${u.name}" required></div>
              <div class="form-group"><label><i class="fas fa-envelope"></i> Work Email</label><input type="email" name="email" value="${u.email}" required></div>
              <div class="form-group"><label><i class="fas fa-id-card"></i> Employee ID</label><input type="text" name="employee_id" value="${u.employee_id}" required></div>
              <div class="form-group"><label><i class="fas fa-building"></i> Department</label><input type="text" name="department" value="${u.department || ''}" placeholder="e.g. IT"></div>
              <div class="form-group"><label><i class="fas fa-phone"></i> Phone Number</label><input type="text" name="phone" value="${u.phone || ''}" placeholder="+60 12-345 6789"></div>
              <div class="form-group"><label><i class="fas fa-briefcase"></i> Job Title</label><input type="text" name="job_title" value="${u.job_title || ''}" placeholder="e.g. Senior Developer"></div>
              <div class="form-group"><label><i class="fas fa-map-marker-alt"></i> Location</label><input type="text" name="location" value="${u.location || ''}" placeholder="e.g. Cyberjaya Office"></div>
            </div>
            <div class="form-footer" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: flex-end;">
              <button type="submit" class="btn btn-primary" id="btn-save-profile">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </div>
          </form>

          <div class="form-header align-left" style="margin-top: 40px;">
            <h2>Security</h2>
            <p class="text-muted">Change your account password.</p>
          </div>
          <button class="btn btn-outline" onclick="app.showPasswordModal()">
            <i class="fas fa-key"></i> Change Password
          </button>
        </div>
      </div>
    `;
  },

  async handleApplicationSubmit(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    const type = document.querySelector('input[name="leave-type"]:checked').value;
    const start = document.getElementById('start-date').value;
    const end = document.getElementById('end-date').value;
    const reason = document.getElementById('reason').value;

    if (new Date(end) < new Date(start)) {
      return this.showToast('Validation Error', 'End date cannot be earlier than start date.', 'danger');
    }
    const duration = this.calculateWorkDays(start, end);
    if (duration <= 0) {
      return this.showToast('Validation Error', 'Selected period contains no work days.', 'danger');
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const fd = new FormData();
    fd.append('type', type);
    fd.append('start', start);
    fd.append('end', end);
    fd.append('reason', reason);
    fd.append('duration', duration);
    this.state.uploadedFiles.forEach(f => fd.append('proof_files[]', f));

    let res = null;
    try {
      const url = this.state.editingRequestId ? API.EDIT_REQUEST : API.CREATE_REQUEST;
      if (this.state.editingRequestId) fd.append('id', this.state.editingRequestId);
      const response = await fetch(url, { method: 'POST', body: fd });
      res = await response.json();
    } catch (err) { res = null; }

    if (res && !res.error) {
      this.showToast('Success', 'Your leave request has been submitted.');
      e.target.reset();
      this.state.editingRequestId = null;
      this.state.uploadedFiles = [];
      this.renderFilePreview();
      await this.refreshAndRender();
      this.switchTab('dashboard', 'Dashboard');
    } else {
      this.showToast('Failed', res?.error || 'Could not submit request.', 'danger');
    }

    btn.disabled = false;
    btn.innerHTML = originalText;
  },

  async handleProfileUpdate(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-profile');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    const res = await this.fetchAPI(API.UPDATE_PROFILE, 'POST', data);
    if (res && !res.error) {
      this.state.currentUser = { ...this.state.currentUser, ...data };
      this.updateUI();
      this.renderProfile();
      this.showToast('Profile Updated', 'Your changes have been saved successfully.');
    } else {
      this.showToast('Update Failed', res?.error || 'Unknown error occurred.', 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = originalText;
  },

  showPasswordModal() {
    const content = `
      <form onsubmit="app.handlePasswordChange(event)">
        <div class="form-group"><label>Current Password</label><input type="password" name="current" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm" required></div>
        <div class="form-footer"><button type="submit" class="btn btn-primary">Update Password</button></div>
      </form>
    `;
    document.getElementById('modal-content').innerHTML = content;
    document.getElementById('detail-modal').classList.add('open');
  },

  async handlePasswordChange(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    if (data.new !== data.confirm) {
      return this.showToast('Validation Error', 'Passwords do not match!', 'danger');
    }

    const res = await this.fetchAPI(API.CHANGE_PASSWORD, 'POST', data);
    if (res && !res.error) {
      this.closeModal();
      this.showToast('Success', 'Password changed successfully.');
    } else {
      this.showToast('Error', res?.error || 'Failed to change password', 'danger');
    }
  },

  showToast(title, desc, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-title').innerText = title;
    document.getElementById('toast-desc').innerText = desc;
    toast.style.borderLeftColor = type === 'success' ? 'var(--success)' : 'var(--danger)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  },

  bindFileUpload() {
    const zone = document.getElementById('upload-zone');
    const input = document.getElementById('proof-file');
    if (!zone || !input) return;

    zone.addEventListener('click', () => input.click());

    input.addEventListener('change', (e) => {
      this.handleFileSelect(e.target.files);
      e.target.value = '';
    });

    zone.addEventListener('dragover', (e) => {
      e.preventDefault();
      zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', (e) => {
      if (!zone.contains(e.relatedTarget)) zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      zone.classList.remove('drag-over');
      this.handleFileSelect(e.dataTransfer.files);
    });
  },

  handleFileSelect(files) {
    const MAX = 5 * 1024 * 1024;
    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    Array.from(files).forEach(file => {
      if (!allowed.includes(file.type)) {
        return this.showToast('Unsupported File', `${file.name} — only PDF and images are allowed.`, 'danger');
      }
      if (file.size > MAX) {
        return this.showToast('File Too Large', `${file.name} exceeds the 5 MB limit.`, 'danger');
      }
      this.state.uploadedFiles.push(file);
    });
    this.renderFilePreview();
  },

  renderFilePreview() {
    const preview = document.getElementById('upload-preview');
    if (!preview) return;
    if (this.state.uploadedFiles.length === 0) { preview.innerHTML = ''; return; }
    preview.innerHTML = this.state.uploadedFiles.map((file, i) => {
      const isImage = file.type.startsWith('image/');
      const blobUrl = URL.createObjectURL(file);
      const size = file.size > 1024 * 1024
        ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
        : Math.round(file.size / 1024) + ' KB';
      return `
        <div class="preview-item">
          <a href="${blobUrl}" target="_blank" class="preview-media-link" title="Click to preview">
            ${isImage
              ? `<img class="preview-thumb" src="${blobUrl}" alt="${file.name}">`
              : `<div class="preview-icon-pdf"><i class="fas fa-file-pdf"></i></div>`}
          </a>
          <div class="preview-info">
            <a href="${blobUrl}" target="_blank" class="preview-name-link" title="Click to preview">${file.name}</a>
            <span class="preview-size">${size}</span>
          </div>
          <button class="preview-remove" type="button" onclick="app.removeFile(${i})">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `;
    }).join('');
  },

  removeFile(index) {
    this.state.uploadedFiles.splice(index, 1);
    this.renderFilePreview();
  },

  bindEvents() {
    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', e => {
        this.switchTab(item.dataset.target, item.innerText.trim());
      });
    });

    // Topbar Buttons
    document.getElementById('btn-notifications')?.addEventListener('click', () => {
      this.switchTab('notifications', 'Notifications');
    });

    document.getElementById('btn-quick-apply')?.addEventListener('click', () => {
      this.switchTab('apply-leave', 'Submit Request');
    });

    this.bindFileUpload();

    // Profile Dropdown Toggle
    const profMenu = document.getElementById('profile-menu');
    const profDrop = document.getElementById('profile-dropdown');
    profMenu?.addEventListener('click', (e) => {
      e.stopPropagation();
      profDrop?.classList.toggle('open');
    });

    document.addEventListener('click', () => profDrop?.classList.remove('open'));
  },

  async cancelRequest(id) {
    if (!confirm('Are you sure you want to cancel this leave request?')) return;
    const res = await this.fetchAPI(API.DELETE_REQUEST, 'POST', { id });
    if (res && res.ok) {
      this.showToast('Cancelled', 'Your leave request has been withdrawn.');
      await this.refreshAndRender();
    } else {
      this.showToast('Failed', res?.error || 'Could not cancel request.', 'danger');
    }
  },

  showActionModal(title, label, callback) {
    const content = `
      <div class="form-group">
        <label>${label}</label>
        <textarea id="action-comment" rows="3" placeholder="Enter your comments here..."></textarea>
      </div>
      <div class="form-footer">
        <button class="btn btn-outline" onclick="app.closeModal()">Cancel</button>
        <button class="btn btn-primary" id="btn-confirm-action">Confirm</button>
      </div>
    `;
    document.getElementById('modal-content').innerHTML = content;
    document.querySelector('.modal-header h3').innerText = title;
    document.getElementById('detail-modal').classList.add('open');

    document.getElementById('btn-confirm-action').onclick = () => {
      const comment = document.getElementById('action-comment').value;
      this.closeModal();
      callback(comment);
    };
  },

  approveRequest(id) {
    this.showActionModal('Approve Request', 'Approval Comment (Optional):', async (comment) => {
      const res = await this.fetchAPI(API.UPDATE_STATUS, 'POST', { id, status: STATUS.APPROVED, comment });
      if (res && res.ok) {
        this.showToast('Approved', 'Leave request approved.');
        await this.refreshAndRender();
      } else {
        this.showToast('Error', res?.error || 'Action failed.', 'danger');
      }
    });
  },

  rejectRequest(id) {
    this.showActionModal('Reject Request', 'Rejection Reason (Required):', async (comment) => {
      if (!comment.trim()) return this.showToast('Error', 'Rejection reason is required.', 'danger');
      const res = await this.fetchAPI(API.UPDATE_STATUS, 'POST', { id, status: STATUS.REJECTED, comment });
      if (res && res.ok) {
        this.showToast('Rejected', 'Leave request rejected.');
        await this.refreshAndRender();
      } else {
        this.showToast('Error', res?.error || 'Action failed.', 'danger');
      }
    });
  },

  async editRequest(id) {
    const req = this.state.requests.find(r => r.id == id);
    if (!req) return;
    this.state.editingRequestId = id;
    this.state.uploadedFiles = [];
    this.renderFilePreview();
    this.switchTab('apply-leave', 'Edit Leave');
    document.getElementById('start-date').value = req.start;
    document.getElementById('end-date').value = req.end;
    document.getElementById('reason').value = req.reason;
  },

  viewRequestDetails(id) {
    const req = this.state.requests.find(r => r.id == id);
    if (!req) return;

    let proofHtml = '';
    if (req.proofFiles && req.proofFiles.length > 0) {
      const items = req.proofFiles.map(f => {
        const isImg = /\.(jpg|jpeg|png|gif|webp)$/i.test(f);
        const viewUrl = `uploads/${f}`;
        const dlUrl   = `api-file-download.php?f=${encodeURIComponent(f)}`;
        return `
          <div class="proof-file-card">
            ${isImg
              ? `<a href="${viewUrl}" target="_blank" class="proof-img-link" title="Click to preview"><img src="${viewUrl}" alt="proof"></a>`
              : `<a href="${viewUrl}" target="_blank" class="proof-pdf-link" title="Click to open"><i class="fas fa-file-pdf"></i><span>${f}</span></a>`}
            <a href="${dlUrl}" class="proof-download-btn" title="Download file">
              <i class="fas fa-download"></i> Download
            </a>
          </div>
        `;
      }).join('');
      proofHtml = `
        <div class="proof-files-section">
          <span class="proof-files-label"><i class="fas fa-paperclip"></i> Attachments</span>
          <div class="proof-files-grid">${items}</div>
        </div>
      `;
    }

    const content = `
      <div class="modal-badge-row">
        <span class="badge status-${req.status.toLowerCase()}">${req.status}</span>
      </div>
      <div class="detail-row"><span>Employee</span><strong>${req.empName}</strong></div>
      <div class="detail-row"><span>Type</span><strong>${req.type}</strong></div>
      <div class="detail-row"><span>Period</span><strong>${req.start} to ${req.end}</strong></div>
      <div class="detail-row"><span>Duration</span><strong>${req.duration} Days</strong></div>
      <div class="detail-row"><span>Reason</span><strong>${req.reason || 'No reason provided'}</strong></div>
      ${proofHtml}
      ${req.comment ? `<div class="approver-comment-box">
          <span class="comment-label">Manager's Comment</span>
          <p class="comment-text">${req.comment}</p>
        </div>` : ''}
    `;
    document.getElementById('modal-content').innerHTML = content;
    document.getElementById('detail-modal').classList.add('open');
  },

  closeModal() {
    document.getElementById('detail-modal').classList.remove('open');
  }
};

document.addEventListener('DOMContentLoaded', () => app.init());
