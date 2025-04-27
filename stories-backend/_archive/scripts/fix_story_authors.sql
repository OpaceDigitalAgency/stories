-- First, check if the relationship already exists before trying to insert
-- This prevents the duplicate key error

-- Delete existing relationships for story 1 if any
DELETE FROM story_authors WHERE story_id = 1;

-- Insert relationship for story 1 with John Doe (ID 1)
INSERT INTO story_authors (story_id, author_id) VALUES (1, 1);

-- We don't need to modify story 2's relationship since it already exists
-- The error showed "Duplicate entry '2-2'" which means story 2 is already linked to author 2

-- Update the stories table to ensure proper flags for filtering
-- Make story 1 featured and sponsored
UPDATE stories SET featured = 1, is_sponsored = 1, is_self_published = 0, is_ai_enhanced = 0 WHERE id = 1;

-- Make story 2 self-published and AI-enhanced
UPDATE stories SET featured = 0, is_sponsored = 0, is_self_published = 1, is_ai_enhanced = 1 WHERE id = 2;