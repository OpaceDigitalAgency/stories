-- Setup Settings Table
-- This script creates the settings table if it doesn't exist
-- Run this script in phpMyAdmin or another SQL client

-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_name` VARCHAR(255) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings if they don't exist
INSERT INTO `settings` (`setting_name`, `setting_value`, `setting_group`, `is_public`)
SELECT * FROM (
    SELECT 'ai_default_model' AS setting_name, 'gpt-4o' AS setting_value, 'ai' AS setting_group, 0 AS is_public
    UNION ALL
    SELECT 'enable_ai_analysis', '1', 'reviews', 0
    UNION ALL
    SELECT 'reviews_per_page', '10', 'reviews', 1
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `setting_name` IN ('ai_default_model', 'enable_ai_analysis', 'reviews_per_page')
);

-- Update existing settings with correct group
UPDATE `settings` SET `setting_group` = 'ai' WHERE `setting_name` = 'ai_default_model' AND `setting_group` != 'ai';
UPDATE `settings` SET `setting_group` = 'reviews' WHERE `setting_name` = 'enable_ai_analysis' AND `setting_group` != 'reviews';
UPDATE `settings` SET `setting_group` = 'reviews', `is_public` = 1 WHERE `setting_name` = 'reviews_per_page' AND `setting_group` != 'reviews';
