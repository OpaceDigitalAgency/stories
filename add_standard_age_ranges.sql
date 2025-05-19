-- Create a table for standard age ranges
CREATE TABLE IF NOT EXISTS age_ranges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    range_name VARCHAR(50) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert standard age ranges
INSERT INTO age_ranges (range_name, display_order) VALUES
('0-3 years', 10),
('3-5 years', 20),
('5-7 years', 30),
('7-9 years', 40),
('7-10 years', 50),
('8-12 years', 60),
('9-12 years', 70),
('9+ years', 80),
('10+ years', 90),
('12+ years', 100),
('12 And Up', 110),
('Unknown', 120),
('Adult', 130),
('Teen', 140),
('Young Adult', 150),
('12+', 160);

-- Note: We're not creating a separate table for age range tags
-- as we're using the existing tag system for genres and age ranges

-- Add a note to the database changelog if you have one
INSERT INTO database_changelog (change_description, applied_at)
VALUES ('Added standard age ranges table and tags', NOW())
ON DUPLICATE KEY UPDATE change_description = VALUES(change_description);
