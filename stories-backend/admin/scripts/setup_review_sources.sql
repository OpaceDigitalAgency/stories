-- Setup Review Sources
-- This script creates and populates the review_sources table if it doesn't exist
-- Run this script in phpMyAdmin or another SQL client

-- Create review_sources table if it doesn't exist
CREATE TABLE IF NOT EXISTS `review_sources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `is_third_party` TINYINT(1) NOT NULL DEFAULT 1,
    `api_key` VARCHAR(255) NULL,
    `api_secret` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_source_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default review sources if they don't exist
INSERT INTO `review_sources` (`name`, `url`, `is_third_party`, `api_key`, `api_secret`)
SELECT * FROM (
    SELECT 'Amazon' AS name, 'https://www.amazon.com' AS url, 1 AS is_third_party, NULL AS api_key, NULL AS api_secret
    UNION ALL
    SELECT 'Goodreads', 'https://www.goodreads.com', 1, NULL, NULL
    UNION ALL
    SELECT 'Google Books', 'https://books.google.com', 1, NULL, NULL
    UNION ALL
    SELECT 'School Library Journal', 'https://www.slj.com', 1, NULL, NULL
    UNION ALL
    SELECT 'Kirkus Reviews', 'https://www.kirkusreviews.com', 1, NULL, NULL
    UNION ALL
    SELECT 'Open Library', 'https://openlibrary.org', 1, NULL, NULL
    UNION ALL
    SELECT 'Stories From The Web', 'https://storiesfromtheweb.org', 0, NULL, NULL
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `review_sources` WHERE `name` IN (
        'Amazon', 'Goodreads', 'Google Books', 'School Library Journal', 
        'Kirkus Reviews', 'Open Library', 'Stories From The Web'
    )
);

-- Update existing sources with correct URLs
UPDATE `review_sources` SET `url` = 'https://www.amazon.com' WHERE `name` = 'Amazon';
UPDATE `review_sources` SET `url` = 'https://www.goodreads.com' WHERE `name` = 'Goodreads';
UPDATE `review_sources` SET `url` = 'https://books.google.com' WHERE `name` = 'Google Books';
UPDATE `review_sources` SET `url` = 'https://www.slj.com' WHERE `name` = 'School Library Journal';
UPDATE `review_sources` SET `url` = 'https://www.kirkusreviews.com' WHERE `name` = 'Kirkus Reviews';
UPDATE `review_sources` SET `url` = 'https://openlibrary.org' WHERE `name` = 'Open Library';
UPDATE `review_sources` SET `url` = 'https://storiesfromtheweb.org', `is_third_party` = 0 WHERE `name` = 'Stories From The Web';
