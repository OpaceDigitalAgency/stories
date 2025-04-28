-- Add columns for different image sizes to the media table
ALTER TABLE media 
ADD COLUMN thumbnail_url VARCHAR(255) AFTER file_path,
ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url,
ADD COLUMN medium_url VARCHAR(255) AFTER small_url,
ADD COLUMN large_url VARCHAR(255) AFTER large_url;

-- Add index for faster lookups
CREATE INDEX idx_media_urls ON media (file_path, thumbnail_url, small_url, medium_url, large_url);