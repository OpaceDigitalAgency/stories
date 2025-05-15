-- Create Review System Tables
-- This script creates the necessary tables for the book review system
-- Run this script in phpMyAdmin or another SQL client

-- Create review_sources table if it doesn't exist
CREATE TABLE IF NOT EXISTS `review_sources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `is_third_party` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reviews table if it doesn't exist
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `book_id` INT NOT NULL,
    `source_id` INT NOT NULL,
    `reviewer_name` VARCHAR(255) NOT NULL,
    `reviewer_age` TINYINT NULL,
    `review_date` DATE NULL,
    `original_rating` VARCHAR(50) NULL,
    `rating_value` DECIMAL(10,2) NULL,
    `rating_scale` DECIMAL(10,2) NULL,
    `rating_normalised` DECIMAL(3,2) NULL,
    `review_text` TEXT NULL,
    `metadata` JSON NULL,
    `ai_summary` TEXT NULL,
    `suitability_score` DECIMAL(3,2) NULL,
    `content_flags` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`book_id`) REFERENCES `directory_items` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`source_id`) REFERENCES `review_sources` (`id`) ON DELETE CASCADE,
    INDEX `idx_book_id` (`book_id`),
    INDEX `idx_source_id` (`source_id`),
    INDEX `idx_reviewer_name` (`reviewer_name`),
    INDEX `idx_rating_normalised` (`rating_normalised`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add review-related columns to directory_items table if they don't exist
ALTER TABLE `directory_items` 
    ADD COLUMN IF NOT EXISTS `review_count` INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `average_rating` DECIMAL(3,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `highest_rating` DECIMAL(3,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lowest_rating` DECIMAL(3,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `suitability_score` DECIMAL(3,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `content_flags` JSON DEFAULT NULL;

-- Insert default review sources if the table is empty
INSERT INTO `review_sources` (`name`, `url`, `is_third_party`)
SELECT * FROM (
    SELECT 'Stories from the Web' AS name, 'https://storiesfromtheweb.org' AS url, 0 AS is_third_party
    UNION ALL
    SELECT 'Google Books', 'https://books.google.com', 1
    UNION ALL
    SELECT 'Open Library', 'https://openlibrary.org', 1
    UNION ALL
    SELECT 'Goodreads', 'https://goodreads.com', 1
    UNION ALL
    SELECT 'Amazon', 'https://amazon.com', 1
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `review_sources` LIMIT 1
);

-- Create book_authors table if it doesn't exist
CREATE TABLE IF NOT EXISTS `book_authors` (
    `directory_item_id` INT NOT NULL,
    `author_id` INT NOT NULL,
    `role` ENUM('author', 'publisher', 'illustrator', 'editor') NOT NULL DEFAULT 'author',
    PRIMARY KEY (`directory_item_id`, `author_id`, `role`),
    FOREIGN KEY (`directory_item_id`) REFERENCES `directory_items` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add author_type column to authors table if it doesn't exist
ALTER TABLE `authors` 
    ADD COLUMN IF NOT EXISTS `author_type` ENUM('author', 'retail', 'publisher') NOT NULL DEFAULT 'author',
    ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(255) DEFAULT NULL;
