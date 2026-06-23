/* ─────────────────────────────────────────────
   NexusLeave  —  Front-end Application Logic
   ───────────────────────────────────────────── */

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

/* ─── API endpoint map ─── */
const API = {
  GET_REQUESTS:        'api-leave-fetch.php',
  GET_ANALYTICS:       'api-stats-fetch.php',
  GET_USERS:           'api-user-fetch-all.php',
  GET_NOTIFICATIONS:   'api-notify-fetch.php',
  DELETE_USER:         'api-user-delete.php',
  CREATE_REQUEST:      'api-leave-create.php',
  EDIT_REQUEST:        'api-leave-edit.php',
  DELETE_REQUEST:      'api-leave-cancel.php',
  UPDATE_STATUS:       'api-leave-approve.php',
  MARK_READ:           'api-notify-read.php',
  DELETE_NOTIFICATION: 'api-notify-delete.php',
  UPDATE_PROFILE:      'api-user-profile-update.php',
  UPDATE_ALLOWANCE:    'api-user-allowance-update.php',
  UPDATE_JOINDATE:     'api-user-joindate-update.php',
  CHANGE_PASSWORD:     'api-user-password-change.php',
};

const STATUS = { APPROVED: 'Approved', REJECTED: 'Rejected', PENDING: 'Pending' };

/* ─────────────────────────────────────────────
   Main application object
   ───────────────────────────────────────────── */
