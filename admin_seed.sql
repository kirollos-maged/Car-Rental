-- Admin Module - Pre-Seeded Admin Account
-- Run this SQL script once to create the first admin user
-- 
-- IMPORTANT: Replace 'HASHED_PASSWORD_HERE' with an actual password hash
-- You can generate a hash using PHP: password_hash('your_password', PASSWORD_DEFAULT)
-- Or use an online bcrypt generator

-- Example: If your password is "admin123", the hash would be something like:
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (full_name, email, password, role, is_active)
VALUES (
    'Super Admin',
    'admin@youssef.com',
    '$2y$10$6qZjT3Bwvbx90L5aeFZDfO0YPptn9cUNrdzf8blrnrDa.JwruxVZK', -- youssef
    'ADMIN',
    1
);

-- Additional Admin Users
INSERT INTO users (full_name, email, password, role, is_active)
VALUES (
    'Admin Manager',
    'admin@yosra.com',
    '$2y$10$DfDOHZpUyWokzCfNDoZ8VuGPn7E.A92Jr2HY3/RAfCpxFPtZN3GvG', -- yosra
    'ADMIN',
    1
);

INSERT INTO users (full_name, email, password, role, is_active)
VALUES (
    'System Admin',
    'admin@kirolos.com',
    '$2y$10$tO.pth08.BjjbqnwIwpFseL31ny.A2ZEVuVoPn0lB6uOL1od8Q0nG', -- kirolos
    'ADMIN',
    1
);

INSERT INTO users (full_name, email, password, role, is_active)
VALUES (
    'Operations Admin',
    'admin@hossam.com',
    '$2y$10$bTJTqb3xsbvYuFn8MAmVO.A82gNMW5WF74iBGEmfcuSvXPRVgl9sm', -- hossam
    'ADMIN',
    1
);

-- NOTE: The default password hash above is for 'password'
-- For production, generate a new hash for a secure password:
-- 
-- PHP Code to generate hash:
-- <?php
-- echo password_hash('your_secure_password_here', PASSWORD_DEFAULT);
-- ?>
--
-- Or use MySQL (if available):
-- SELECT PASSWORD('your_secure_password_here');

