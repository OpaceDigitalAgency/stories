-- Rename cover_image_url to cover_url in books table for consistency
ALTER TABLE books CHANGE COLUMN cover_image_url cover_url VARCHAR(255);

-- Update the save-directory-item.php script to use the new column name
-- This is just a comment for reference, the actual PHP code changes will be made separately
