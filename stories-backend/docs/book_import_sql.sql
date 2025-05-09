-- Check if type column exists in directory_items table
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'directory_items' AND column_name = 'type';

-- Add type field to directory_items table if it doesn't exist
SET @query = IF(@columnExists = 0,
    'ALTER TABLE directory_items ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT \'general\'',
    'SELECT \'Column already exists\' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if books table exists
SET @tableExists = 0;
SELECT COUNT(*) INTO @tableExists FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'books';

-- Create books table if it doesn't exist
SET @query = IF(@tableExists = 0, 'CREATE TABLE books (
    directory_item_id INT PRIMARY KEY,
    isbn VARCHAR(20),
    isbn13 VARCHAR(20),
    author VARCHAR(255),
    publisher VARCHAR(255),
    publication_date DATE,
    page_count INT,
    age_range VARCHAR(50),
    reading_level VARCHAR(50),
    cover_image_url VARCHAR(255),
    purchase_links JSON,
    metadata JSON,
    FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE
)', 'SELECT \'Books table already exists\' AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;