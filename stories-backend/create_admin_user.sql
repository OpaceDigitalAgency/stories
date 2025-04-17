-- Create admin user with proper password hash
-- The password is: Pa55word!
-- This hash was generated with password_hash('Pa55word!', PASSWORD_DEFAULT) in PHP 8.2

-- First, check if the user already exists
SELECT COUNT(*) FROM users WHERE email = 'admin@example.com';

-- If the user exists, update the password
UPDATE users 
SET password = '$2y$10$8AobgFUdBaUKoeBkFxfRgeIod6CVuToAfM0c/niIXv3LhyCd9cCIu',
    active = 1,
    updated_at = NOW()
WHERE email = 'admin@example.com';

-- If the user doesn't exist, insert a new admin user
INSERT INTO users 
       (name,          email,            password,                         role,  active, created_at,        updated_at)
SELECT 'Site Admin', 'admin@example.com', 
        '$2y$10$8AobgFUdBaUKoeBkFxfRgeIod6CVuToAfM0c/niIXv3LhyCd9cCIu', -- hash of:  Pa55word!
        'admin',        1,      NOW(),             NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.com');

-- Verify the user was created/updated
SELECT id, name, email, role, active FROM users WHERE email = 'admin@example.com';