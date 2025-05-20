-- Setup script for Book Validation System
-- This script adds necessary columns to the books table and creates the validation_cache table

-- Check if validation_cache table exists and create it if not
CREATE TABLE IF NOT EXISTS validation_cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    cache_data LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
);

-- Add missing columns to books table
-- Check if language column exists
SELECT COUNT(*) INTO @language_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'language';

-- Add language column if it doesn't exist
SET @query = IF(@language_exists = 0, 'ALTER TABLE books ADD COLUMN language VARCHAR(50) DEFAULT NULL', 'SELECT "Language column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if format column exists
SELECT COUNT(*) INTO @format_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'format';

-- Add format column if it doesn't exist
SET @query = IF(@format_exists = 0, 'ALTER TABLE books ADD COLUMN format VARCHAR(50) DEFAULT NULL', 'SELECT "Format column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if preview_link column exists
SELECT COUNT(*) INTO @preview_link_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'preview_link';

-- Add preview_link column if it doesn't exist
SET @query = IF(@preview_link_exists = 0, 'ALTER TABLE books ADD COLUMN preview_link VARCHAR(255) DEFAULT NULL', 'SELECT "Preview link column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if internet_archive_id column exists
SELECT COUNT(*) INTO @internet_archive_id_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'internet_archive_id';

-- Add internet_archive_id column if it doesn't exist
SET @query = IF(@internet_archive_id_exists = 0, 'ALTER TABLE books ADD COLUMN internet_archive_id VARCHAR(100) DEFAULT NULL', 'SELECT "Internet Archive ID column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if awards column exists
SELECT COUNT(*) INTO @awards_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'awards';

-- Add awards column if it doesn't exist
SET @query = IF(@awards_exists = 0, 'ALTER TABLE books ADD COLUMN awards TEXT DEFAULT NULL', 'SELECT "Awards column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if characters column exists
SELECT COUNT(*) INTO @characters_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'characters';

-- Add characters column if it doesn't exist
SET @query = IF(@characters_exists = 0, 'ALTER TABLE books ADD COLUMN characters TEXT DEFAULT NULL', 'SELECT "Characters column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if settings column exists
SELECT COUNT(*) INTO @settings_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'settings';

-- Add settings column if it doesn't exist
SET @query = IF(@settings_exists = 0, 'ALTER TABLE books ADD COLUMN settings TEXT DEFAULT NULL', 'SELECT "Settings column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if maturity_rating column exists
SELECT COUNT(*) INTO @maturity_rating_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'maturity_rating';

-- Add maturity_rating column if it doesn't exist
SET @query = IF(@maturity_rating_exists = 0, 'ALTER TABLE books ADD COLUMN maturity_rating VARCHAR(50) DEFAULT NULL', 'SELECT "Maturity rating column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if rating column exists
SELECT COUNT(*) INTO @rating_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'rating';

-- Add rating column if it doesn't exist
SET @query = IF(@rating_exists = 0, 'ALTER TABLE books ADD COLUMN rating DECIMAL(3,2) DEFAULT NULL', 'SELECT "Rating column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if rating_count column exists
SELECT COUNT(*) INTO @rating_count_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'rating_count';

-- Add rating_count column if it doesn't exist
SET @query = IF(@rating_count_exists = 0, 'ALTER TABLE books ADD COLUMN rating_count INT DEFAULT NULL', 'SELECT "Rating count column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if review_count column exists
SELECT COUNT(*) INTO @review_count_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'review_count';

-- Add review_count column if it doesn't exist
SET @query = IF(@review_count_exists = 0, 'ALTER TABLE books ADD COLUMN review_count INT DEFAULT NULL', 'SELECT "Review count column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if validation_status column exists
SELECT COUNT(*) INTO @validation_status_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'validation_status';

-- Add validation_status column if it doesn't exist
SET @query = IF(@validation_status_exists = 0, 'ALTER TABLE books ADD COLUMN validation_status ENUM("pending", "valid", "invalid", "partial") DEFAULT "pending"', 'SELECT "Validation status column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if last_validated column exists
SELECT COUNT(*) INTO @last_validated_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'last_validated';

-- Add last_validated column if it doesn't exist
SET @query = IF(@last_validated_exists = 0, 'ALTER TABLE books ADD COLUMN last_validated TIMESTAMP NULL', 'SELECT "Last validated column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better performance
ALTER TABLE books ADD INDEX IF NOT EXISTS idx_isbn (isbn);
ALTER TABLE books ADD INDEX IF NOT EXISTS idx_isbn13 (isbn13);
ALTER TABLE books ADD INDEX IF NOT EXISTS idx_internet_archive_id (internet_archive_id);
ALTER TABLE books ADD INDEX IF NOT EXISTS idx_validation_status (validation_status);
