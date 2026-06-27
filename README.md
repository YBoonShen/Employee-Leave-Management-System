# NexusLeave — Employee Leave Management System

A web-based leave management system built for TWT2231 Web Techniques and Applications (MMU).  
Supports two roles: **Employee** and **Manager**.

---

## Features

**Employee**
- Register and log in securely
- Apply for Annual, Sick, or Unpaid leave
- Attach supporting documents (PDF, JPG, PNG — max 3 files)
- View leave balance, leave history, and request status
- Edit or cancel pending requests
- Receive in-app notifications
- Update profile and change password

**Manager**
- Approve or reject leave requests with a comment
- Download proof documents submitted by employees
- View real-time team statistics and daily attendance
- Manage employee accounts and override leave allowances
- View leave reports with charts and filters

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Server | Apache (XAMPP) |

---

## Setup

### Requirements
- [XAMPP](https://www.apachefriends.org) (Apache + MySQL + PHP)
- Any modern browser (Chrome, Firefox, Edge)

---

### Step 1 — Copy the project folder

Place the project folder inside XAMPP's `htdocs` directory:

```
C:\xampp\htdocs\Employee-Leave-Management-System\
```

Or clone this repository directly into `htdocs`:

```bash
cd C:\xampp\htdocs
git clone https://github.com/YBoonShen/Employee-Leave-Management-System.git
```

---

### Step 2 — Start XAMPP

Open the XAMPP Control Panel and start both:
- **Apache**
- **MySQL**

---

### Step 3 — Set up the database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** and create a database named:
   ```
   leave_management
   ```
3. Select the database, go to the **Import** tab
4. Import the file:
   ```
   seed-manager-profile.sql
   ```
5. Click **Go**

---

### Step 4 — Open the system

Go to:
```
http://localhost/Employee-Leave-Management-System/page-login.php
```

---

## Login Credentials

### Manager
| Field | Value |
|---|---|
| Email | boonshen1159@gmail.com |
| Password | Yeap05** |

> The manager account is created automatically on first login. No extra setup needed.

### Employee
Click **Create Account** on the login page to register.

Password requirements:
- Minimum 8 characters
- At least one uppercase letter, one lowercase letter, one number, and one special symbol

---

## Project Structure

```
Employee-Leave-Management-System/
├── page-login.php              # Login page
├── page-register.php           # Register page
├── page-main.php               # Main dashboard
├── auth-login-process.php      # Login handler
├── auth-register-process.php   # Registration handler
├── auth-forgot-password.php    # Password reset handler
├── api-*.php                   # Backend API endpoints
├── script.js                   # Frontend JavaScript (SPA)
├── styles.css                  # All CSS styling
├── config.php                  # Database connection
├── seed-manager-profile.sql    # Database setup file
├── uploads/                    # Uploaded proof documents
└── readme.txt                  # Setup instructions
```

---

## Group Members

| No | Student ID | Name |
|---|---|---|
| 1 | 242UT244D2 | Yeap Boon Shen |
| 2 | 242UT241HP | Lee Jia Yin |
| 3 | 243UT246VN | Samantha Chan Pei Yin |
| 4 | 243UT246G2 | Teo Kee Jie |

Lab Section: 1F — TWT2231 Mar/Apr 2026
