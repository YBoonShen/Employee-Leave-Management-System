# NexusLeave — Employee Leave Management System
## Complete System Feature & Technical Report

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Technology Stack](#2-technology-stack)
3. [Database Schema](#3-database-schema)
4. [User Roles & Access Control](#4-user-roles--access-control)
5. [Authentication Module](#5-authentication-module)
6. [Leave Management Features](#6-leave-management-features)
7. [Manager Features](#7-manager-features)
8. [Employee Features](#8-employee-features)
9. [Notification System](#9-notification-system)
10. [File Upload System](#10-file-upload-system)
11. [Profile Management](#11-profile-management)
12. [API Endpoints — Complete Reference](#12-api-endpoints--complete-reference)
13. [Validation & Error Handling](#13-validation--error-handling)
14. [Security Measures](#14-security-measures)
15. [Malaysia Employment Act 1955 Compliance](#15-malaysia-employment-act-1955-compliance)
16. [Frontend Application (script.js)](#16-frontend-application-scriptjs)
17. [UI/UX Design Features](#17-uiux-design-features)
18. [Bug Fixes Applied](#18-bug-fixes-applied)
19. [File Structure](#19-file-structure)

---

## 1. System Overview

**NexusLeave** is a web-based Employee Leave Management System built for organisations to manage employee leave applications, approvals, balances, and records digitally.

| Item | Detail |
|------|--------|
| System Name | NexusLeave |
| Version | Production-ready |
| Target Users | Employees, Managers, Admins |
| Architecture | PHP + MySQL + Vanilla JavaScript (SPA-style) |
| Deployment | XAMPP (Apache + MySQL) |
| Database | nexusleave (UTF8MB4) |

---

## 2. Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ via PDO |
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| Icons | Font Awesome 6.0.0 |
| Fonts | Google Fonts — Fredoka (headings), Inter (body) |
| Session | PHP native sessions |
| Password Hashing | PHP `password_hash()` — bcrypt (PASSWORD_DEFAULT) |
| File Uploads | PHP `$_FILES`, `move_uploaded_file()` |

---

## 3. Database Schema

### Table: `users`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT PK | Primary key |
| employee_id | VARCHAR(20) UNIQUE NOT NULL | e.g. EMP001 |
| name | VARCHAR(100) NOT NULL | Full name |
| email | VARCHAR(150) UNIQUE NOT NULL | Login credential |
| password_hash | VARCHAR(255) NOT NULL | bcrypt hash |
| role | ENUM('employee','manager','admin') | Default: employee |
| employment_type | ENUM('Permanent','Contract','Part-Time') | Default: Permanent |
| join_date | DATE NULL | Start of employment |
| allowance | INT | Annual leave days entitlement |
| department | VARCHAR(100) | e.g. IT, HR |
| phone | VARCHAR(20) | Contact number |
| job_title | VARCHAR(100) | e.g. Senior Developer |
| location | VARCHAR(150) | Office location |
| created_at | TIMESTAMP | Auto-set on creation |

> **Dynamic migration**: Columns `phone`, `job_title`, `location`, `allowance`, `employment_type`, `join_date` are added at runtime via `ALTER TABLE … ADD COLUMN IF NOT EXISTS` for backward compatibility with existing databases.

---

### Table: `leave_requests`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT PK | Primary key |
| user_id | INT NOT NULL | FK → users.id (CASCADE DELETE) |
| type | ENUM('Annual','Sick','Unpaid') | Leave type |
| start_date | DATE NOT NULL | Leave start |
| end_date | DATE NOT NULL | Leave end |
| duration_days | INT NOT NULL | Working days (weekdays only) |
| reason | TEXT | Optional explanation |
| status | ENUM('Pending','Approved','Rejected') | Default: Pending |
| manager_comment | TEXT | Manager's approval/rejection note |
| proof_files | TEXT | JSON array of uploaded filenames |
| created_at | TIMESTAMP | Auto-set on creation |
| updated_at | TIMESTAMP NULL | Updated on edits/approvals |

> **Dynamic migration**: `manager_comment` and `proof_files` columns are added at runtime if missing.

---

### Table: `notifications`

| Column | Type | Notes |
|--------|------|-------|
| id | INT AUTO_INCREMENT PK | Primary key |
| user_id | INT NOT NULL | FK → users.id (CASCADE DELETE) |
| title | VARCHAR(200) | Default: 'System Alert' |
| message | TEXT NOT NULL | Notification body |
| type | VARCHAR(50) | e.g. 'approved', 'rejected', 'request', 'info' |
| request_id | INT NULL | Linked leave request |
| is_read | TINYINT(1) | 0 = unread, 1 = read |
| created_at | TIMESTAMP | Auto-set on creation |

---

## 4. User Roles & Access Control

| Feature | Employee | Manager | Admin |
|---------|----------|---------|-------|
| View own dashboard | ✓ | ✓ | ✓ |
| Apply for leave | ✓ | ✓ | ✓ |
| Edit pending leave | ✓ | ✓ | ✓ |
| Cancel pending leave | ✓ | ✓ | ✓ |
| View own history | ✓ | ✓ | ✓ |
| View all employees | ✗ | ✓ | ✓ |
| Approve/Reject leave | ✗ | ✓ | ✓ |
| View team statistics | ✗ | ✓ | ✓ |
| Override leave allowance | ✗ | ✓ | ✓ |
| Edit employee join_date | ✗ | ✓ | ✓ |
| Delete employees | ✗ | ✓ | ✓ |
| Download proof files | Own only | All | All |
| Approve own leave | ✗ | ✗ (blocked) | ✗ (blocked) |

---

## 5. Authentication Module

### 5.1 Registration (`auth-register-process.php` + `page-register.php`)

**Form Fields:**
- Full Name (required)
- Employee ID — must match pattern `EMP\d+` (required, client-side regex + server-side uniqueness check)
- Work Email (required, unique)
- Password (required, strength enforcement)
- Department (required)
- Job Title (required)
- Phone Number (required, pre-filled `+60`)
- Location (required)
- Join Date (required, date picker, max = today)
- Employment Type (required, card selector: Permanent / Contract / Part-Time)

**Server-side Validations:**
1. All required fields are non-empty
2. `employment_type` must be in `['Permanent', 'Contract', 'Part-Time']`
3. `join_date` must be valid `Y-m-d` format and not a future date
4. Email must not already exist in `users`
5. Employee ID must not already exist in `users`

**Password Rules (client-side + server-side):**
- Minimum 6 characters
- Must contain at least one letter
- Must contain at least one number
- Must contain at least one symbol

**Allowance Auto-Calculation:**
On registration, leave allowance is automatically calculated based on:
- Employment type
- Join date (years of service)
- Malaysia Employment Act 1955 tiers (see Section 15)

**Redirect Outcomes:**
| Outcome | Redirect |
|---------|----------|
| Success | `page-login.php?just_signed_up=1` |
| Duplicate email/ID | `page-register.php?error=exists` |
| Other failure | `page-register.php?error=1` |

---

### 5.2 Login (`auth-login-process.php` + `page-login.php`)

**Process:**
1. Check request method is POST
2. Run dynamic column migration for optional fields
3. Seed manager account on first run (email: `boonshen1159@gmail.com`, password: `Nexus@2024`, role: manager)
4. Enforce `role = 'manager'` for seeded account on every login (name is never overwritten)
5. Verify email exists and `password_verify()` passes
6. Call `session_regenerate_id(true)` to prevent session fixation
7. Store `user_id`, `employee_id`, `name`, `role` in `$_SESSION`
8. Redirect to `page-main.php`

**Redirect Outcomes:**
| Outcome | Redirect |
|---------|----------|
| Success | `page-main.php` |
| Bad credentials | `page-login.php?error=1` |

---

### 5.3 Forgot Password (`auth-forgot-password.php`)

Two-step flow, no email required (suitable for local/intranet systems):

**Step 1 — Verify Email (`step=verify`):**
- Validates email format via `filter_var(FILTER_VALIDATE_EMAIL)`
- Checks if email exists in database
- Returns `{"found": true/false}`

**Step 2 — Reset Password (`step=reset`):**
- Validates new password: minimum 6 characters
- Verifies email still exists in database
- Hashes new password with `password_hash(PASSWORD_DEFAULT)`
- Updates `password_hash` in database
- Returns `{"ok": true}`

**UI Steps (page-login.php):**
1. User enters email → system confirms account exists
2. User enters new password → system updates password
3. Success confirmation shown → redirects to login

---

### 5.4 Logout (`auth-logout-process.php`)

- Calls `session_unset()` — clears all session variables
- Calls `session_destroy()` — destroys session
- Redirects to `page-login.php`

---

## 6. Leave Management Features

### 6.1 Apply for Leave (`api-leave-create.php`)

**Input fields:** Leave Type, Start Date, End Date, Reason, Proof Documents (optional)

**Validations (server-side):**
1. User session must be active
2. Request method must be `POST`
3. `duration` must be > 0
4. `start_date` must be ≤ `end_date`
5. No overlapping leave request exists (status ≠ 'Rejected' check)
6. Leave balance check: `(approved_taken + new_duration) ≤ allowance`

**Actions on success:**
- Inserts record into `leave_requests`
- Sends notification to manager: *"New {type} leave request from {name}"*

**Leave Type Options:** Annual, Sick, Unpaid

---

### 6.2 Edit Leave Request (`api-leave-edit.php`)

**Conditions for editing:**
- Request must belong to current user
- Request status must be `Pending`

**Validations (server-side):**
1. Request method must be `POST`
2. `duration` must be > 0
3. `start_date` must be ≤ `end_date`
4. No overlap with other requests (current request is excluded from overlap check)
5. Leave balance check against approved leave only

**File handling on edit:**
- Old proof files are deleted from disk before saving new ones
- New file list replaces old one in database
tyr 
---

### 6.3 Cancel Leave Request (`api-leave-cancel.php`)

**Conditions:**
- Request must belong to current user
- Request status must be `Pending`

**Action:** Permanently deletes the leave request record.

---

### 6.4 Leave Duration Calculation

**Frontend (JavaScript):**
- `calculateWorkDays(start, end)` — counts working days only (Monday–Friday)
- Weekends are excluded from the duration count
- End date is included in the count

---

### 6.5 Leave Balance Tracking

Balance is derived dynamically:
```
Balance = Allowance − Total Approved Duration
```

- `allowance` stored per user in `users` table
- `taken` = `SUM(duration_days)` WHERE `status = 'Approved'`
- Displayed in dashboard: Total Allowance, Days Taken, Days Remaining

---

### 6.6 Leave Status Labels

| Status | Colour | Meaning |
|--------|--------|---------|
| Pending | Amber/Yellow | Awaiting manager review |
| Approved | Green | Manager approved |
| Rejected | Red | Manager rejected |

---

## 7. Manager Features

### 7.1 Approvals Dashboard (`api-leave-approve.php`)

- View all pending leave requests from all employees
- Approve or Reject with an optional comment
- **Self-approval is blocked**: Manager cannot approve their own request (`(int)owner_id === (int)session_id`)

---

### 7.2 Employee Management (`api-user-fetch-all.php`)

Manager can view full employee directory with:
- Name, Employee ID, Role, Department
- Employment Type (badge)
- Years of Service (calculated)
- Leave Allowance
- Phone, Location, Job Title, Email

---

### 7.3 Employee Profile View (modal)

Clicking any employee opens a detailed modal showing:
- Full profile info (name, ID, email, phone, department, job title, location)
- Employment type badge
- Join date and years of service
- Tier information (Malaysia Employment Act tier)
- Leave balance breakdown (Total / Taken / Remaining)
- Recent leave requests

---

### 7.4 Manual Allowance Override (`api-user-allowance-update.php`)

Manager can manually override any employee's annual leave allowance.

**Validations:**
1. User must be manager or admin
2. `user_id` must be > 0
3. `allowance` must be between 0 and 365
4. New allowance must not be less than days already approved (prevents negative balance)

---

### 7.5 Delete Employee (`api-user-delete.php`)

**Validations:**
1. User must be manager or admin
2. Target `user_id` must be > 0
3. Manager cannot delete their own account

**Cascading Delete:** Deleting a user also deletes all their `leave_requests` and `notifications` (via FK `ON DELETE CASCADE`).

---

### 7.6 Manager Dashboard Stats (`api-stats-fetch.php`)

| Stat Card | Query |
|-----------|-------|
| Pending Actions | `COUNT(*) WHERE status='Pending'` |
| Team on Leave Today | `COUNT(DISTINCT user_id)` WHERE today is within approved leave dates |
| Who's Away Today | List of employees on approved leave today |
| Recent Employee Activity | Latest leave requests across all employees |

---

## 8. Employee Features

### 8.1 Employee Dashboard

- Onboarding stepper: 3-step guide (Check Balance → Submit Request → Wait Approval)
- 3 stat cards: Annual Allowance, Leave Taken, Balance (with progress bars)
- Latest activity table showing recent requests

---

### 8.2 Employment Overview Card

Displayed on employee dashboard, auto-populated from database:
- Employment type badge (Permanent / Contract / Part-Time)
- Join date (formatted)
- Years of service (calculated to months)
- Current tier label (Tier 1 / 2 / 3) and daily entitlement
- Tier progress bar showing progress to next tier
- Note if manager has overridden the allowance

---

### 8.3 Leave Status Page

Table showing all employee's own leave requests with:
- Expand row (show/hide reason)
- Type, Period, Duration, Status badge
- Action buttons: Edit (Pending only), Cancel (Pending only)

---

### 8.4 Leave History Page

Full chronological history of all leave requests with status indicators.

---

## 9. Notification System

### Automatic Notifications Triggered

| Event | Recipient | Message |
|-------|-----------|---------|
| New leave request submitted | Manager | "New {type} leave request from {name}" |
| Leave approved by manager | Employee | "Your {type} leave request has been Approved by {managerName}." |
| Leave rejected by manager | Employee | "Your {type} leave request has been Rejected by {managerName}." |

### Notification Features

- Unread count badge on bell icon in topbar
- Notifications list page with timestamp
- Mark as read: `api-notify-read.php`
- Delete: `api-notify-delete.php`
  - Delete single notification by ID
  - Delete all read notifications at once (`all_read=true`)
- Notifications are user-specific (users only see their own)
- Limit: 20 most recent notifications per fetch

---

## 10. File Upload System

### Supported File Types

| Type | MIME | Extension |
|------|------|-----------|
| JPEG Image | image/jpeg | .jpg, .jpeg |
| PNG Image | image/png | .png |
| GIF Image | image/gif | .gif |
| WebP Image | image/webp | .webp |
| PDF Document | application/pdf | .pdf |

### Upload Rules
- Max 5 MB per file (enforced client-side via UI hint)
- Multiple files per request supported
- MIME type validated server-side via `mime_content_type()`
- File named using `uniqid('proof_', true)` to prevent collisions and enumeration
- Stored in `/uploads/` directory

### Download (`api-file-download.php`)

**Access control:**
- Must be logged in (session check)
- Managers and admins: can download any file
- Employees: can only download files linked to their own leave requests (SQL ownership check)

**Response headers:**
- `Content-Type` — correct MIME type
- `Content-Disposition: attachment` — triggers browser download
- `Content-Length` — file size
- `Cache-Control: no-cache`

**On edit:** Old proof files are deleted from disk before new ones are saved.

---

## 11. Profile Management

### 11.1 View Profile (`renderProfile()` in script.js)

Displays:
- Avatar with initials
- Role badge + Employment Type badge
- Employment & Contact Details (name, email, ID, department, phone, job title, location, employment type, join date, years of service)

### 11.2 Edit Profile (`api-user-profile-update.php`)

**Editable fields (all users):**
- Full Name, Work Email, Employee ID
- Department, Phone Number, Job Title, Location
- Employment Type (dropdown)

**Restricted field:**
- **Join Date** — editable by Manager/Admin only; displayed as read-only for employees with lock icon

**Allowance Recalculation on Join Date Change:**
When a manager changes an employee's join date, the allowance is automatically recalculated using the Malaysia Employment Act tiers and the effective employment type.

**Session sync:** After save, `$_SESSION['name']`, `$_SESSION['email']`, and `$_SESSION['employee_id']` are updated for real-time consistency.

---

### 11.3 Change Password (`api-user-password-change.php`)

**Requirements:**
- Current password must be verified first
- New password must pass all rules:
  - ≥ 6 characters
  - Contains at least one letter
  - Contains at least one number
  - Contains at least one symbol

---

## 12. API Endpoints — Complete Reference

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `api-leave-fetch.php` | GET | User | Fetch leave requests + user data |
| `api-leave-create.php` | POST | User | Submit new leave request |
| `api-leave-edit.php` | POST | User | Edit pending leave request |
| `api-leave-approve.php` | POST | Manager | Approve / Reject a request |
| `api-leave-cancel.php` | POST | User | Cancel pending request |
| `api-stats-fetch.php` | GET | Manager | Dashboard statistics |
| `api-notify-fetch.php` | GET | User | Fetch notifications (last 20) |
| `api-notify-read.php` | POST | User | Mark notification as read |
| `api-notify-delete.php` | POST | User | Delete notification(s) |
| `api-user-fetch-all.php` | GET | Manager | List all employees |
| `api-user-profile-update.php` | POST | User | Update own profile |
| `api-user-allowance-update.php` | POST | Manager | Override employee allowance |
| `api-user-password-change.php` | POST | User | Change password |
| `api-user-delete.php` | POST | Manager | Delete employee account |
| `api-file-download.php` | GET | User | Download proof document |
| `auth-register-process.php` | POST | None | Create new account |
| `auth-login-process.php` | POST | None | Login |
| `auth-logout-process.php` | GET | None | Logout |
| `auth-forgot-password.php` | POST | None | Password reset (2-step) |

---

## 13. Validation & Error Handling

### HTTP Status Codes Used

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful response (default) |
| 400 | Bad Request | Invalid input, failed business rule |
| 401 | Unauthorized | No active session |
| 403 | Forbidden | Insufficient role, self-approval attempt, ownership violation |
| 404 | Not Found | Record not found |
| 405 | Method Not Allowed | Wrong HTTP method |
| 500 | Internal Server Error | Unhandled exception |

### Field-Level Validations

| Field | Rule |
|-------|------|
| Employee ID | Pattern `EMP\d+` (client + server), unique in DB |
| Email | Valid email format (`filter_var`), unique in DB |
| Password (register/change) | ≥6 chars, contains letter, number, symbol |
| Password (forgot reset) | ≥6 chars |
| Employment Type | Must be in `['Permanent', 'Contract', 'Part-Time']` |
| Join Date | Valid `Y-m-d` format, not in future |
| Leave Duration | Must be ≥ 1 day |
| Start/End Date | Start must be ≤ End date |
| Leave Dates | No overlap with existing non-rejected requests |
| Leave Balance | `(approved_taken + new_duration) ≤ allowance` |
| Allowance Override | 0–365, must not be < days already approved |
| User ID (delete/allowance) | Must be integer > 0 |

### Exception Handling

All API endpoints wrap database operations in `try { … } catch (Throwable $e)`:
- Returns HTTP 500 with `{"error": "…"}` JSON
- Server errors never crash the page — caught and returned gracefully

### Frontend Error Handling

- Toast notification system for all success/error feedback
- `fetchAPI()` wrapper: returns `null` on network/parse error instead of throwing
- All `data.requests` accesses use `(data.requests || [])` null guard
- Modal shows error message if employee profile data fails to load

---

## 14. Security Measures

### Authentication & Session

| Measure | Implementation |
|---------|---------------|
| Session fixation prevention | `session_regenerate_id(true)` called after successful login |
| Session destruction on logout | `session_unset()` + `session_destroy()` |
| Role enforcement on every request | All protected endpoints check `$_SESSION['role']` |
| Password hashing | `password_hash(PASSWORD_DEFAULT)` — bcrypt |
| Password verification | `password_verify()` — timing-safe comparison |

### Input Security

| Measure | Implementation |
|---------|---------------|
| SQL Injection prevention | PDO prepared statements with named placeholders throughout |
| XSS prevention on page render | `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` on all session data in `page-main.php` |
| Integer casting | All ID parameters cast with `(int)` before use |
| String trimming | All text inputs trimmed before validation |
| ENUM validation | Employment type validated against whitelist array |
| Email format validation | `filter_var(FILTER_VALIDATE_EMAIL)` in forgot-password flow |

### Access Control

| Measure | Implementation |
|---------|---------------|
| Role-based API protection | Every manager-only endpoint checks `in_array($role, ['manager','admin'])` |
| Ownership checks | Leave edit/cancel verifies `user_id = session user_id` |
| Self-approval prevention | `(int)$owner['user_id'] === (int)$_SESSION['user_id']` strict type comparison |
| Self-deletion prevention | Manager cannot delete own account |
| File download access control | Employees can only download files linked to their own requests |
| Join date restriction | Only manager/admin can modify `join_date` via profile update API |

### File Upload Security

| Measure | Implementation |
|---------|---------------|
| MIME type whitelist | Only JPEG, PNG, GIF, WebP, PDF accepted |
| Upload error check | `UPLOAD_ERR_OK` required |
| Unique file naming | `uniqid('proof_', true)` prevents filename guessing |
| Path traversal prevention | `basename()` applied to all file parameters |
| Safe file move | `move_uploaded_file()` used (atomic, safe) |

---

## 15. Malaysia Employment Act 1955 Compliance

### Annual Leave Entitlement Tiers

| Years of Service | Annual Leave Entitlement |
|-----------------|-------------------------|
| Less than 2 years | 8 days |
| 2 years to less than 5 years | 12 days |
| 5 years or more | 16 days |

### Part-Time Pro-Rating

Part-Time employees receive 50% of the standard entitlement (ceiling applied):

| Tier | Full-Time | Part-Time |
|------|-----------|-----------|
| < 2 years | 8 days | 4 days |
| 2–5 years | 12 days | 6 days |
| ≥ 5 years | 16 days | 8 days |

### Where This Logic Is Applied

| Location | Context |
|----------|---------|
| `auth-register-process.php` | On new account creation |
| `api-user-profile-update.php` | When manager updates employee's join date |

### Tier Display (Employee Dashboard — Employment Overview Card)

| Tier | Colour | Label |
|------|--------|-------|
| Tier 1 | Blue (#2563eb) | < 2 years |
| Tier 2 | Amber (#d97706) | 2 – 5 years |
| Tier 3 | Green (#16a34a) | ≥ 5 years |

- Progress bar shows time elapsed toward next tier
- Countdown displays "Tier X (Y days) in Xyr Xm"
- Note shown if manager has manually overridden the calculated allowance

---

## 16. Frontend Application (script.js)

The entire dashboard is a single-page application driven by `script.js`.

### Architecture

- Single `app` object containing all state and methods
- `app.init()` bootstraps from `window.NEXUS_USER` (injected by PHP)
- `app.loadFromServer()` fetches data via `fetchAPI()` wrapper
- Role-based CSS classes on `<body>` control what UI sections are visible

### Key Functions

| Function | Purpose |
|----------|---------|
| `init()` | Bootstrap: load user, bind events, render UI |
| `loadFromServer()` | Fetch all leave data + user profile |
| `fetchAPI(endpoint, method, payload)` | Generic `fetch()` wrapper with JSON handling |
| `calculateWorkDays(start, end)` | Count Mon–Fri days between two dates |
| `getActTier(joinDate, employmentType)` | Calculate Employment Act tier + progress |
| `getYearsOfService(joinDate)` | Format service duration as "X years, Y months" |
| `getEtypeBadge(etype)` | Return coloured employment type badge HTML |
| `renderEmploymentCard()` | Render Employment Overview card on dashboard |
| `renderTables()` | Render all data tables based on role |
| `updateUI()` | Update avatars, stats, progress bars, call renders |
| `handleApplicationSubmit(event)` | Submit new leave request with file upload |
| `viewEmployeeProfile(userId)` | Load + render employee modal (manager) |
| `editAllowance(userId, current)` | Render inline allowance edit UI |
| `saveAllowance(userId)` | POST new allowance to API |
| `loadUsers()` | Fetch and render employee management table |
| `renderProfile()` | Render My Profile page |
| `handleProfileUpdate(event)` | POST profile changes to API |
| `showPasswordModal()` | Show change password form |
| `showToast(title, desc, type)` | Display toast notification |
| `switchTab(target, heading)` | SPA navigation between sections |

### State Object

```javascript
app.state = {
  role: 'employee',          // 'employee' | 'manager' | 'admin'
  currentUser: {             // Loaded from API
    name, id, allowance,
    employee_id, email,
    employment_type, join_date, ...
  },
  requests: [],              // All leave requests visible to user
  editingRequestId: null,    // ID being edited (null if new)
  uploadedFiles: [],         // Files staged for upload
}
```

---

## 17. UI/UX Design Features

### Layout
- Fixed sidebar navigation with logo, grouped nav links, and user profile footer
- Topbar with page title, notification bell (with unread dot indicator), quick action button, and profile dropdown
- Role-based visibility: `.employee-only` and `.manager-only` CSS classes control section visibility

### Colour System
- Primary: Blue `#3b82f6`
- Success: Green `#16a34a`
- Warning: Amber `#d97706`
- Danger: Red `#ef4444`
- Surface: White cards with subtle shadows

### Components
- **Stat Cards** with icon, value, progress bar, micro-label
- **Data Tables** with expand rows, action button groups, coloured status badges
- **Toast Notifications** — slide-in feedback for success/error/info
- **Modal** — full-screen overlay for detail views and employee profiles
- **File Upload Zone** — drag-and-drop styled area with file preview list
- **Employment Type Card Selector** — CSS-only radio card UI on register page
- **Horizontal Stepper** — onboarding guide for employees
- **Tier Progress Bar** — shows service tier advancement progress

### Responsive Navigation
- SPA tab switching — only the active `<section>` is visible
- Sidebar nav highlights active item
- Clicking notification bell navigates to Notifications tab

---

## 18. Bug Fixes Applied

The following bugs were identified and fixed during development QA:

| # | Severity | File | Issue Fixed |
|---|----------|------|-------------|
| 1 | Critical | `auth-login-process.php` | `else` branch was overwriting the manager's `name` to "Boon Shen" on every login, reverting any profile edits. Fixed: only `role = 'manager'` is enforced. |
| 2 | Critical | `auth-login-process.php` | Seed account created with hardcoded password `123`. Changed to `Nexus@2024`. |
| 3 | Critical | `script.js` | `getActTier()` had undeclared `color` variable in three `if/else` branches, creating implicit globals. Dead assignments removed; return already used `colors[tier]` correctly. |
| 4 | Medium | `api-user-password-change.php` | Password change only required 3 characters. Strengthened to: ≥6 chars, letter, number, symbol. |
| 5 | Medium | `api-user-profile-update.php` | Changing `join_date` did not recalculate `allowance` in the database. Now automatically recalculates and updates. |
| 6 | Medium | `api-user-allowance-update.php` | No check that new allowance ≥ days already approved. Now queries approved total and rejects if allowance would go below taken. |
| 7 | Medium | `script.js` | `viewEmployeeProfile()` used `data.requests.filter()` without null guard. Changed to `(data.requests \|\| []).filter()`. |
| 8 | Low | `api-user-fetch-all.php` | `phone` and `location` columns missing from SELECT, causing null values in employee profile modal. Added to query. |
| 9 | Medium | `api-leave-edit.php` | Missing POST method check — GET requests could trigger updates. Added `REQUEST_METHOD !== 'POST'` guard. |
| 10 | Medium | `api-leave-edit.php` | No validation that `duration > 0`. A 0-day edit could pass balance check. Added `duration <= 0` reject. |
| 11 | Medium | `api-leave-edit.php` | No validation that `start_date ≤ end_date`. Reversed dates silently accepted. Added date order check. |
| 12 | Medium | `api-leave-create.php` | Same as #10–11: missing `duration > 0` and date order validation. Both added. |
| 13 | Medium | `api-leave-approve.php` | Self-approval comparison used loose `==` without explicit type. Changed to `(int)$owner['user_id'] === (int)$_SESSION['user_id']`. |
| 14 | Medium | `api-user-profile-update.php` | Employees could submit `join_date` to manipulate their own leave tier/allowance. Now `join_date` is only accepted from manager/admin sessions. |
| 14 | Medium | `script.js` | Employee profile edit form showed editable `join_date` input. Changed to read-only display with "Managed by HR/Manager" note for non-manager users. |
| 15 | Low | `auth-login-process.php` | `session_regenerate_id(true)` not called after login, leaving system vulnerable to session fixation attacks. Added after successful login. |

---

## 19. File Structure

```
Employee-Leave-Management-System/
│
├── index.php                      # Entry point (redirect to page-main or page-login)
├── page-main.php                  # Session gate + injects NEXUS_USER + loads dashboard
├── page-login.php                 # Login + Forgot Password UI
├── page-register.php              # Registration form
├── view-dashboard.php             # Full dashboard HTML (all sections)
│
├── auth-login-process.php         # Login handler
├── auth-register-process.php      # Registration handler
├── auth-logout-process.php        # Logout handler
├── auth-forgot-password.php       # Password reset (2-step, no email)
│
├── api-leave-fetch.php            # GET leave requests + user info
├── api-leave-create.php           # POST new leave request
├── api-leave-edit.php             # POST edit pending request
├── api-leave-approve.php          # POST approve/reject (manager)
├── api-leave-cancel.php           # POST cancel pending request
│
├── api-stats-fetch.php            # GET manager dashboard statistics
│
├── api-notify-fetch.php           # GET notifications
├── api-notify-read.php            # POST mark notification as read
├── api-notify-delete.php          # POST delete notification(s)
│
├── api-user-fetch-all.php         # GET all users (manager)
├── api-user-profile-update.php    # POST update own profile
├── api-user-allowance-update.php  # POST override allowance (manager)
├── api-user-password-change.php   # POST change password
├── api-user-delete.php            # POST delete employee (manager)
│
├── api-file-download.php          # GET download proof document
│
├── config.php                     # DB connection (PDO singleton)
├── schema.sql                     # Database schema definition
│
├── script.js                      # Full SPA frontend logic
├── styles.css                     # All CSS styles
│
└── uploads/                       # Proof document uploads (runtime created)
```

---

*Report generated for NexusLeave Employee Leave Management System — PHP + MySQL + JavaScript*
