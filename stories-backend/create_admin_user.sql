-- Create admin user with proper password hash
-- The password is: Pa55word!
-- This hash was generated with password_hash('Pa55word!', PASSWORD_DEFAULT) in PHP 8.2

-- Delete existing admin user to ensure clean state
DELETE FROM users WHERE email = 'admin@example.com';

-- Insert a new admin user with the correct hash
INSERT INTO users
       (name,          email,            password,                         role,  active, created_at,        updated_at)
VALUES ('Site Admin', 'admin@example.com',
        '$2y$10$8AobgFUdBaUKoeBkFxfRgeIod6CVuToAfM0c/niIXv3LhyCd9cCIu', -- hash of:  Pa55word!
        'admin',        1,      NOW(),             NOW());

-- Verify the user was created
SELECT id, name, email, role, active FROM users WHERE email = 'admin@example.com';