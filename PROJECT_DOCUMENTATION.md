# NexusLeave - Project Technical Report

# NexusLeave - 项目技术报告

## 1. System Overview | 系统概述

NexusLeave is a web-based system for managing employee leave. It has two roles: **Employee** and **Manager**.
NexusLeave 是一个用于管理员工休假的网页系统。它包含两个角色：**员工** 和 **经理**。

---

## 2. File Directory | 文件目录

| New File Name        | Purpose                                      |
| :------------------- | :------------------------------------------- |
| `page-login.php`     | The first page you see to sign in.           |
| `page-register.php`  | Page to create a new account.                |
| `page-main.php`      | The main dashboard after you log in.         |
| `view-dashboard.php` | The layout and HTML structure of the system. |
| `script.js`          | All the "brains" and logic of the website.   |
| `styles.css`         | All the colors, fonts, and layout designs.   |

---

## 3. Step-by-Step User Flow | 完整操作流程

### Flow 1: Authentication (Login/Register) | 身份验证流

1.  **Register**: User fills in 7 fields. The system checks if the email already exists.
    - **Failure**: If email is taken, it shows "Email already in use."
2.  **Login**: User enters email and password.
    - **Success**: Goes to `page-main.php`.
    - **Failure**: Shows "Invalid email or password" if details are wrong.

### Flow 2: Applying for Leave | 请假申请流

1.  **Select Dates**: User picks start and end dates.
2.  **Logic**: `calculateWorkDays()` runs to skip Saturday and Sunday.
3.  **Backend Check**: `api-leave-create.php` checks two things:
    - **Balance**: Do you have enough days left?
    - **Overlap**: Did you already apply for leave on these dates?
4.  **Result**:
    - **Success**: Button shows "Submitting...", then shows a success screen.
    - **Failure**: Shows a red Toast message like "Insufficient Balance".

### Flow 3: Manager Approval | 经理审批流

1.  **View Requests**: Manager sees a list of pending requests.
2.  **Check Details**: Manager clicks the employee **Avatar** to see their full profile and history in a large 1000px window.
3.  **Action**: Manager clicks **Approve** or **Reject**.
4.  **Feedback**: Manager enters a reason (mandatory for rejection).
5.  **Notification**: Employee gets a real-time alert in their bell icon.

---

## 4. Full Function List (Numbered) | 完整函数清单

### Frontend Functions (script.js)

1.  **init()**: Starts the app and sets the minimum date for the calendar.
2.  **fetchAPI()**: Sends data to the server and gets a response back.
3.  **loadFromServer()**: Gets your profile and leave list from the database.
4.  **getInitials()**: Changes "Ali Ahmad" to "AA" for the avatar.
5.  **formatDate()**: Changes "2026-03-18" to "Mar 18" to make it easy to read.
6.  **calculateWorkDays()**: The core math logic that skips weekends.
7.  **refreshAndRender()**: Refreshes everything on the page without reloading.
8.  **renderTables()**: Draws the tables for Dashboard, Status, and History.
9.  **toggleRow()**: Opens the small "+" to see comments and reasons.
10. **switchTab()**: Moves between Dashboard, Profile, and Notifications.
11. **loadUsers()**: (Manager) Gets the list of all staff members.
12. **viewEmployeeProfile()**: (Manager) Opens the large side-by-side profile page.
13. **deleteUser()**: (Manager) Removes an employee from the system.
14. **loadNotifications()**: Loads the messages in the top bell menu.
15. **markNotificationRead()**: Removes the blue dot from a notification.
16. **updateUI()**: Updates the Allowance/Taken/Balance numbers and progress bars.
17. **renderProfile()**: Shows the vertical profile page with 7 fields.
18. **handleApplicationSubmit()**: Handles the "Submit Leave" button click.
19. **handleProfileUpdate()**: Handles the "Save Profile" button click.
20. **showPasswordModal()**: Opens the pop-up to change password.
21. **handlePasswordChange()**: Sends the new password to the server.
22. **showToast()**: Shows the small message box at the bottom right.
23. **bindEvents()**: Listens for all clicks on buttons and menus.
24. **cancelRequest()**: Allows users to delete a "Pending" request.
25. **approveRequest()**: Manager's "Approve" button logic.
26. **rejectRequest()**: Manager's "Reject" button logic.
27. **viewRequestDetails()**: Shows full info of a single leave request.
28. **closeModal()**: Closes any open pop-up window.

### Backend APIs (PHP)

29. **auth-login-process.php**: Checks your password and starts your session.
30. **auth-register-process.php**: Saves new users into the database.
31. **auth-logout-process.php**: Logs you out safely.
32. **api-leave-fetch.php**: Gets leave data. Supports `user_id` for manager views.
33. **api-leave-create.php**: Logic to save a new leave request (with balance check).
34. **api-leave-edit.php**: Logic to update an existing leave request.
35. **api-leave-cancel.php**: Logic to delete a request from the database.
36. **api-leave-approve.php**: Manager's API to change status to Approved/Rejected.
37. **api-user-fetch-all.php**: Gets the full list of employees.
38. **api-user-delete.php**: Completely removes a user and their data.
39. **api-user-profile-update.php**: Updates phone, job, location, etc.
40. **api-user-password-change.php**: Changes and encrypts your new password.
41. **api-notify-fetch.php**: Gets your latest alerts.
42. **api-notify-read.php**: Marks alerts as seen.
43. **api-notify-delete.php**: Clears old notifications.
44. **api-stats-fetch.php**: Provides data for the Pie Chart and KPI cards.

---

## 5. Security & Error Handling | 安全与错误处理

### Why would a function fail? | 为什么会操作失败？

- **Wrong Role**: If an employee tries to use a Manager API, they get "403 Forbidden".
- **Duplicate Date**: If you try to apply for leave on a date you already booked.
- **No Balance**: If you try to take 10 days but only have 5 left.
- **Empty Fields**: If you leave the "Reason" blank when applying.
- **Self-Delete**: A manager cannot delete their own account.

### Defensive Design | 防御性设计

- **Loading Spinners**: Buttons become unclickable when waiting for the server.
- **DB Migration**: The system automatically adds missing columns (like `allowance`) if they don't exist.
- **Silent Refresh**: The system updates numbers without making the user click "Refresh".

---
