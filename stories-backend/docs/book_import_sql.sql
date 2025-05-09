-- Add type field to directory_items table if it doesn't exist
ALTER TABLE directory_items ADD COLUMN IF NOT EXISTS type VARCHAR(50) NOT NULL DEFAULT 'general';

-- Create books table
CREATE TABLE IF NOT EXISTS books (
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
);