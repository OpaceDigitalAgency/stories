-- SQL to add age and location fields to the authors table

-- Add age field (for child authors)
ALTER TABLE `authors` ADD COLUMN `age` TINYINT UNSIGNED NULL AFTER `author_type`;

-- Add location field (for all authors)
ALTER TABLE `authors` ADD COLUMN `location` VARCHAR(100) NULL AFTER `age`;

-- Add index on location for faster filtering
ALTER TABLE `authors` ADD INDEX `idx_location` (`location`);