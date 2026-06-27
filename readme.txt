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

2. Click "New" on the left side.
3. Type this as the database name:
      leave_management
4. Click Create.

5. Click on "leave_management" in the left panel.
6. Click the "Import" tab at the top.
7. Click "Choose File".
8. Go to the project folder and select the file:
      seed-manager-profile.sql
9. Scroll down and click "Go".
10. You should see a green success message.


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
   the first time anyone logs in.
   No extra setup is needed.

---- EMPLOYEE ACCOUNT ----
   Click "Create Account" on the Login page to register
   a new employee account.

   Password must have:
   - At least 8 characters
   - One uppercase letter  (A–Z)
   - One lowercase letter  (a–z)
   - One number            (0–9)
   - One special symbol    (e.g. @, #, !)

   Example:  Ahmad123!


================================================================
FOLDER STRUCTURE (what is inside the project)
================================================================

   page-login.php          — Login page
   page-register.php       — Register page
   page-main.php           — Main dashboard (Employee + Manager)
   auth-login-process.php  — Handles login
   auth-register-process.php — Handles registration
   auth-forgot-password.php  — Handles password reset
   api-*.php               — All backend API endpoints
   script.js               — All frontend JavaScript
   styles.css              — All CSS styling
   config.php              — Database connection settings
   uploads/                — Folder where proof files are saved
   seed-manager-profile.sql — SQL file to set up the database


================================================================
IF SOMETHING DOES NOT WORK
================================================================

Problem: Page not found (404)
Fix    : Make sure Apache is running in XAMPP.
         Make sure the folder is inside htdocs.

Problem: Database error
Fix    : Make sure MySQL is running in XAMPP.
         Make sure you created the database named "leave_management"
         and imported the SQL file.

Problem: Cannot log in as Manager
Fix    : Just open the Login page and log in once.
         The system creates the manager account automatically.

Problem: Uploaded files not saving
Fix    : The "uploads" folder must exist inside the project folder.
         Create it manually if it is missing.


================================================================
  Built with HTML5, CSS3, JavaScript, PHP, and MySQL
  Runs on Apache via XAMPP
================================================================
