-- Final Cleanup for Reviews
-- This script performs a more aggressive cleanup of duplicate reviews
-- Run this in phpMyAdmin

-- Step 1: Fix reviewer names by removing asterisks and standardizing format
UPDATE reviews 
SET reviewer_name = TRIM(REPLACE(REPLACE(reviewer_name, '**', ''), '*', ''))
WHERE reviewer_name LIKE '%**%' OR reviewer_name LIKE '%*%';

-- Step 2: Create a temporary table to store the IDs of reviews to keep
CREATE TEMPORARY TABLE reviews_to_keep (
    id INT NOT NULL,
    book_id INT NOT NULL,
    clean_name VARCHAR(255),
    PRIMARY KEY (id)
);

-- Step 3: Insert the first review for each book and normalized reviewer name combination
INSERT INTO reviews_to_keep (id, book_id, clean_name)
SELECT 
    MIN(r.id) as id,
    r.book_id,
    LOWER(TRIM(reviewer_name)) as clean_name
FROM 
    reviews r
GROUP BY 
    r.book_id, 
    LOWER(TRIM(reviewer_name));

-- Step 4: Delete reviews that are not in the reviews_to_keep table
DELETE FROM reviews 
WHERE id NOT IN (SELECT id FROM reviews_to_keep);

-- Step 5: Drop the temporary table
DROP TEMPORARY TABLE IF EXISTS reviews_to_keep;

-- Step 6: Update book ratings
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

-- Step 7: Set ratings to 0 for books with no reviews
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

-- Step 8: Fix specific issues with reviewer names
-- Fix the specific issue with "## The Whizz Pop Chocolate Shop" reviewer
UPDATE reviews 
SET reviewer_name = 'Jessica'
WHERE reviewer_name LIKE '## The Whizz Pop Chocolate Shop%Jessica%';

-- Step 9: Fix "Review" as reviewer name
UPDATE reviews
SET reviewer_name = CONCAT('Anonymous ', id)
WHERE reviewer_name = 'Review';

-- Step 10: Final cleanup - remove any remaining duplicates
-- Create a new temporary table for the final cleanup
CREATE TEMPORARY TABLE final_reviews_to_keep (
    id INT NOT NULL,
    book_id INT NOT NULL,
    clean_name VARCHAR(255),
    PRIMARY KEY (id)
);

-- Insert the first review for each book and normalized reviewer name combination
INSERT INTO final_reviews_to_keep (id, book_id, clean_name)
SELECT 
    MIN(r.id) as id,
    r.book_id,
    LOWER(TRIM(reviewer_name)) as clean_name
FROM 
    reviews r
GROUP BY 
    r.book_id, 
    LOWER(TRIM(reviewer_name));

-- Delete reviews that are not in the final_reviews_to_keep table
DELETE FROM reviews 
WHERE id NOT IN (SELECT id FROM final_reviews_to_keep);

-- Drop the temporary table
DROP TEMPORARY TABLE IF EXISTS final_reviews_to_keep;

-- Final update of book ratings
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
