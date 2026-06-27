-- Fills in the manager (Boon Shen / EMP000) profile fields shown on the My Profile page.
-- Run after schema.sql. Safe on a fresh database: these columns are otherwise added lazily
-- at runtime by the app (see auth-register-process.php), so they are declared here explicitly.
USE `employee-leave-management-system`;

ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20);
ALTER TABLE users ADD COLUMN IF NOT EXISTS job_title VARCHAR(100);
ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(150);
ALTER TABLE users ADD COLUMN IF NOT EXISTS allowance INT DEFAULT 21;

UPDATE users
SET phone = '+601163369388',
    job_title = 'General Manager',
    location = 'Kuala Lumpur',
    join_date = '2013-06-24'
WHERE employee_id = 'EMP000';
