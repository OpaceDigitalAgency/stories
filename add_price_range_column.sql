-- Add price_range column to books table
ALTER TABLE books ADD COLUMN price_range VARCHAR(20) DEFAULT NULL AFTER page_count;

-- Update the existing books with default price ranges based on common children's book pricing
-- This is just a starting point - you'll want to update these values manually for accuracy
UPDATE books SET price_range = '£5-£10' WHERE price_range IS NULL;

-- Create a list of standard price ranges for reference
-- These can be used in dropdown menus in the admin interface
CREATE TABLE IF NOT EXISTS price_ranges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    range_name VARCHAR(20) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert standard price ranges
INSERT INTO price_ranges (range_name, display_order) VALUES
('Under £5', 10),
('£5-£10', 20),
('£10-£15', 30),
('£15-£20', 40),
('Over £20', 50);

-- You can run this query to see all books with their new price range
-- SELECT directory_item_id, title, price_range FROM books JOIN directory_items ON books.directory_item_id = directory_items.id;
