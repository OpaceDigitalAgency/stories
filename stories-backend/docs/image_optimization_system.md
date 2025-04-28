# Image Optimization System

This document describes the image optimization system implemented for Stories From The Web. The system provides a consistent approach to image optimization across all parts of the application.

## Overview

The image optimization system:

1. Creates multiple size variants of each image (thumbnail, small, medium, large)
2. Converts images to more efficient formats (JPG) where appropriate
3. Provides appropriate image sizes for different contexts (thumbnails, cards, full-width)
4. Ensures consistent optimization across all upload methods

## Components

### 1. Core Libraries

- **`includes/image_config.php`**: Defines standard image sizes and formats
- **`includes/image_optimizer.php`**: Contains modular functions for image processing

### 2. Database Schema

The `media` table has been extended with additional columns to store different image sizes:

```sql
ALTER TABLE media 
ADD COLUMN thumbnail_url VARCHAR(255) AFTER file_path,
ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url,
ADD COLUMN medium_url VARCHAR(255) AFTER small_url,
ADD COLUMN large_url VARCHAR(255) AFTER large_url;
```

### 3. API Integration

The API includes all image URLs in responses, allowing the frontend to choose the appropriate size:

```json
{
  "cover_url": "https://api.storiesfromtheweb.org/uploads/image.jpg",
  "cover_urls": {
    "thumbnail": "https://api.storiesfromtheweb.org/uploads/optimized/thumbnail-image.jpg",
    "small": "https://api.storiesfromtheweb.org/uploads/optimized/small-image.jpg",
    "medium": "https://api.storiesfromtheweb.org/uploads/optimized/medium-image.jpg",
    "large": "https://api.storiesfromtheweb.org/uploads/optimized/large-image.jpg"
  }
}
```

### 4. Frontend Integration

The frontend components use the appropriate image size for each context:

- **Card components**: Use small or thumbnail sizes
- **Detail pages**: Use large or medium sizes

## Standard Image Sizes

| Size      | Dimensions    | Use Case                                |
|-----------|---------------|----------------------------------------|
| thumbnail | 150x150       | Avatar thumbnails, tiny previews        |
| small     | 300x300       | Card thumbnails, small previews         |
| medium    | 640x640       | Medium-sized previews, list views       |
| large     | 1200x800      | Detail pages, full-width images         |
| original  | (unchanged)   | Original image (preserved if needed)    |

## Usage

### Optimizing Existing Media

To optimize all existing media files:

1. Run the database schema update script:
   ```
   https://api.storiesfromtheweb.org/public/update_media_schema.php
   ```

2. Run the media optimization script:
   ```
   https://api.storiesfromtheweb.org/public/fix_media_sizes.php
   ```

3. Update story cover URLs to use optimized images:
   ```
   Click the "Update Story Cover URLs" button on the fix_media_sizes.php page
   ```

### Integration with Upload Methods

The image optimization system is integrated with all upload methods:

1. **Manual uploads** via the admin interface
2. **Import scripts** for batch importing content
3. **API uploads** for programmatic content creation

## Technical Details

### Image Processing Libraries

The system uses the following image processing libraries, in order of preference:

1. **ImageMagick**: Provides the best quality and compression
2. **GD**: Used as a fallback if ImageMagick is not available

### Format Conversion

By default, the system converts images to JPG format for better compression. PNG transparency is preserved when needed.

### Optimization Process

1. Check if the image is already small enough (< 300KB)
2. Create multiple size variants using the appropriate library
3. Update the database with all variant URLs
4. Return the optimized URLs for use in the application

## Troubleshooting

### Missing Image Libraries

If neither ImageMagick nor GD is available, the system will fall back to using existing sized versions of images if available, or simply copy the original files without optimization.

To install the required libraries:

```bash
# For ImageMagick (preferred)
sudo apt-get install php-imagick

# For GD (alternative)
sudo apt-get install php-gd

# Then restart PHP
sudo systemctl restart php-fpm  # or apache2, depending on your setup
```

### Large Images Still Being Used

If large images are still being used in the frontend:

1. Check that the API is returning the `cover_urls` object
2. Verify that the frontend components are using the appropriate image sizes
3. Run the "Update Story Cover URLs" process again

## Future Improvements

- Add WebP format support for even better compression
- Implement lazy loading for images in the frontend
- Add responsive image support with srcset and sizes attributes