-- Clear validation cache entries that might contain "12+" values
-- This will force fresh API calls without cached "12+" data

-- Clear all validation cache entries containing "12+"
DELETE FROM validation_cache 
WHERE cache_data LIKE '%12+%';

-- Clear all Google Books cache entries to force fresh API calls
DELETE FROM validation_cache 
WHERE cache_key LIKE '%google_books%';

-- Clear all book validation cache entries to force fresh validation
DELETE FROM validation_cache 
WHERE cache_key LIKE '%book_validation_%';

-- Show remaining cache entries count
SELECT 'Remaining cache entries:' as info, COUNT(*) as count FROM validation_cache;
