-- Standardize Reading Levels and Age Ranges
-- Run these SQL commands in phpMyAdmin to create standardized lookup tables

-- 1. Create standard reading levels table
CREATE TABLE IF NOT EXISTS `standard_reading_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `age_group` varchar(20) NOT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `reading_stage` varchar(50) NOT NULL,
  `lexile_range` varchar(20) DEFAULT NULL,
  `typical_skills` text,
  `sort_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `age_group` (`age_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insert standard reading levels data
INSERT INTO `standard_reading_levels` (`age_group`, `school_year`, `reading_stage`, `lexile_range`, `typical_skills`, `sort_order`) VALUES
('0-12 months', NULL, 'Pre-literacy (Sensory)', 'N/A', 'Listening to voices, looking at pictures', 1),
('12-24 months', NULL, 'Pre-literacy (Naming)', 'N/A', 'Responding to stories, pointing at objects', 2),
('2-3 years', NULL, 'Pre-literacy (Mimicry)', 'BR', 'Repeating phrases, "reading" from memory', 3),
('3-4 years', NULL, 'Early Pre-reader', 'BR', 'Identifying letters, understanding sequences', 4),
('4-5 years', 'Reception', 'Beginning Reader', 'BR-120L', 'Introduction to phonics, basic sentences', 5),
('5-6 years', 'Year 1', 'Early Reader', '120L-220L', 'Development of decoding skills', 6),
('6-7 years', 'Year 2', 'Developing Reader', '220L-420L', 'Enhancement of fluency and comprehension', 7),
('7-8 years', 'Year 3', 'Transitional Reader', '420L-620L', 'Transition from learning to read to reading to learn', 8),
('8-9 years', 'Year 4', 'Fluent Reader', '620L-820L', 'Exposure to variety of genres', 9),
('9-10 years', 'Year 5', 'Fluent Reader', '820L-940L', 'More complex texts and analysis', 10),
('10-11 years', 'Year 6', 'Fluent Reader', '940L-1000L+', 'Advanced comprehension skills', 11),
('11-14 years', 'Years 7-9', 'Advanced Reader', '1000L-1100L+', 'Critical reading and text analysis', 12),
('14-16 years', 'Years 10-11', 'Advanced Reader', '1100L-1200L+', 'GCSE preparation, literature study', 13),
('16-18 years', 'Years 12-13', 'Advanced Reader', '1200L-1300L+', 'A-level analytical skills', 14),
('18+ years', 'Adult', 'Proficient Reader', '1300L-1600L+', 'Professional and academic reading', 15);

-- 3. Create standard age ranges table (if not exists)
CREATE TABLE IF NOT EXISTS `standard_age_ranges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `range_name` varchar(50) NOT NULL,
  `min_age_months` int(11) NOT NULL,
  `max_age_months` int(11) DEFAULT NULL,
  `description` text,
  `sort_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `range_name` (`range_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Insert standard age ranges data
INSERT INTO `standard_age_ranges` (`range_name`, `min_age_months`, `max_age_months`, `description`, `sort_order`) VALUES
('0-12 months', 0, 12, 'Babies - Sensory development stage', 1),
('12-24 months', 12, 24, 'Toddlers - Language emergence', 2),
('2-3 years', 24, 36, 'Early toddlers - Story mimicry', 3),
('3-4 years', 36, 48, 'Pre-school - Letter recognition', 4),
('4-5 years', 48, 60, 'Reception - Beginning phonics', 5),
('5-6 years', 60, 72, 'Year 1 - Early reading skills', 6),
('6-7 years', 72, 84, 'Year 2 - Developing fluency', 7),
('7-8 years', 84, 96, 'Year 3 - Transitional reading', 8),
('8-9 years', 96, 108, 'Year 4 - Fluent reading', 9),
('9-10 years', 108, 120, 'Year 5 - Complex texts', 10),
('10-11 years', 120, 132, 'Year 6 - Advanced comprehension', 11),
('11-14 years', 132, 168, 'Years 7-9 - Critical analysis', 12),
('14-16 years', 168, 192, 'Years 10-11 - GCSE level', 13),
('16-18 years', 192, 216, 'Years 12-13 - A-level', 14),
('18+ years', 216, NULL, 'Adult - Professional reading', 15);

-- 5. Update existing age_ranges table to use standard values (if table exists)
-- First, let's see what we have
SELECT 'Current age_ranges table contents:' as info;
SELECT * FROM age_ranges ORDER BY id;

-- 6. Clean up duplicate publishers - Harper Collins variations
SELECT 'Harper Collins variations:' as info;
SELECT id, name FROM authors WHERE LOWER(name) LIKE '%harper%collins%' ORDER BY name;

-- 7. Clean up duplicate publishers - Bloomsbury variations  
SELECT 'Bloomsbury variations:' as info;
SELECT id, name FROM authors WHERE LOWER(name) LIKE '%bloomsbury%' ORDER BY name;

-- 8. Clean up duplicate publishers - Scholastic variations
SELECT 'Scholastic variations:' as info;
SELECT id, name FROM authors WHERE LOWER(name) LIKE '%scholastic%' ORDER BY name;

-- 9. Show current reading level inconsistencies
SELECT 'Current reading level values:' as info;
SELECT reading_level, COUNT(*) as count 
FROM books 
WHERE reading_level IS NOT NULL AND reading_level != '' 
GROUP BY reading_level 
ORDER BY count DESC;

-- 10. Show books with missing publisher relationships
SELECT 'Books with missing publisher relationships:' as info;
SELECT COUNT(*) as total_missing
FROM books b 
JOIN directory_items di ON b.directory_item_id = di.id 
WHERE b.publisher IS NOT NULL 
AND b.publisher != '' 
AND b.publisher_id IS NULL;
