-- Clean Duplicate Reviews SQL Script
-- This script identifies and removes duplicate reviews in the database
-- Run this script in phpMyAdmin or another SQL client

-- Create a temporary table to store the IDs of reviews to keep
CREATE TEMPORARY TABLE reviews_to_keep (
    id INT NOT NULL,
    book_id INT NOT NULL,
    clean_name VARCHAR(255),
    PRIMARY KEY (id)
);

-- Insert the first review for each book and reviewer name combination
INSERT INTO reviews_to_keep (id, book_id, clean_name)
SELECT 
    MIN(r.id) as id,
    r.book_id,
    TRIM(REGEXP_REPLACE(REGEXP_REPLACE(r.reviewer_name, '^\*\*', ''), '\*\*.*$', '')) as clean_name
FROM 
    reviews r
GROUP BY 
    r.book_id, 
    TRIM(REGEXP_REPLACE(REGEXP_REPLACE(r.reviewer_name, '^\*\*', ''), '\*\*.*$', ''));

-- Delete reviews that are not in the reviews_to_keep table
DELETE FROM reviews 
WHERE id NOT IN (SELECT id FROM reviews_to_keep);

-- Drop the temporary table
DROP TEMPORARY TABLE IF EXISTS reviews_to_keep;

-- Update book ratings
-- For each book, recalculate the average rating, review count, highest rating, and lowest rating
UPDATE directory_items d
SET 
    d.average_rating = (
        SELECT AVG(r.rating_normalised)
        FROM reviews r
        WHERE r.book_id = d.id
    ),
    d.review_count = (
        SELECT COUNT(*)
        FROM reviews r
        WHERE r.book_id = d.id
    ),
    d.highest_rating = (
        SELECT MAX(r.rating_normalised)
        FROM reviews r
        WHERE r.book_id = d.id
    ),
    d.lowest_rating = (
        SELECT MIN(r.rating_normalised)
        FROM reviews r
        WHERE r.book_id = d.id
    )
WHERE EXISTS (
    SELECT 1
    FROM reviews r
    WHERE r.book_id = d.id
);

-- Set lowest_rating to 0 for books with no reviews
UPDATE directory_items d
SET 
    d.average_rating = 0,
    d.review_count = 0,
    d.highest_rating = 0,
    d.lowest_rating = 0
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews r
    WHERE r.book_id = d.id
);
