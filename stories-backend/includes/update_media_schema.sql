-- Add columns for different image sizes to the media table
ALTER TABLE media 
ADD COLUMN thumbnail_url VARCHAR(255) AFTER file_path,
ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url,
ADD COLUMN medium_url VARCHAR(255) AFTER small_url,
ADD COLUMN large_url VARCHAR(255) AFTER medium_url;

-- Add indexes for faster lookups (separate indexes to avoid exceeding key length limit)
CREATE INDEX idx_media_file_path ON media (file_path);
CREATE INDEX idx_media_thumbnail_url ON media (thumbnail_url);
CREATE INDEX idx_media_small_url ON media (small_url);
CREATE INDEX idx_media_medium_url ON media (medium_url);
CREATE INDEX idx_media_large_url ON media (large_url);