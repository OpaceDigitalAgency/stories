-- Simple SQL script for adding book support

-- Note: The 'type' column already exists in the directory_items table, so we don't need to add it.
-- If you're setting up a new installation, you would need to ensure the directory_items table has a 'type' column:
-- ALTER TABLE directory_items ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general';

-- Note: The 'books' table already exists, so we don't need to create it.
-- If you're setting up a new installation, you would need to create the books table:
-- CREATE TABLE books (
--     directory_item_id INT PRIMARY KEY,
--     isbn VARCHAR(20),
--     isbn13 VARCHAR(20),
--     author VARCHAR(255),
--     publisher VARCHAR(255),
--     publication_date DATE,
--     page_count INT,
--     age_range VARCHAR(50),
--     reading_level VARCHAR(50),
--     cover_image_url VARCHAR(255),
--     purchase_links JSON,
--     metadata JSON,
--     genre VARCHAR(255),
--     series VARCHAR(255),
--     FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE
-- );

-- Add genre and series columns to the books table if they don't exist
-- Note: MySQL doesn't support IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- Run these statements one at a time, and ignore errors if the columns already exist

-- First, check if genre column exists
SELECT COUNT(*) INTO @genre_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'genre';

-- Add genre column if it doesn't exist
SET @query = IF(@genre_exists = 0, 'ALTER TABLE books ADD COLUMN genre VARCHAR(255)', 'SELECT "Genre column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Then, check if series column exists
SELECT COUNT(*) INTO @series_exists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'books' AND column_name = 'series';

-- Add series column if it doesn't exist
SET @query = IF(@series_exists = 0, 'ALTER TABLE books ADD COLUMN series VARCHAR(255)', 'SELECT "Series column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Alternative approach: Just run these directly and ignore errors if columns already exist
-- ALTER TABLE books ADD COLUMN genre VARCHAR(255);
-- ALTER TABLE books ADD COLUMN series VARCHAR(255);

-- Note: The 'book_authors' table already exists, so we don't need to create it.
-- If you're setting up a new installation, you would need to create the book_authors table:
-- CREATE TABLE book_authors (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     directory_item_id INT NOT NULL,
--     author_id INT NOT NULL,
--     role VARCHAR(50) NOT NULL DEFAULT 'author',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
--     FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
--     UNIQUE KEY (directory_item_id, author_id, role)
-- );