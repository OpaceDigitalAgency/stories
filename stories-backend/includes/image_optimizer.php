<?php
/**
 * Image Optimizer Library
 * 
 * A modular library for optimizing images with consistent behavior
 * across all parts of the application.
 */

// Include the image configuration
require_once __DIR__ . '/image_config.php';

/**
 * Check if image libraries are available
 * 
 * @return array Associative array of available libraries
 */
function getAvailableImageLibraries() {
    $libraries = [
        'imagick' => extension_loaded('imagick'),
        'gd' => extension_loaded('gd')
    ];
    
    return $libraries;
}

/**
 * Get image type from file
 * 
 * @param string $path Path to the image file
 * @return string|null Image type (jpg, png, gif, etc.) or null if not an image
 */
function getImageType($path) {
    if (!file_exists($path)) {
        return null;
    }
    
    $info = getimagesize($path);
    if (!$info) {
        return null;
    }
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            return 'jpg';
        case 'image/png':
            return 'png';
        case 'image/gif':
            return 'gif';
        case 'image/webp':
            return 'webp';
        default:
            return null;
    }
}

/**
 * Get image dimensions
 * 
 * @param string $path Path to the image file
 * @return array|null Array with width and height, or null if not an image
 */
function getImageDimensions($path) {
    if (!file_exists($path)) {
        return null;
    }
    
    $info = getimagesize($path);
    if (!$info) {
        return null;
    }
    
    return [
        'width' => $info[0],
        'height' => $info[1]
    ];
}

/**
 * Create a unique filename for an optimized image
 * 
 * @param string $originalFilename Original filename
 * @param string $size Size identifier (thumbnail, small, medium, large)
 * @param string $format Format (jpg, png, webp)
 * @return string Unique filename
 */
function createOptimizedFilename($originalFilename, $size = 'medium', $format = 'jpg') {
    $pathInfo = pathinfo($originalFilename);
    $baseName = $pathInfo['filename'];
    
    // Extract the original descriptive part if it exists
    if (preg_match('/^[0-9a-f]+-(.+)$/', $baseName, $matches)) {
        // If there's a descriptive part after the random digits, use it
        $baseName = $matches[1];
    } else if (preg_match('/^img_[A-Za-z0-9]+$/', $baseName)) {
        // If it's just a random img_ name with no descriptive part, use a generic name
        // Check if we can get a descriptive name from the original URL
        if (isset($GLOBALS['current_media_filename']) && !empty($GLOBALS['current_media_filename'])) {
            $originalPathInfo = pathinfo($GLOBALS['current_media_filename']);
            $baseName = preg_replace('/^[0-9a-f]+-/', '', $originalPathInfo['filename']);
        } else {
            $baseName = 'image';
        }
    }
    
    // Clean up the basename - replace spaces with hyphens and remove special characters
    $baseName = preg_replace('/[^a-zA-Z0-9-]/', '-', $baseName);
    $baseName = preg_replace('/-+/', '-', $baseName); // Replace multiple hyphens with a single one
    $baseName = trim($baseName, '-'); // Remove hyphens from start and end
    
    // Create a SEO-friendly filename with size indicator but no random prefix
    return $baseName . '-' . $size . '.' . $format;
}

/**
 * Resize and optimize an image using ImageMagick
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param array $options Resize options
 * @return bool Success or failure
 */
