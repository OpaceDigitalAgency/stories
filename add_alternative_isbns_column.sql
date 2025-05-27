-- Add alternative_isbns column to books table
-- This stores comma-separated alternative ISBNs from OpenLibrary data

ALTER TABLE `books` ADD COLUMN `alternative_isbns` TEXT DEFAULT NULL COMMENT 'Comma-separated list of alternative ISBNs from OpenLibrary';