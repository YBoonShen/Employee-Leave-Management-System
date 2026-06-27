================================================================
  NexusLeave — Employee Leave Management System
  Setup Instructions
================================================================

WHAT YOU NEED BEFORE YOU START
----------------------------------------------------------------
1. XAMPP  —  download from https://www.apachefriends.org
2. A web browser  —  Google Chrome, Firefox, or Microsoft Edge


================================================================
STEP 1 — COPY THE PROJECT FOLDER
================================================================

1. Unzip the project file you downloaded.
2. You will see a folder called:
      Employee-Leave-Management-System

3. Copy that folder into the XAMPP htdocs folder.
   The htdocs folder is usually here:

      Windows  :  C:\xampp\htdocs\
      Mac      :  /Applications/XAMPP/htdocs/

   After copying, it should look like this:
      C:\xampp\htdocs\Employee-Leave-Management-System\


================================================================
STEP 2 — START XAMPP
================================================================

1. Open the XAMPP Control Panel.
2. Click the START button next to Apache.
3. Click the START button next to MySQL.
4. Both rows should turn green.


================================================================
STEP 3 — SET UP THE DATABASE
================================================================

1. Open your browser and go to:
      http://localhost/phpmyadmin

2. Click the "Import" tab at the top (do NOT select any database first).
3. Click "Choose File".
4. Go to the project folder and select the file:
      schema.sql
5. Scroll down and click "Go".
6. You should see a green success message.

   NOTE: schema.sql automatically creates the database called
   "employee-leave-management-system" and all three tables.
   You do not need to create the database manually.


================================================================
STEP 4 — OPEN THE SYSTEM
================================================================

1. Open your browser.
2. Go to this link:
      http://localhost/Employee-Leave-Management-System/page-login.php

3. The Login page will appear.
4. The system is ready to use.


================================================================
LOGIN DETAILS
================================================================

---- MANAGER ACCOUNT ----
   Email     :  boonshen1159@gmail.com
   Password  :  Yeap05**

   The manager account is created automatically by the system
   the first time you visit the Login page.
   No extra setup is needed.

---- EMPLOYEE ACCOUNT ----
   Click "Create Account" on the Login page to register
   a new employee account.

   Password must have:
   - At least 8 characters
   - One uppercase letter  (A-Z)
   - One lowercase letter  (a-z)
   - One number            (0-9)
   - One special symbol    (e.g. @, #, !)

   Example:  Ahmad123!


================================================================
FOLDER STRUCTURE (what is inside the project)
================================================================

   page-login.php             — Login page
   page-register.php          — Register page
   page-main.php              — Main dashboard (Employee + Manager)
   auth-login-process.php     — Handles login
   auth-register-process.php  — Handles registration
   auth-forgot-password.php   — Handles password reset
   auth-logout-process.php    — Handles logout
   api-*.php                  — All backend API endpoints
   script.js                  — All frontend JavaScript
   styles.css                 — All CSS styling
   config.php                 — Database connection settings
   schema.sql                 — Database setup (run this first)
   seed-manager-profile.sql   — Optional: fills manager profile details
   uploads/                   — Folder where proof files are saved


================================================================
IF SOMETHING DOES NOT WORK
================================================================

Problem: Page not found (404 error)
Fix    : Make sure Apache is running in XAMPP.
         Make sure the project folder is inside htdocs and named
         exactly "Employee-Leave-Management-System".

Problem: Database connection error
Fix    : Make sure MySQL is running in XAMPP.
         Make sure you imported schema.sql correctly.
         The database name must be "employee-leave-management-system"
         (set in config.php).

Problem: Cannot log in as Manager
Fix    : Just open the Login page. The system creates the
         manager account automatically on first visit.

Problem: Uploaded files not saving
Fix    : Make sure the "uploads" folder exists inside the project
         folder. Create it manually if it is missing.


================================================================
  Full source code also available at:
  https://github.com/YBoonShen/Employee-Leave-Management-System
================================================================
  Built with HTML5, CSS3, JavaScript, PHP, and MySQL
  Runs on Apache via XAMPP
================================================================