function resizeWithImageMagick($sourcePath, $destinationPath, $options = []) {
    try {
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $crop = $options['crop'] ?? false;
        $quality = $options['quality'] ?? 85;
        $format = $options['format'] ?? 'jpg';
        
        $imagick = new Imagick($sourcePath);
        
        // Strip metadata to reduce size
        $imagick->stripImage();
        
        // Get original dimensions
        $origWidth = $imagick->getImageWidth();
        $origHeight = $imagick->getImageHeight();
        
        // Calculate new dimensions if needed
        if ($width && $height) {
            if ($crop) {
                // Crop to exact dimensions
                $imagick->cropThumbnailImage($width, $height);
            } else {
                // Resize maintaining aspect ratio
                $imagick->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);
            }
        }
        
        // Set format-specific options
        if ($format === 'jpg') {
            $imagick->setImageFormat('JPEG');
            $imagick->setImageCompressionQuality($quality);
            $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        } else if ($format === 'png') {
            $imagick->setImageFormat('PNG');
            $imagick->setOption('png:compression-level', 9);
            $imagick->setOption('png:compression-strategy', 1);
            $imagick->setOption('png:exclude-chunk', 'all');
        } else if ($format === 'webp') {
            $imagick->setImageFormat('WEBP');
            $imagick->setImageCompressionQuality($quality);
        }
        
        // Write the optimized image
        $imagick->writeImage($destinationPath);
        $imagick->destroy();
        
        return true;
    } catch (Exception $e) {
        error_log("ImageMagick optimization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Resize and optimize an image using GD
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param array $options Resize options
 * @return bool Success or failure
 */
function resizeWithGD($sourcePath, $destinationPath, $options = []) {
    try {
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;
        $crop = $options['crop'] ?? false;
        $quality = $options['quality'] ?? 85;
        $format = $options['format'] ?? 'jpg';
        
        // Get image info
        list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
        
        // Create source image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Calculate new dimensions
        $newWidth = $width ?? $origWidth;
        $newHeight = $height ?? $origHeight;
        
        if (!$crop && $width && $height) {
            // Maintain aspect ratio
            $ratio = min($newWidth / $origWidth, $newHeight / $origHeight);
            $newWidth = $origWidth * $ratio;
            $newHeight = $origHeight * $ratio;
        }
        
        // Create destination image
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize the image
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight, $origWidth, $origHeight
        );
        
        // Save the image
        $success = false;
        switch ($format) {
            case 'jpg':
                $success = imagejpeg($destination, $destinationPath, $quality);
                break;
            case 'png':
                $pngQuality = floor((100 - $quality) / 11.111111);
                $pngQuality = max(0, min(9, $pngQuality));
                $success = imagepng($destination, $destinationPath, $pngQuality);
                break;
            case 'webp':
                $success = imagewebp($destination, $destinationPath, $quality);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($destination);
        
        return $success;
    } catch (Exception $e) {
        error_log("GD optimization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Resize and optimize an image
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param array $options Resize options
 * @return bool Success or failure
 */
function resizeImage($sourcePath, $destinationPath, $options = []) {
    // Check if the source file exists
    if (!file_exists($sourcePath)) {
        error_log("Source file not found: $sourcePath");
        return false;
    }
    
    // Get available libraries
    $libraries = getAvailableImageLibraries();
    
    // Use GD since it's available
    if ($libraries['gd']) {
        error_log("Using GD library for image optimization");
        
        // Get image info
        list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
        
        // Create source image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                error_log("Unsupported image type: $type");
                return false;
        }
        
        if (!$source) {
            error_log("Failed to create source image resource");
            return false;
        }
        
        // Calculate new dimensions
        $width = $options['width'] ?? $origWidth;
        $height = $options['height'] ?? $origHeight;
        $quality = $options['quality'] ?? 85;
        
        // Create destination image
        $destination = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $width, $height, $transparent);
        }
        
        // Resize the image
        imagecopyresampled(
            $destination, $source,
            0, 0, 0, 0,
            $width, $height, $origWidth, $origHeight
        );
        
        // Save with maximum compression
        $format = $options['format'] ?? 'jpg';
        $success = false;
        
        switch ($format) {
            case 'jpg':
                $success = imagejpeg($destination, $destinationPath, $quality);
                break;
            case 'png':
                // Use maximum PNG compression (9)
                $success = imagepng($destination, $destinationPath, 9);
                break;
            case 'webp':
                $success = imagewebp($destination, $destinationPath, $quality);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($destination);
        
        if ($success) {
            error_log("Successfully optimized image with GD: " . filesize($destinationPath) . " bytes");
            return true;
        }
    }
    
    error_log("No image libraries available, copying without optimization");
    return copy($sourcePath, $destinationPath);
}

/**
 * Convert an image to JPG format
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param int $quality JPEG quality (0-100)
 * @return bool Success or failure
 */
function convertToJpg($sourcePath, $destinationPath, $quality = 85) {
    return resizeImage($sourcePath, $destinationPath, [
        'format' => 'jpg',
        'quality' => $quality
    ]);
}

/**
 * Create multiple size variants of an image
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationDir Destination directory
 * @param array $options Additional options
 * @return array|false Array of variant URLs or false on failure
 */
function createImageVariants($sourcePath, $destinationDir, $options = []) {
    global $IMAGE_SIZES, $DEFAULT_CONVERT_FORMAT;
    
    // Ensure destination directory exists
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    
    // Get image type and dimensions
    $imageType = getImageType($sourcePath);
    if (!$imageType) {
        error_log("Not a valid image: $sourcePath");
        return false;
    }
    
    $dimensions = getImageDimensions($sourcePath);
    if (!$dimensions) {
        error_log("Could not get image dimensions: $sourcePath");
        return false;
    }
    
    // Determine if we should convert format
    $convertFormat = $options['convert_format'] ?? $DEFAULT_CONVERT_FORMAT;
    $originalFilename = basename($sourcePath);
    
    $variants = [];
    
    // Create each size variant
    foreach ($IMAGE_SIZES as $size => $sizeConfig) {
        // Skip original size if not needed
        if ($size === 'original' && !($options['include_original'] ?? true)) {
            continue;
        }
        
        // Determine output format
        $outputFormat = $convertFormat;
        if ($size === 'original') {
            $outputFormat = $imageType; // Keep original format for original size
        }
        
        // Get format config
        $formatConfig = getImageFormatConfig($outputFormat);
        
        // Create unique filename
        $variantFilename = createOptimizedFilename($originalFilename, $size, $formatConfig['extension']);
        
        // Ensure no double slashes in the path
        $destinationDir = rtrim($destinationDir, '/');
        $variantPath = $destinationDir . '/' . $variantFilename;
        
        // Set resize options
        $resizeOptions = [
            'width' => $sizeConfig['width'],
            'height' => $sizeConfig['height'],
            'crop' => $sizeConfig['crop'],
            'format' => $outputFormat,
            'quality' => $formatConfig['quality']
        ];
        
        // For original size, just convert format if needed
        if ($size === 'original') {
            $resizeOptions['width'] = null;
            $resizeOptions['height'] = null;
        }
        
        // Resize and optimize
        $success = resizeImage($sourcePath, $variantPath, $resizeOptions);
        
        if ($success) {
            // Create URL
            $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $variantPath);
            // Ensure the relative path starts with a single slash
            $relativePath = '/' . ltrim($relativePath, '/');
            $url = 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
            
            $variants[$size] = [
                'path' => $variantPath,
                'url' => $url,
                'size' => filesize($variantPath)
            ];
        }
    }
    
    return $variants;
}

/**
 * Optimize a single image
 * 
 * @param string $sourcePath Source image path
 * @param string $destinationDir Destination directory
 * @param array $options Additional options
 * @return array|false Array with optimization results or false on failure
 */
function optimizeImage($sourcePath, $destinationDir, $options = []) {
    global $MAX_FILE_SIZE;
    
    // Check if the source file exists
    if (!file_exists($sourcePath)) {
        error_log("Source file not found: $sourcePath");
        return false;
    }
    
    // Get file size
    $fileSize = filesize($sourcePath);
    
    // Skip if file is already small enough and not forced
    if ($fileSize < $MAX_FILE_SIZE && !($options['force'] ?? false)) {
        error_log("File is already small enough: $sourcePath ($fileSize bytes)");
        
        // Just return the original file info
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $sourcePath);
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
        
        return [
            'path' => $sourcePath,
            'url' => $url,
            'size' => $fileSize,
            'optimized' => false
        ];
    }
    
    // Create variants
    $variants = createImageVariants($sourcePath, $destinationDir, $options);
    
    if (!$variants) {
        return false;
    }
    
    // Return the medium size as the default optimized version
    return $variants['medium'] ?? $variants['original'] ?? false;
}

/**
 * Update media record in database with optimized URLs
 * 
 * @param PDO $db Database connection
 * @param int $mediaId Media ID
 * @param array $variants Image variants
 * @return bool Success or failure
 */
function updateMediaRecord($db, $mediaId, $variants) {
    try {
        // Prepare update statement
        $sql = "UPDATE media SET
                file_path = :file_path,
                file_size = :file_size,
                file_type = :file_type,
                thumbnail_url = :thumbnail_url,
                small_url = :small_url,
                medium_url = :medium_url,
                large_url = :large_url
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        
        // Determine the file type based on the format used
        $fileType = 'image/jpeg'; // Default to JPEG since we're converting most images
        if (isset($variants['medium'])) {
            $ext = pathinfo($variants['medium']['path'], PATHINFO_EXTENSION);
            if ($ext === 'png') {
                $fileType = 'image/png';
            } elseif ($ext === 'webp') {
                $fileType = 'image/webp';
            }
        }
        
        // Bind parameters
        $stmt->bindValue(':file_path', $variants['medium']['url'] ?? $variants['original']['url'] ?? '');
        $stmt->bindValue(':file_size', $variants['medium']['size'] ?? $variants['original']['size'] ?? 0);
        $stmt->bindValue(':file_type', $fileType);
        $stmt->bindValue(':thumbnail_url', $variants['thumbnail']['url'] ?? '');
        $stmt->bindValue(':small_url', $variants['small']['url'] ?? '');
        $stmt->bindValue(':medium_url', $variants['medium']['url'] ?? '');
        $stmt->bindValue(':large_url', $variants['large']['url'] ?? '');
        $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);
        
        // Execute the statement
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error updating media record: " . $e->getMessage());
        return false;
    }
}

/**
 * Get optimized image URL for a specific size
 * 
 * @param string $originalUrl Original image URL
 * @param string $size Size identifier (thumbnail, small, medium, large)
 * @return string URL for the requested size or original URL if not available
 */
function getOptimizedImageUrl($originalUrl, $size = 'medium') {
    // Extract the media ID from the URL if possible
    if (preg_match('/\/media\/(\d+)\//', $originalUrl, $matches)) {
        $mediaId = $matches[1];
        
        // Connect to database
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                'stories_user',
                '$tw1cac3*sOt',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            
            // Get the media record
            $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
            $stmt->execute([$mediaId]);
            $media = $stmt->fetch();
            
            if ($media) {
                // Return the requested size URL if available
                $sizeUrl = $media[$size . '_url'] ?? null;
                if ($sizeUrl) {
                    return $sizeUrl;
                }
            }
        } catch (PDOException $e) {
            error_log("Database error getting optimized URL: " . $e->getMessage());
        }
    }
    
    // If we can't get an optimized URL, return the original
    return $originalUrl;
}