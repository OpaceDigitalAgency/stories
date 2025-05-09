-- Simple SQL script for adding book support

-- Note: The 'type' column already exists in the directory_items table, so we don't need to add it.
-- If you're setting up a new installation, you would need to ensure the directory_items table has a 'type' column:
-- ALTER TABLE directory_items ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general';

-- 1. Run this to create the books table
CREATE TABLE books (
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

-- 2. If the above fails with "Table already exists", it means the table already exists, which is fine

-- 3. Run this to create the book_authors table for book-author relationships
CREATE TABLE book_authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    directory_item_id INT NOT NULL,
    author_id INT NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'author',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
    UNIQUE KEY (directory_item_id, author_id, role)
);

-- 4. If the above fails with "Table already exists", it means the table already exists, which is fine