-- Create Settings Table
-- This script creates the settings table for storing application settings
-- Run this script in phpMyAdmin or another SQL client

-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_name` VARCHAR(255) NOT NULL,
    `setting_value` TEXT NULL,
    `setting_group` VARCHAR(100) DEFAULT 'general',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings if they don't exist
INSERT INTO `settings` (`setting_name`, `setting_value`, `setting_group`, `is_public`)
SELECT * FROM (
    SELECT 'site_name' AS setting_name, 'Stories from the Web' AS setting_value, 'general' AS setting_group, 1 AS is_public
    UNION ALL
    SELECT 'site_description', 'A collection of stories from around the web', 'general', 1
    UNION ALL
    SELECT 'contact_email', 'contact@storiesfromtheweb.org', 'general', 1
    UNION ALL
    SELECT 'openai_api_key', '', 'ai', 0
    UNION ALL
    SELECT 'ai_default_model', 'gpt-4-turbo', 'ai', 0
    UNION ALL
    SELECT 'reviews_per_page', '10', 'reviews', 1
    UNION ALL
    SELECT 'enable_ai_analysis', '1', 'reviews', 0
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `setting_name` = tmp.setting_name
);
