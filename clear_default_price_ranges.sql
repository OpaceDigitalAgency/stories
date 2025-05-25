-- Clear the default price range values that were incorrectly set to '£5-£10' for all books
-- This will reset them to NULL so they can be properly enriched from APIs

-- First, create a backup of current price_range values (optional but recommended)
CREATE TABLE IF NOT EXISTS books_price_range_backup AS 
SELECT directory_item_id, price_range, NOW() as backup_date 
FROM books 
WHERE price_range IS NOT NULL;

-- Clear the default '£5-£10' values that were set by the migration script
-- Only clear values that are exactly '£5-£10' to avoid removing manually set values
UPDATE books 
SET price_range = NULL 
WHERE price_range = '£5-£10';

-- Verify the changes
SELECT 
    COUNT(*) as total_books,
    COUNT(price_range) as books_with_price_range,
    COUNT(*) - COUNT(price_range) as books_without_price_range
FROM books;

-- Show sample of books that still have price ranges (these were manually set)
SELECT directory_item_id, price_range 
FROM books 
WHERE price_range IS NOT NULL 
LIMIT 10;
