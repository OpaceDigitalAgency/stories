-- First, create a backup of the books table (optional but recommended)
CREATE TABLE IF NOT EXISTS books_backup AS SELECT * FROM books;

-- Remove the genre column from the books table
ALTER TABLE books DROP COLUMN genre;

-- Add a note to the database changelog if you have one
INSERT INTO database_changelog (change_description, applied_at) 
VALUES ('Removed genre column from books table as tags are used instead', NOW())
ON DUPLICATE KEY UPDATE change_description = VALUES(change_description);
