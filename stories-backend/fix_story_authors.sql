-- First, clear existing story_authors relationships to avoid duplicates
DELETE FROM story_authors;

-- Insert relationships for each story
-- This assumes that stories with ID 1 should be authored by John Doe (ID 1)
-- and stories with ID 2 should be authored by Jane Smith (ID 2)
INSERT INTO story_authors (story_id, author_id) VALUES (1, 1);
INSERT INTO story_authors (story_id, author_id) VALUES (2, 2);

-- Update the stories table to ensure proper flags for filtering
-- Make story 1 featured and sponsored
UPDATE stories SET featured = 1, is_sponsored = 1, is_self_published = 0, is_ai_enhanced = 0 WHERE id = 1;

-- Make story 2 self-published and AI-enhanced
UPDATE stories SET featured = 0, is_sponsored = 0, is_self_published = 1, is_ai_enhanced = 1 WHERE id = 2;