const app = {

  /* ─── Shared state ─── */
  state: {
    role: 'employee',
    currentUser: { name: '', id: '', allowance: 21, employee_id: '' },
    requests:    [],
    editingRequestId: null,
    uploadedFiles:    [],
  },

  /* ─── Bootstrap ─── */

  async init() {
    const now   = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
    document.getElementById('start-date')?.setAttribute('min', today);
    document.getElementById('end-date')?.setAttribute('min', today);

    // When start date changes, update end-date min to match (allow same day)
    document.getElementById('start-date')?.addEventListener('change', function () {
      const endInput = document.getElementById('end-date');
      endInput.min = this.value;
      if (endInput.value && endInput.value < this.value) {
        endInput.value = this.value;
      }
    });

    if (window.NEXUS_USER) {
      this.state.currentUser.name        = window.NEXUS_USER.name;
      this.state.currentUser.employee_id = window.NEXUS_USER.id;
      this.state.role                    = window.NEXUS_USER.role;
      document.body.className            = `role-${this.state.role}`;
    }
    await this.loadFromServer();
    this.bindEvents();
    this.updateUI();
  },

  /* ─── Data helpers ─── */

  // Generic fetch wrapper — returns parsed JSON or null on error
  async fetchAPI(endpoint, method = 'GET', payload = null) {
    try {
      const opts = { method };
      if (payload) {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body    = JSON.stringify(payload);
      }
      const res = await fetch(endpoint, opts);
      return await res.json();
    } catch (e) { return null; }
  },

  async loadFromServer() {
    const data = await this.fetchAPI(API.GET_REQUESTS);
    if (!data) return;
    if (data.user) {
      this.state.currentUser = { ...this.state.currentUser, ...data.user };
      this.state.role        = data.user.role;
    }
    if (Array.isArray(data.requests)) this.state.requests = data.requests;
  },

  async refreshAndRender() {
    await this.loadFromServer();
    this.renderTables();
    this.updateUI();
  },

  getInitials(name) {
    return name ? name.split(' ').map(p => p[0]).join('').toUpperCase() : '??';
  },

  formatDate(d) {
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  },

  // Count weekdays only — weekends are not deducted from leave balance
  calculateWorkDays(start, end) {
    let count = 0;
    const cur = new Date(start);
    const last = new Date(end);
    while (cur <= last) {
      const day = cur.getDay();
      if (day !== 0 && day !== 6) count++;
      cur.setDate(cur.getDate() + 1);
    }
    return count;
  },

  // Returns a human-readable service duration string from a join_date
  getYearsOfService(joinDate) {
    if (!joinDate) return 'N/A';
    const join  = new Date(joinDate);
    const now   = new Date();
    const total = (now.getFullYear() - join.getFullYear()) * 12 + (now.getMonth() - join.getMonth());
    const y = Math.floor(total / 12);
    const m = total % 12;
    if (total <= 0)  return 'Just joined';
    if (y === 0)     return `${m} month${m !== 1 ? 's' : ''}`;
    if (m === 0)     return `${y} year${y !== 1 ? 's' : ''}`;
    return `${y} year${y !== 1 ? 's' : ''}, ${m} month${m !== 1 ? 's' : ''}`;
  },

  // Returns tier info based on Malaysia Employment Act 1955 + employment type
  // Tiers: <2yr = 8 days, 2-5yr = 12 days, >=5yr = 16 days
  // Part-Time is pro-rated at 50%
  getActTier(joinDate, employmentType) {
    const colors = { 1: '#2563eb', 2: '#d97706', 3: '#16a34a' };

    if (!joinDate) {
      return { days: 8, tier: 1, label: '< 2 years', color: colors[1],
               nextLabel: 'Set your join date to track your tier', progress: 0 };
    }

    const join  = new Date(joinDate);
    const now   = new Date();
    const total = (now.getFullYear() - join.getFullYear()) * 12 + (now.getMonth() - join.getMonth());

    let days, tier, label, nextLabel, progress;

    if (total >= 60) {
      days = 16; tier = 3; label = '≥ 5 years';
      nextLabel = 'Maximum tier reached';
      progress  = 100;
    } else if (total >= 24) {
      days = 12; tier = 2; label = '2 – 5 years';
      const left = 60 - total;
      nextLabel  = `Tier 3 (16 days) in ${Math.floor(left/12) > 0 ? Math.floor(left/12)+'y ' : ''}${left%12}m`;
      progress   = Math.round((total - 24) / 36 * 100);
    } else {
      days = 8; tier = 1; label = '< 2 years';
      const left = 24 - total;
      nextLabel  = `Tier 2 (12 days) in ${Math.floor(left/12) > 0 ? Math.floor(left/12)+'y ' : ''}${left%12}m`;
      progress   = Math.round(total / 24 * 100);
    }

    if (employmentType === 'Part-Time') days = Math.ceil(days / 2);

    return { days, tier, label, color: colors[tier], nextLabel, progress };
  },

  // Renders the Employment Overview card on the employee dashboard
  renderEmploymentCard() {
    const container = document.getElementById('emp-overview-section');
    if (!container || this.state.role !== 'employee') return;

    const u       = this.state.currentUser;
    const tier    = this.getActTier(u.join_date, u.employment_type);
    const service = this.getYearsOfService(u.join_date);
    const joinFmt = u.join_date
      ? new Date(u.join_date + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
      : 'Not set';

    // Detect if manager has manually overridden the allowance
    const managerOverride = u.allowance && (Number(u.allowance) !== tier.days);

    container.innerHTML = `
      <div class="emp-overview-card">
        <div class="emp-overview-header">
          <div class="emp-overview-title">
            <i class="fas fa-id-badge"></i>
            <span>Employment Overview</span>
          </div>
          ${this.getEtypeBadge(u.employment_type)}
        </div>
        <div class="emp-overview-stats">
          <div class="emp-ov-stat">
            <span class="emp-ov-label"><i class="fas fa-calendar-day"></i> Join Date</span>
            <strong class="emp-ov-value">${joinFmt}</strong>
          </div>
          <div class="emp-ov-stat">
            <span class="emp-ov-label"><i class="fas fa-history"></i> Years of Service</span>
            <strong class="emp-ov-value">${service}</strong>
          </div>
          <div class="emp-ov-stat">
            <span class="emp-ov-label"><i class="fas fa-umbrella-beach"></i> Act Entitlement</span>
            <strong class="emp-ov-value" style="color:${tier.color};">${tier.days} days / year</strong>
          </div>
        </div>
        <div class="emp-tier-block">
          <div class="emp-tier-header">
            <div>
              <span class="emp-tier-label">Tier ${tier.tier}</span>
              <span class="emp-tier-desc">${tier.label} &mdash; Malaysia Employment Act 1955</span>
            </div>
            <span class="emp-tier-next">${tier.nextLabel}</span>
          </div>
          <div class="emp-tier-bar">
            <div class="emp-tier-fill" style="width:${tier.progress}%; background:${tier.color};"></div>
          </div>
          ${managerOverride
            ? `<p class="emp-tier-note"><i class="fas fa-info-circle"></i> Your allowance has been set to <strong>${u.allowance} days</strong> by your manager.</p>`
            : ''}
        </div>
      </div>`;
  },

  /* ─── Rendering ─── */

  renderTables() {
    const myId  = this.state.currentUser.employee_id;
    const today = new Date().toISOString().split('T')[0];

    // Employee dashboard — latest 5 requests
    const dash = document.getElementById('dashboard-requests-list');
    if (dash && this.state.role === 'employee') {
      const list = this.state.requests.filter(r => r.empId === myId).slice(0, 5);
      dash.innerHTML = list.length === 0
        ? `<tr><td colspan="5" style="text-align:center; padding:20px;">No recent activity.</td></tr>`
        : list.map(r => `
            <tr onclick="app.viewRequestDetails(${r.id})" style="cursor:pointer">
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">${this.getInitials(r.empName)}</div>
                  <div class="employee-meta">
                    <span class="employee-name">${r.empName}</span>
                    <span class="employee-id">${r.empId}</span>
                  </div>
                </div>
              </td>
              <td><strong>${r.type}</strong></td>
              <td>${this.formatDate(r.start)}</td>
              <td>${r.duration}d</td>
              <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
            </tr>`).join('');
    }

    // Manager dashboard — pending count, away count, recent activity
    if (this.state.role === 'manager') {
      const pending = this.state.requests.filter(r => r.status === 'Pending');
      const away    = this.state.requests.filter(r => r.status === 'Approved' && today >= r.start && today <= r.end);
      const recent  = [...this.state.requests].sort((a, b) => b.id - a.id);

      const elPending = document.getElementById('stat-mgr-pending');
      const elAway    = document.getElementById('stat-mgr-away');
      if (elPending) elPending.innerText = pending.length;
      if (elAway)    elAway.innerText    = away.length;

      const awayTable = document.getElementById('mgr-dash-away');
      if (awayTable) {
        awayTable.innerHTML = away.length === 0
          ? `<tr><td colspan="3" style="text-align:center; padding:30px; color:var(--success); font-weight:500;"><i class="fas fa-check-circle"></i> Everyone is in office today!</td></tr>`
          : away.slice(0, 5).map(r => `
              <tr>
                <td><div class="employee-cell"><span class="employee-name">${r.empName}</span></div></td>
                <td>${r.type}</td>
                <td>Until ${this.formatDate(r.end)}</td>
              </tr>`).join('');
      }

      const activityTable = document.getElementById('mgr-dashboard-activity');
      if (activityTable) {
        activityTable.innerHTML = recent.length === 0
          ? `<tr><td colspan="5" style="text-align:center; padding:20px;">No employee activity found.</td></tr>`
          : recent.slice(0, 5).map(r => `
              <tr onclick="app.viewRequestDetails(${r.id})" style="cursor:pointer">
                <td>
                  <div class="employee-cell">
                    <div class="employee-avatar">${this.getInitials(r.empName)}</div>
                    <div class="employee-meta">
                      <span class="employee-name">${r.empName}</span>
                      <span class="employee-id">${r.empId}</span>
                    </div>
                  </div>
                </td>
                <td><strong>${r.type}</strong></td>
                <td>${this.formatDate(r.start)}</td>
                <td>${r.duration}d</td>
                <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
              </tr>`).join('');
      }
    }

    // Leave Status — only pending requests (awaiting manager action)
    const statusTable = document.getElementById('status-table-body');
    if (statusTable) {
      const list = this.state.requests.filter(r => r.empId === myId && r.status === 'Pending');
      statusTable.innerHTML = list.length === 0
        ? `<tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
             <i class="fas fa-check-circle" style="display:block; font-size:2rem; margin-bottom:10px; color:var(--success);"></i>
             No active requests. All caught up!
           </td></tr>`
        : list.map(r => `
            <tr id="row-${r.id}">
              <td><button class="expand-toggle" onclick="app.toggleRow(${r.id})">+</button></td>
              <td><strong>${r.type}</strong></td>
              <td>${this.formatDate(r.start)} – ${this.formatDate(r.end)}</td>
              <td>${r.duration}d</td>
              <td><span class="badge status-pending">Waiting Manager</span></td>
              <td>
                <div class="action-group" style="gap:5px;">
                  <button class="btn btn-sm" onclick="app.editRequest(${r.id})"><i class="fas fa-edit"></i> Edit</button>
                  <button class="btn btn-sm btn-reject" onclick="app.cancelRequest(${r.id})"><i class="fas fa-times"></i> Cancel</button>
                </div>
              </td>
            </tr>`).join('');
    }

    // Leave History — approved and rejected requests
    const historyTable = document.getElementById('history-table-body');
    if (historyTable) {
      const list = this.state.requests.filter(r => r.empId === myId && r.status !== 'Pending');
      historyTable.innerHTML = list.length === 0
        ? `<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No leave history yet.</td></tr>`
        : list.map(r => `
            <tr id="row-hist-${r.id}">
              <td><button class="expand-toggle" onclick="app.toggleRow(${r.id}, 'hist')">+</button></td>
              <td>${r.date}</td>
              <td><strong>${r.type}</strong></td>
              <td>${this.formatDate(r.start)} – ${this.formatDate(r.end)}</td>
              <td>${r.duration}d</td>
              <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
            </tr>`).join('');
    }

    // Manager Approvals — all pending requests across all employees
    const mgrTable = document.getElementById('manager-table-body');
    if (mgrTable) {
      const list = this.state.requests.filter(r => r.status === 'Pending');
      mgrTable.innerHTML = list.length === 0
        ? `<tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No pending requests.</td></tr>`
        : list.map(r => `
            <tr>
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
                  <button class="btn btn-sm btn-outline" onclick="app.viewRequestDetails(${r.id})" title="View Details"><i class="fas fa-eye"></i></button>
                  <button class="btn btn-sm btn-approve" onclick="app.approveRequest(${r.id})">Approve</button>
                  <button class="btn btn-sm btn-reject"  onclick="app.rejectRequest(${r.id})">Reject</button>
                </div>
              </td>
            </tr>`).join('');
    }
  },

  toggleRow(id, prefix = '') {
    const rowId = prefix ? `row-${prefix}-${id}` : `row-${id}`;
    const row   = document.getElementById(rowId);
    const btn   = row.querySelector('.expand-toggle');
    const next  = row.nextElementSibling;

    if (next && next.classList.contains('expanded-row')) {
      next.remove();
      btn.innerText = '+';
      return;
    }

    const req = this.state.requests.find(r => r.id == id);
    row.insertAdjacentHTML('afterend', `
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
      </tr>`);
    btn.innerText = '-';
  },

  async switchTab(id, title) {
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    document.getElementById(id)?.classList.add('active');

    document.querySelectorAll('.nav-item').forEach(n => {
      n.classList.toggle('active', n.getAttribute('data-target') === id);
    });

    const heading = document.getElementById('page-heading');
    if (heading) heading.innerText = title;

    if (id === 'profile')       this.renderProfile();
    if (id === 'team-overview') this.loadUsers();
    if (id === 'notifications') this.loadNotifications();
    if (id === 'reports')       this.loadReport();

    this.renderTables();
  },

  /* ─── Manager: Reports ─── */

  async loadReport() {
    if (this.state.role !== 'manager') return;

    const status = document.getElementById('rpt-filter-status')?.value || '';
    const type   = document.getElementById('rpt-filter-type')?.value   || '';
    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (type)   params.set('type', type);

    const data = await this.fetchAPI('api-report-fetch.php?' + params.toString());
    if (!data) return;

    // ── Summary stats ─────────────────────────────────────
    const s = data.summary;
    document.getElementById('rpt-total').textContent    = s.total    || 0;
    document.getElementById('rpt-approved').textContent = s.approved || 0;
    document.getElementById('rpt-pending').textContent  = s.pending  || 0;
    document.getElementById('rpt-rejected').textContent = s.rejected || 0;

    // ── Bar chart helper ──────────────────────────────────
    function barChart(containerId, rows, labelKey, valueKey, colorMap) {
      const el = document.getElementById(containerId);
      if (!el) return;
      const max = Math.max(...rows.map(r => Number(r[valueKey]) || 0), 1);
      const colors = { Annual:'#4f46e5', Sick:'#22c55e', Unpaid:'#f59e0b', default:'#6366f1' };
      el.innerHTML = rows.map(r => {
        const val  = Number(r[valueKey]) || 0;
        const pct  = Math.round((val / max) * 100);
        const color = colorMap?.[r[labelKey]] || colors[r[labelKey]] || colors.default;
        return `<div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:0.85rem;font-weight:500;color:var(--text-main);">${r[labelKey]}</span>
            <span style="font-size:0.85rem;color:var(--text-sub);">${val} days</span>
          </div>
          <div style="background:#f1f5f9;border-radius:6px;height:10px;overflow:hidden;">
            <div style="width:${pct}%;background:${color};height:100%;border-radius:6px;transition:width 0.4s;"></div>
          </div>
        </div>`;
      }).join('');
    }

    barChart('rpt-type-bars', data.byType, 'leave_type', 'days');
    barChart('rpt-dept-bars', data.byDept, 'department', 'days', {});

    // ── Records table ─────────────────────────────────────
    const statusBadge = s => {
      const m = {Approved:'badge-approved', Pending:'badge-pending', Rejected:'badge-rejected'};
      return `<span class="status-badge ${m[s]||''}">${s}</span>`;
    };
    const tbody = document.getElementById('rpt-records-body');
    if (!tbody) return;
    if (!data.records?.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No records found.</td></tr>';
      return;
    }
    tbody.innerHTML = data.records.map(r => {
      const start = r.start_date ? new Date(r.start_date+'T00:00:00').toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—';
      const end   = r.end_date   ? new Date(r.end_date  +'T00:00:00').toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—';
      const applied = r.created_at ? new Date(r.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}) : '—';
      return `<tr>
        <td><strong>${r.name}</strong><br><span style="font-size:0.78rem;color:var(--text-sub);">${r.employee_id}</span></td>
        <td>${r.department || '—'}</td>
        <td>${r.leave_type}</td>
        <td style="font-size:0.85rem;">${start} – ${end}</td>
        <td>${r.duration} day${r.duration!=1?'s':''}</td>
        <td>${statusBadge(r.status)}</td>
        <td style="font-size:0.85rem;color:var(--text-sub);">${applied}</td>
      </tr>`;
    }).join('');
  },

  /* ─── Manager: User Management ─── */

  async loadUsers() {
    if (this.state.role !== 'manager') return;
    const data  = await this.fetchAPI(API.GET_USERS);
    const tbody = document.getElementById('user-management-table-body');
    if (!tbody || !data || data.error) return;

    const list = data.users || [];
    tbody.innerHTML = list.length === 0
      ? `<tr><td colspan="7" style="text-align:center;">No employees found.</td></tr>`
      : list.map(u => `
          <tr>
            <td>
              <div class="employee-cell clickable" onclick="app.viewEmployeeProfile(${u.id})">
                <div class="employee-avatar">${this.getInitials(u.name)}</div>
                <div class="employee-meta"><span class="employee-name">${u.name}</span></div>
              </div>
            </td>
            <td>${u.employee_id}</td>
            <td>${u.role}</td>
            <td>${u.department || '—'}</td>
            <td>${this.getEtypeBadge(u.employment_type)}</td>
            <td style="font-size:0.82rem; color:var(--text-sub);">${this.getYearsOfService(u.join_date)}</td>
            <td>
              <div class="action-group" style="gap:5px;">
                <button class="btn btn-sm btn-outline" onclick="app.viewEmployeeProfile(${u.id})" title="View Profile">
                  <i class="fas fa-id-card"></i>
                </button>
                ${u.id != this.state.currentUser.id
                  ? `<button class="btn btn-sm btn-reject" onclick="app.deleteUser(${u.id})" title="Delete"><i class="fas fa-trash"></i></button>`
                  : `<span class="badge status-approved">You</span>`}
              </div>
            </td>
          </tr>`).join('');
  },

  async viewEmployeeProfile(userId) {
    document.getElementById('modal-content').innerHTML = `
      <div class="modal-loader">
        <i class="fas fa-circle-notch fa-spin"></i>
        <p>Fetching employee records...</p>
      </div>`;
    document.querySelector('.modal-header h3').innerText = 'Employee Profile';
    document.getElementById('detail-modal').classList.add('open');

    const data = await this.fetchAPI(`${API.GET_REQUESTS}?user_id=${userId}`);
    if (!data || !data.user) {
      const msg = data?.error || 'Could not load employee data. Please try again.';
      document.getElementById('modal-content').innerHTML =
        `<p class="modal-error-msg">${msg}</p>`;
      return;
    }

    const u      = data.user;
    const total  = u.allowance || 21;
    const taken  = (data.requests || [])
      .filter(r => r.status === 'Approved')
      .reduce((sum, r) => sum + Number(r.duration), 0);
    const balance = total - taken;

    const infoRow = (icon, label, value) => `
      <div class="emp-info-row">
        <i class="fas ${icon}"></i>
        <div>
          <span class="emp-info-label">${label}</span>
          <strong>${value}</strong>
        </div>
      </div>`;

    document.getElementById('modal-content').innerHTML = `
      <div class="emp-profile-layout">
        <div class="emp-profile-sidebar">
          <div class="emp-profile-avatar-wrap">
            <div class="profile-avatar-large emp-avatar-lg">${this.getInitials(u.name)}</div>
            <h2>${u.name}</h2>
            <span class="job-badge">${u.role.toUpperCase()}</span>
          </div>
          <div class="emp-info-list">
            ${infoRow('fa-id-card',        'Employee ID',      u.employee_id)}
            ${infoRow('fa-envelope',       'Email',            u.email)}
            ${infoRow('fa-phone',          'Phone',            u.phone       || 'N/A')}
            ${infoRow('fa-building',       'Department',       u.department  || 'N/A')}
            ${infoRow('fa-briefcase',      'Job Title',        u.job_title   || 'N/A')}
            ${infoRow('fa-map-marker-alt', 'Location',         u.location    || 'N/A')}
            <div class="emp-info-row">
              <i class="fas fa-user-tag"></i>
              <div>
                <span class="emp-info-label">Employment Type</span>
                <strong>${this.getEtypeBadge(u.employment_type)}</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="emp-profile-main">
          <div class="emp-stats-row">
            <div class="emp-stat-card">
              <span class="emp-stat-label">Allowance</span>
              <strong>${total}</strong>
              <div id="allowance-edit-area" style="margin-top:6px;">
                <button class="btn btn-sm btn-outline" style="font-size:0.72rem; padding:3px 8px;" onclick="app.editAllowance(${u.id}, ${total})">
                  <i class="fas fa-edit"></i> Edit
                </button>
              </div>
            </div>
            <div class="emp-stat-card emp-stat-taken"><span class="emp-stat-label">Taken</span><strong>${taken}</strong></div>
            <div class="emp-stat-card emp-stat-balance"><span class="emp-stat-label">Balance</span><strong>${balance}</strong></div>
          </div>
          ${(() => {
            const tier    = this.getActTier(u.join_date, u.employment_type);
            const service = this.getYearsOfService(u.join_date);
            const joinFmt = u.join_date
              ? new Date(u.join_date + 'T00:00:00').toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' })
              : 'Not set';
            return `
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:16px;">
              <div style="background:#f8fafc; border-radius:10px; padding:12px 14px;">
                <span style="font-size:0.72rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:4px;">Join Date</span>
                <div id="joindate-edit-area" style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                  <strong style="font-size:0.9rem;">${joinFmt}</strong>
                  <button class="btn btn-sm btn-outline" style="font-size:0.68rem; padding:2px 6px;" onclick="app.editJoinDate(${u.id}, '${u.join_date || ''}')" title="Edit join date">
                    <i class="fas fa-edit"></i>
                  </button>
                </div>
              </div>
              <div style="background:#f8fafc; border-radius:10px; padding:12px 14px;">
                <span style="font-size:0.72rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:4px;">Years of Service</span>
                <strong style="font-size:0.9rem;">${service}</strong>
              </div>
              <div style="background:#f8fafc; border-radius:10px; padding:12px 14px;">
                <span style="font-size:0.72rem; font-weight:700; color:var(--text-sub); text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:4px;">Act Entitlement</span>
                <strong style="font-size:0.9rem; color:${tier.color};">${tier.days} days (Tier ${tier.tier})</strong>
              </div>
            </div>`;
          })()}

          <div class="table-card emp-history-table">
            <div class="card-header">
              <i class="fas fa-history" style="color:var(--primary);"></i>
              <h4>Leave History</h4>
            </div>
            <div class="table-container" style="max-height:400px; overflow-y:auto;">
              <table class="data-table">
                <thead><tr><th>Type</th><th>Start</th><th>Days</th><th>Status</th></tr></thead>
                <tbody>
                  ${data.requests.length === 0
                    ? `<tr><td colspan="4" style="text-align:center; padding:40px; color:var(--text-muted);">No records found.</td></tr>`
                    : data.requests.map(r => `
                        <tr>
                          <td><strong>${r.type}</strong></td>
                          <td>${this.formatDate(r.start)}</td>
                          <td>${r.duration}d</td>
                          <td><span class="badge status-${r.status.toLowerCase()}">${r.status}</span></td>
                        </tr>`).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>`;
  },

  // Show inline number input for manager to override employee allowance
  editAllowance(userId, current) {
    const area = document.getElementById('allowance-edit-area');
    if (!area) return;
    area.innerHTML = `
      <div class="allowance-edit-row">
        <input type="number" id="new-allowance-input" value="${current}" min="0" max="365">
        <span style="font-size:0.78rem; color:var(--text-sub);">days</span>
        <button class="btn btn-sm btn-primary" onclick="app.saveAllowance(${userId})">Save</button>
        <button class="btn btn-sm btn-outline" onclick="app.viewEmployeeProfile(${userId})">Cancel</button>
      </div>`;
  },

  async saveAllowance(userId) {
    const input = document.getElementById('new-allowance-input');
    const days  = parseInt(input?.value ?? '');
    if (isNaN(days) || days < 0 || days > 365) {
      return this.showToast('Invalid', 'Enter a value between 0 and 365.', 'danger');
    }
    const res = await this.fetchAPI(API.UPDATE_ALLOWANCE, 'POST', { user_id: userId, allowance: days });
    if (res && res.ok) {
      this.showToast('Updated', `Allowance set to ${days} days.`);
      await this.loadFromServer();
      this.viewEmployeeProfile(userId);
    } else {
      this.showToast('Error', res?.error || 'Failed to update allowance.', 'danger');
    }
  },

  // Show inline date input for manager to set an employee's join date
  editJoinDate(userId, current) {
    const area = document.getElementById('joindate-edit-area');
    if (!area) return;
    area.innerHTML = `
      <input type="date" id="new-joindate-input" value="${current}" max="${new Date().toISOString().split('T')[0]}" style="font-size:0.82rem; width:100%;">
      <button class="btn btn-sm btn-primary" onclick="app.saveJoinDate(${userId})">Save</button>
      <button class="btn btn-sm btn-outline" onclick="app.viewEmployeeProfile(${userId})">Cancel</button>`;
  },

  async saveJoinDate(userId) {
    const input = document.getElementById('new-joindate-input');
    const joinDate = input?.value ?? '';
    if (!joinDate) {
      return this.showToast('Invalid', 'Please pick a join date.', 'danger');
    }
    const res = await this.fetchAPI(API.UPDATE_JOINDATE, 'POST', { user_id: userId, join_date: joinDate });
    if (res && res.ok) {
      this.showToast('Updated', `Join date set. Allowance recalculated to ${res.allowance} days.`);
      await this.loadFromServer();
      this.viewEmployeeProfile(userId);
    } else {
      this.showToast('Error', res?.error || 'Failed to update join date.', 'danger');
    }
  },

  async deleteUser(id) {
    if (!confirm('Are you sure you want to permanently delete this employee? All their leave records will be removed.')) return;
    const res = await this.fetchAPI(API.DELETE_USER, 'POST', { id });
    if (res && !res.error) {
      this.showToast('Deleted', 'Employee account has been removed.');
      this.loadUsers();
    } else {
      this.showToast('Error', res?.error || 'Failed to delete user.', 'danger');
    }
  },

  /* ─── Notifications ─── */

  async loadNotifications() {
    const data = await this.fetchAPI(API.GET_NOTIFICATIONS);
    const list = document.getElementById('notification-list');
    if (!list || !data || data.error) return;

    const notifs = data.notifications || [];

    if (notifs.length === 0) {
      list.innerHTML = `<div class="notif-empty">No notifications yet.</div>`;
      document.querySelector('.dot-indicator').style.display = 'none';
      return;
    }

    const unread = notifs.some(n => n.is_read == 0);
    document.querySelector('.dot-indicator').style.display = unread ? 'block' : 'none';

    list.innerHTML = notifs.map(n => {
      const isUnread = n.is_read == 0;
      const icon  = n.type === 'request' ? 'fa-paper-plane' : 'fa-bell';
      const title = n.title || (n.type === 'request' ? 'Leave Request' : 'Status Update');
      return `
        <div class="notif-item ${isUnread ? 'notif-unread' : 'notif-read'}" data-id="${n.id}">
          <div class="notif-icon-col">
            <div class="notif-icon-wrap ${isUnread ? 'notif-icon-unread' : ''}">
              <i class="fas ${icon}"></i>
            </div>
          </div>
          <div class="notif-body">
            <div class="notif-title">${title}</div>
            <div class="notif-message">${n.message}</div>
            <div class="notif-date">${this.formatDate(n.created_at)}</div>
          </div>
          <div class="notif-actions">
            ${isUnread
              ? `<button class="notif-btn-read" onclick="app.markNotificationRead(${n.id})" title="Mark as read"><i class="fas fa-check"></i></button>`
              : `<span class="notif-read-badge"><i class="fas fa-check-double"></i></span>`}
            <button class="notif-btn-dismiss" onclick="app.dismissNotification(${n.id})" title="Dismiss"><i class="fas fa-times"></i></button>
          </div>
        </div>`;
    }).join('');
  },

  async markNotificationRead(id) {
    const item = document.querySelector(`.notif-item[data-id="${id}"]`);
    if (item) {
      item.classList.replace('notif-unread', 'notif-read');
      item.querySelector('.notif-icon-wrap')?.classList.remove('notif-icon-unread');
      const readBtn = item.querySelector('.notif-btn-read');
      if (readBtn) readBtn.outerHTML = `<span class="notif-read-badge"><i class="fas fa-check-double"></i></span>`;
    }
    await this.fetchAPI(API.MARK_READ, 'POST', { id });
    const stillUnread = document.querySelectorAll('.notif-unread').length > 0;
    document.querySelector('.dot-indicator').style.display = stillUnread ? 'block' : 'none';
  },

  async dismissNotification(id) {
    const item = document.querySelector(`.notif-item[data-id="${id}"]`);
    if (item) {
      item.classList.add('notif-dismissing');
      await new Promise(r => setTimeout(r, 300));
      item.remove();
    }
    await this.fetchAPI(API.DELETE_NOTIFICATION, 'POST', { id });
    const list = document.getElementById('notification-list');
    if (list && list.querySelectorAll('.notif-item').length === 0) {
      list.innerHTML = `<div class="notif-empty">No notifications yet.</div>`;
      document.querySelector('.dot-indicator').style.display = 'none';
    }
  },

  /* ─── Dashboard Stats ─── */

  updateUI() {
    const u       = this.state.currentUser;
    const total   = u.allowance || 21;
    const taken   = this.state.requests
      .filter(r => r.empId === u.employee_id && r.status === 'Approved')
      .reduce((sum, r) => sum + Number(r.duration), 0);
    const balance = total - taken;

    document.getElementById('user-name')   .innerText = u.name;
    document.getElementById('profile-name').innerText = u.name;
    document.getElementById('profile-avatar').innerText = this.getInitials(u.name);
    document.getElementById('user-avatar')  .innerText = this.getInitials(u.name);

    const roleLabel  = (u.role || 'employee').charAt(0).toUpperCase() + (u.role || 'employee').slice(1);
    const elRole     = document.getElementById('user-display-role');
    const elTotal    = document.getElementById('stat-total');
    const elTaken    = document.getElementById('stat-taken');
    const elBalance  = document.getElementById('stat-balance');
    if (elRole)    elRole.innerText    = roleLabel;
    if (elTotal)   elTotal.innerText   = `${total} Days`;
    if (elTaken)   elTaken.innerText   = `${taken} Days`;
    if (elBalance) elBalance.innerText = `${balance} Days`;

    const takenPct   = (taken   / total) * 100;
    const balancePct = (balance / total) * 100;
    document.getElementById('progress-taken')  ?.style.setProperty('width', `${takenPct}%`);
    document.getElementById('progress-balance')?.style.setProperty('width', `${balancePct}%`);

    this.renderTables();
    this.renderEmploymentCard();
  },

  /* ─── My Profile Page ─── */

  // Returns a styled badge for the given employment type string
  getEtypeBadge(etype) {
    const map = {
      'Permanent': { css: 'etype-permanent', icon: 'fa-user-tie',      label: 'Permanent' },
      'Contract':  { css: 'etype-contract',  icon: 'fa-file-contract', label: 'Contract'  },
      'Part-Time': { css: 'etype-parttime',  icon: 'fa-clock',         label: 'Part-Time' },
    };
    const cfg = map[etype] || { css: 'etype-permanent', icon: 'fa-user-tie', label: etype || 'Permanent' };
    return `<span class="etype-badge ${cfg.css}"><i class="fas ${cfg.icon}"></i>${cfg.label}</span>`;
  },

  // Returns a horizontal card for employment type (used in profile view only)
  getEtypeCard(etype) {
    const map = {
      'Permanent': { icon: 'fa-user-tie' },
      'Contract':  { icon: 'fa-file-contract' },
      'Part-Time': { icon: 'fa-clock' },
    };
    const cfg = map[etype] || { icon: 'fa-user-tie' };
    return `<div class="etype-profile-card">
      <div class="etype-profile-icon"><i class="fas ${cfg.icon}"></i></div>
      <div class="etype-profile-text"><span>Employment Type</span><strong>${etype || 'Permanent'}</strong></div>
    </div>`;
  },

  renderProfile() {
    const u         = this.state.currentUser;
    const container = document.getElementById('profile-content');
    if (!container) return;

    const etypes   = ['Permanent', 'Contract', 'Part-Time'];
    const etOptions = etypes.map(t =>
      `<option value="${t}" ${(u.employment_type || 'Permanent') === t ? 'selected' : ''}>${t}</option>`
    ).join('');

    container.innerHTML = `
      <div class="profile-stack" style="display:flex; flex-direction:column; gap:30px; max-width:900px; margin:0 auto;">

        <div class="profile-card">
          <div class="profile-header-bg"></div>
          <div class="profile-identity-wrap">
            <div class="profile-avatar-large">${this.getInitials(u.name)}</div>
            <div class="profile-identity-info">
              <h2 class="profile-display-name">${u.name}</h2>
              <div class="profile-badge-row">
                <span class="profile-info-pill"><i class="fas fa-user-tie"></i> Title: ${u.role.charAt(0).toUpperCase() + u.role.slice(1)}</span>
                <span class="profile-info-pill"><i class="fas fa-user-tag"></i> Employment: ${u.employment_type || 'Permanent'}</span>
              </div>
            </div>
          </div>
          <div class="profile-info-sections">
            <div class="info-block">
              <span class="section-label">Employment & Contact Details</span>
              <div class="info-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="info-item"><i class="fas fa-user"></i>          <div class="info-content"><span>Full Name</span>        <strong>${u.name}</strong></div></div>
                <div class="info-item"><i class="fas fa-envelope"></i>      <div class="info-content"><span>Work Email</span>       <strong>${u.email}</strong></div></div>
                <div class="info-item"><i class="fas fa-id-card"></i>       <div class="info-content"><span>Employee ID</span>      <strong>${u.employee_id}</strong></div></div>
                <div class="info-item"><i class="fas fa-building"></i>      <div class="info-content"><span>Department</span>      <strong>${u.department || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-phone"></i>         <div class="info-content"><span>Phone Number</span>    <strong>${u.phone      || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-briefcase"></i>     <div class="info-content"><span>Job Title</span>      <strong>${u.job_title  || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-map-marker-alt"></i><div class="info-content"><span>Location</span>      <strong>${u.location   || 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-calendar-day"></i>  <div class="info-content"><span>Join Date</span>      <strong>${u.join_date ? new Date(u.join_date + 'T00:00:00').toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : 'N/A'}</strong></div></div>
                <div class="info-item"><i class="fas fa-history"></i>       <div class="info-content"><span>Years of Service</span><strong>${this.getYearsOfService(u.join_date)}</strong></div></div>
                <div class="info-item"><i class="fas fa-user-tag"></i>      <div class="info-content"><span>Employment Type</span> <strong>${u.employment_type || 'N/A'}</strong></div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="profile-edit-card">
          <div class="form-header align-left">
            <h2>Manage Profile</h2>
            <p class="text-muted">Update your personal and employment information.</p>
          </div>
          <form id="profile-edit-form" onsubmit="app.handleProfileUpdate(event)">
            <div class="edit-form-grid">
              <div class="form-group"><label><i class="fas fa-user"></i> Full Name</label>       <input type="text"  name="name"        value="${u.name}"           required></div>
              <div class="form-group"><label><i class="fas fa-envelope"></i> Work Email</label>  <input type="email" name="email"       value="${u.email}"          required></div>
              <div class="form-group"><label><i class="fas fa-id-card"></i> Employee ID</label>  <input type="text"  name="employee_id" value="${u.employee_id}"    required></div>
              <div class="form-group"><label><i class="fas fa-building"></i> Department</label>  <input type="text"  name="department"  value="${u.department || ''}" placeholder="e.g. IT"></div>
              <div class="form-group"><label><i class="fas fa-phone"></i> Phone Number</label>   <input type="text"  name="phone"       value="${u.phone      || ''}" placeholder="+60 12-345 6789"></div>
              <div class="form-group"><label><i class="fas fa-briefcase"></i> Job Title</label>  <input type="text"  name="job_title"   value="${u.job_title  || ''}" placeholder="e.g. Senior Developer"></div>
              <div class="form-group"><label><i class="fas fa-map-marker-alt"></i> Location</label><input type="text" name="location"  value="${u.location   || ''}" placeholder="e.g. Cyberjaya Office"></div>
              <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Employment Type</label>
                <div class="emp-type-select-wrap">
                  <select name="employment_type">${etOptions}</select>
                </div>
              </div>
              ${this.state.role === 'manager' || this.state.role === 'admin' ? `
              <div class="form-group">
                <label><i class="fas fa-calendar-day"></i> Join Date</label>
                <input type="date" name="join_date" value="${u.join_date || ''}" max="${new Date().toISOString().split('T')[0]}">
              </div>` : `
              <div class="form-group">
                <label><i class="fas fa-calendar-day"></i> Join Date</label>
                <input type="text" value="${u.join_date ? new Date(u.join_date + 'T00:00:00').toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' }) : 'Not set'}" disabled style="background:var(--bg-section); color:var(--text-sub); cursor:not-allowed;">
                <p style="font-size:0.78rem; color:var(--text-sub); margin-top:4px;"><i class="fas fa-lock" style="font-size:0.7rem;"></i> Managed by HR/Manager</p>
              </div>`}
            </div>
            <div class="form-footer" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end;">
              <button type="submit" class="btn btn-primary" id="btn-save-profile">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </div>
          </form>

          <div class="form-header align-left" style="margin-top:40px;">
            <h2>Security</h2>
            <p class="text-muted">Change your account password.</p>
          </div>
          <button class="btn btn-outline" onclick="app.showPasswordModal()">
            <i class="fas fa-key"></i> Change Password
          </button>
        </div>

      </div>`;
  },

  /* ─── Leave Application ─── */

  async handleApplicationSubmit(e) {
    e.preventDefault();
    const btn          = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;

    const type   = document.querySelector('input[name="leave-type"]:checked').value;
    const start  = document.getElementById('start-date').value;
    const end    = document.getElementById('end-date').value;
    const reason = document.getElementById('reason').value;

    if (new Date(end) < new Date(start)) {
      return this.showToast('Validation Error', 'End date cannot be earlier than start date.', 'danger');
    }
    const duration = this.calculateWorkDays(start, end);
    if (duration <= 0) {
      return this.showToast('Validation Error', 'Selected period contains no work days.', 'danger');
    }

    btn.disabled  = true;
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
      res = await (await fetch(url, { method: 'POST', body: fd })).json();
    } catch { res = null; }

    if (res && !res.error) {
      this.showToast('Success', 'Your leave request has been submitted.');
      e.target.reset();
      this.state.editingRequestId = null;
      this.state.uploadedFiles    = [];
      this.renderFilePreview();
      await this.refreshAndRender();
      this.switchTab('dashboard', 'Dashboard');
    } else {
      this.showToast('Failed', res?.error || 'Could not submit request.', 'danger');
    }

    btn.disabled  = false;
    btn.innerHTML = originalText;
  },

  async editRequest(id) {
    const req = this.state.requests.find(r => r.id == id);
    if (!req) return;
    this.state.editingRequestId = id;
    this.state.uploadedFiles    = [];
    this.renderFilePreview();
    this.switchTab('apply-leave', 'Edit Leave');
    document.getElementById('start-date').value = req.start;
    document.getElementById('end-date').value   = req.end;
    document.getElementById('reason').value     = req.reason;
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

  /* ─── Manager Approval Actions ─── */

  // Opens a modal with a comment box, then calls the callback with the comment
  showActionModal(title, label, callback) {
    document.getElementById('modal-content').innerHTML = `
      <div class="form-group">
        <label>${label}</label>
        <textarea id="action-comment" rows="3" placeholder="Enter your comments here..."></textarea>
      </div>
      <div class="form-footer">
        <button class="btn btn-outline" onclick="app.closeModal()">Cancel</button>
        <button class="btn btn-primary" id="btn-confirm-action">Confirm</button>
      </div>`;
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

  /* ─── Request Details Modal ─── */

  viewRequestDetails(id) {
    const req = this.state.requests.find(r => r.id == id);
    if (!req) return;

    let proofHtml = '';
    if (req.proofFiles && req.proofFiles.length > 0) {
      const items = req.proofFiles.map(f => {
        const isImg  = /\.(jpg|jpeg|png|gif|webp)$/i.test(f);
        const viewUrl = `uploads/${f}`;
        const dlUrl   = `api-file-download.php?f=${encodeURIComponent(f)}`;
        return `
          <div class="proof-file-card">
            ${isImg
              ? `<a href="${viewUrl}" target="_blank" class="proof-img-link"><img src="${viewUrl}" alt="proof"></a>`
              : `<a href="${viewUrl}" target="_blank" class="proof-pdf-link"><i class="fas fa-file-pdf"></i><span>${f}</span></a>`}
            <a href="${dlUrl}" class="proof-download-btn"><i class="fas fa-download"></i> Download</a>
          </div>`;
      }).join('');
      proofHtml = `
        <div class="proof-files-section">
          <span class="proof-files-label"><i class="fas fa-paperclip"></i> Attachments</span>
          <div class="proof-files-grid">${items}</div>
        </div>`;
    }

    document.getElementById('modal-content').innerHTML = `
      <div class="modal-badge-row">
        <span class="badge status-${req.status.toLowerCase()}">${req.status}</span>
      </div>
      <div class="detail-row"><span>Employee</span><strong>${req.empName}</strong></div>
      <div class="detail-row"><span>Type</span>    <strong>${req.type}</strong></div>
      <div class="detail-row"><span>Period</span>  <strong>${req.start} to ${req.end}</strong></div>
      <div class="detail-row"><span>Duration</span><strong>${req.duration} Days</strong></div>
      <div class="detail-row"><span>Reason</span>  <strong>${req.reason || 'No reason provided'}</strong></div>
      ${proofHtml}
      ${req.comment ? `
        <div class="approver-comment-box">
          <span class="comment-label">Manager's Comment</span>
          <p class="comment-text">${req.comment}</p>
        </div>` : ''}`;
    document.getElementById('detail-modal').classList.add('open');
  },

  closeModal() {
    document.getElementById('detail-modal').classList.remove('open');
  },

  /* ─── Profile Edit & Password ─── */

  async handleProfileUpdate(e) {
    e.preventDefault();
    const btn          = document.getElementById('btn-save-profile');
    const originalText = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const data = Object.fromEntries(new FormData(e.target).entries());
    const res  = await this.fetchAPI(API.UPDATE_PROFILE, 'POST', data);

    if (res && !res.error) {
      this.state.currentUser = { ...this.state.currentUser, ...data };
      this.updateUI();
      this.renderProfile();
      this.showToast('Profile Updated', 'Your changes have been saved.');
    } else {
      this.showToast('Update Failed', res?.error || 'Unknown error occurred.', 'danger');
    }

    btn.disabled  = false;
    btn.innerHTML = originalText;
  },

  showPasswordModal() {
    document.getElementById('modal-content').innerHTML = `
      <form onsubmit="app.handlePasswordChange(event)">
        <div class="form-group"><label>Current Password</label><div class="pw-input-wrap"><input type="password" name="current" required><button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button></div></div>
        <div class="form-group"><label>New Password</label><div class="pw-input-wrap"><input type="password" name="new" required><button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button></div></div>
        <div class="form-group"><label>Confirm New Password</label><div class="pw-input-wrap"><input type="password" name="confirm" required><button type="button" class="pw-toggle-btn" onclick="togglePw(this)" tabindex="-1"><i class="fas fa-eye"></i></button></div></div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
      </form>`;
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
      this.showToast('Error', res?.error || 'Failed to change password.', 'danger');
    }
  },

  /* ─── File Upload ─── */

  bindFileUpload() {
    const zone  = document.getElementById('upload-zone');
    const input = document.getElementById('proof-file');
    if (!zone || !input) return;

    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', (e) => {
      this.handleFileSelect(e.target.files);
      e.target.value = '';
    });
    zone.addEventListener('dragover',  (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', (e) => { if (!zone.contains(e.relatedTarget)) zone.classList.remove('drag-over'); });
    zone.addEventListener('drop',      (e) => { e.preventDefault(); zone.classList.remove('drag-over'); this.handleFileSelect(e.dataTransfer.files); });
  },

  handleFileSelect(files) {
    const MAX_SIZE    = 5 * 1024 * 1024;
    const ALLOWED     = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    Array.from(files).forEach(file => {
      if (!ALLOWED.includes(file.type)) {
        return this.showToast('Unsupported File', `${file.name} — only PDF and images are allowed.`, 'danger');
      }
      if (file.size > MAX_SIZE) {
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
      const isImage  = file.type.startsWith('image/');
      const blobUrl  = URL.createObjectURL(file);
      const size     = file.size > 1024 * 1024
        ? (file.size / 1024 / 1024).toFixed(1) + ' MB'
        : Math.round(file.size / 1024) + ' KB';
      return `
        <div class="preview-item">
          <a href="${blobUrl}" target="_blank" class="preview-media-link">
            ${isImage
              ? `<img class="preview-thumb" src="${blobUrl}" alt="${file.name}">`
              : `<div class="preview-icon-pdf"><i class="fas fa-file-pdf"></i></div>`}
          </a>
          <div class="preview-info">
            <a href="${blobUrl}" target="_blank" class="preview-name-link">${file.name}</a>
            <span class="preview-size">${size}</span>
          </div>
          <button class="preview-remove" type="button" onclick="app.removeFile(${i})">
            <i class="fas fa-times"></i>
          </button>
        </div>`;
    }).join('');
  },

  removeFile(index) {
    this.state.uploadedFiles.splice(index, 1);
    this.renderFilePreview();
  },

  /* ─── Toast Notification ─── */

  showToast(title, desc, type = 'success') {
    const toast = document.getElementById('toast');
    document.getElementById('toast-title').innerText = title;
    document.getElementById('toast-desc') .innerText = desc;
    toast.style.borderLeftColor = type === 'success' ? 'var(--success)' : 'var(--danger)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  },

  /* ─── Event Bindings ─── */

  bindEvents() {
    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', () => this.switchTab(item.dataset.target, item.innerText.trim()));
    });

    document.getElementById('btn-notifications')?.addEventListener('click', () => {
      this.switchTab('notifications', 'Notifications');
    });
    document.getElementById('btn-quick-apply')?.addEventListener('click', () => {
      this.switchTab('apply-leave', 'Submit Request');
    });

    this.bindFileUpload();

    // Close profile dropdown when clicking outside
    const profMenu = document.getElementById('profile-menu');
    const profDrop = document.getElementById('profile-dropdown');
    profMenu?.addEventListener('click', (e) => { e.stopPropagation(); profDrop?.classList.toggle('open'); });
    document.addEventListener('click', () => profDrop?.classList.remove('open'));
  },
};

document.addEventListener('DOMContentLoaded', () => app.init());
