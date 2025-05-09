<?php
/**
 * Image Optimizer Library
 *
 * A modular library for optimizing images with consistent behavior
 * across all parts of the application.
 */

// Include the image configuration with error handling
try {
    if (file_exists(__DIR__ . '/image_config.php')) {
        // Add debug log to track inclusion
        error_log("Including image_config.php from image_optimizer.php");
        require_once __DIR__ . '/image_config.php';
    } else {
        error_log("image_config.php file not found in image_optimizer.php");

        // Define fallback configuration
        $IMAGE_SIZES = [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'small'     => ['width' => 300, 'height' => 300, 'crop' => false],
            'medium'    => ['width' => 640, 'height' => 640, 'crop' => false],
            'large'     => ['width' => 1200, 'height' => 800, 'crop' => false],
            'original'  => ['width' => null, 'height' => null, 'crop' => false]
        ];

        $IMAGE_FORMATS = [
            'jpg' => [
                'mime' => 'image/jpeg',
                'quality' => 85,
                'extension' => 'jpg'
            ],
            'png' => [
                'mime' => 'image/png',
                'quality' => 9,
                'extension' => 'png'
            ],
            'webp' => [
                'mime' => 'image/webp',
                'quality' => 85,
                'extension' => 'webp'
            ]
        ];

        $DEFAULT_CONVERT_FORMAT = 'webp';
        $PRESERVE_PNG_TYPES = ['cartoon', 'illustration', 'logo', 'text-heavy'];
        $MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
        $OPTIMIZED_DIR = 'optimized';
    }
} catch (Exception $e) {
    error_log("Error including image_config.php: " . $e->getMessage());

    // Define fallback configuration
    $IMAGE_SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'small'     => ['width' => 300, 'height' => 300, 'crop' => false],
        'medium'    => ['width' => 640, 'height' => 640, 'crop' => false],
        'large'     => ['width' => 1200, 'height' => 800, 'crop' => false],
        'original'  => ['width' => null, 'height' => null, 'crop' => false]
    ];

    $IMAGE_FORMATS = [
        'jpg' => [
            'mime' => 'image/jpeg',
            'quality' => 85,
            'extension' => 'jpg'
        ],
        'png' => [
            'mime' => 'image/png',
            'quality' => 9,
            'extension' => 'png'
        ],
        'webp' => [
            'mime' => 'image/webp',
            'quality' => 85,
            'extension' => 'webp'
        ]
    ];

    $DEFAULT_CONVERT_FORMAT = 'webp';
    $PRESERVE_PNG_TYPES = ['cartoon', 'illustration', 'logo', 'text-heavy'];
    $MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    $OPTIMIZED_DIR = 'optimized';
}

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
 * Get image format configuration - only define if not already defined
 *
 * @param string $format Format identifier (jpg, png, webp)
 * @return array Format configuration
 */
if (!function_exists('getImageFormatConfig')) {
    function getImageFormatConfig($format) {
        global $IMAGE_FORMATS;

        // Return the format configuration if it exists
        if (isset($IMAGE_FORMATS[$format])) {
            return $IMAGE_FORMATS[$format];
        }

        // Default to WebP if format not found
        return $IMAGE_FORMATS['webp'];
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
function createOptimizedFilename($originalFilename, $size = 'medium', $format = 'webp') {
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
        $format = $options['format'] ?? 'webp';

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
        $format = $options['format'] ?? 'webp';

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

    // Get image info
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        error_log("Failed to get image info: $sourcePath");
        return false;
    }

    $origWidth = $imageInfo[0];
    $origHeight = $imageInfo[1];
    $type = $imageInfo[2];

    // Use the requested dimensions or maintain original size
    $width = $options['width'] ?? $origWidth;
    $height = $options['height'] ?? $origHeight;

    // If only width is specified, calculate height to maintain aspect ratio
    if ($options['width'] && !$options['height']) {
        $height = ($origHeight / $origWidth) * $width;
    }

    $quality = $options['quality'] ?? 85; // Use higher default quality
    $format = $options['format'] ?? 'webp';

    // Try ImageMagick first (best quality and compression)
    if (extension_loaded('imagick')) {
        try {
            error_log("Using ImageMagick for optimization");
            $imagick = new Imagick($sourcePath);

            // Strip metadata to reduce size
            $imagick->stripImage();

            // Always resize to 300px width
            $imagick->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);

            // Set format-specific options
            if ($format === 'jpg' || $imagick->getImageFormat() === 'JPEG') {
                $imagick->setImageFormat('JPEG');
                $imagick->setImageCompressionQuality($quality);
                $imagick->setInterlaceScheme(Imagick::INTERLACE_PLANE);
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            } else if ($format === 'png' || $imagick->getImageFormat() === 'PNG') {
                $imagick->setImageFormat('PNG');
                $imagick->setOption('png:compression-level', 9);
                $imagick->setOption('png:compression-strategy', 1);
                $imagick->setOption('png:exclude-chunk', 'all');
            } else if ($format === 'webp' || $imagick->getImageFormat() === 'WEBP') {
                $imagick->setImageFormat('WEBP');
                $imagick->setImageCompressionQuality($quality);
            }

            // Write the optimized image
            $imagick->writeImage($destinationPath);
            $imagick->destroy();

            error_log("Successfully optimized image with ImageMagick: " . filesize($destinationPath) . " bytes");
            return true;
        } catch (Exception $e) {
            error_log("ImageMagick optimization failed: " . $e->getMessage());
            // Fall through to GD
        }
    }

    // Try GD as fallback
    if (extension_loaded('gd')) {
        try {
            error_log("Using GD for optimization");

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
            $success = false;
            switch ($format) {
                case 'jpg':
                    $success = imagejpeg($destination, $destinationPath, $quality);
                    break;
                case 'png':
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
        } catch (Exception $e) {
            error_log("GD optimization failed: " . $e->getMessage());
        }
    }

    // If all optimization attempts fail, copy the original
    error_log("All optimization methods failed, copying without optimization");
    return copy($sourcePath, $destinationPath);
}

/**
 * Convert an image to WebP format
 *
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param int $quality WebP quality (0-100)
 * @return bool Success or failure
 */
function convertToWebP($sourcePath, $destinationPath, $quality = 85) {
    return resizeImage($sourcePath, $destinationPath, [
        'format' => 'webp',
        'quality' => $quality
    ]);
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
            // Create URL - use a more reliable method to generate relative paths
            $basePath = realpath(dirname(__FILE__) . '/../');
            $relativePath = str_replace($basePath, '', $variantPath);
            // Ensure the relative path starts with a single slash
            $relativePath = '/' . ltrim($relativePath, '/');

            // Use relative URLs instead of absolute URLs to avoid domain issues
            $url = $relativePath;

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
 * Create a thumbnail of an image
 *
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @param bool $crop Whether to crop the image to exact dimensions
 * @return bool Success or failure
 */
function createThumbnail($sourcePath, $destinationPath, $width = 150, $height = 150, $crop = true) {
    return resizeImage($sourcePath, $destinationPath, [
        'width' => $width,
        'height' => $height,
        'crop' => $crop,
        'quality' => 85,
        'format' => 'webp'
    ]);
}

/**
 * Optimize a single image
 *
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @param string $format Output format (jpg, png, webp)
 * @param int $quality Output quality
 * @return bool Success or failure
 */
function optimizeImage($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 600, $format = 'webp', $quality = 85) {
    return resizeImage($sourcePath, $destinationPath, [
        'width' => $maxWidth,
        'height' => $maxHeight,
        'crop' => false,
        'quality' => $quality,
        'format' => $format
    ]);
}

/**
 * Optimize a single image and return metadata
 *
 * @param string $sourcePath Source image path
 * @param string $destinationDir Destination directory
 * @param array $options Additional options
 * @return array|false Array with optimization results or false on failure
 */
function optimizeImageWithMetadata($sourcePath, $destinationDir, $options = []) {
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

        // Just return the original file info using a more reliable method
        $basePath = realpath(dirname(__FILE__) . '/../');
        $relativePath = str_replace($basePath, '', $sourcePath);
        $url = '/' . ltrim($relativePath, '/');

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
        // First check if the media table has the required columns
        $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'metadata'");
        $hasMetadataColumn = $stmt->rowCount() > 0;

        // Check for thumbnail_url column to determine schema version
        $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
        $hasLegacyColumns = $stmt->rowCount() > 0;

        // Determine the file type based on the format used
        $fileType = 'image/webp'; // Default to WebP since we're converting most images
        if (isset($variants['medium'])) {
            $ext = pathinfo($variants['medium']['path'], PATHINFO_EXTENSION);
            if ($ext === 'png') {
                $fileType = 'image/png';
            } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                $fileType = 'image/jpeg';
            }
        }

        // Get the original media record to preserve existing data
        $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $mediaRecord = $stmt->fetch();

        if (!$mediaRecord) {
            error_log("Media record not found: $mediaId");
            return false;
        }

        // If we have the metadata column, use it for storing variant URLs
        if ($hasMetadataColumn) {
            // Prepare metadata
            $metadata = [];

            // If we already have metadata, decode it
            if (!empty($mediaRecord['metadata'])) {
                $existingMetadata = json_decode($mediaRecord['metadata'], true);
                if (is_array($existingMetadata)) {
                    $metadata = $existingMetadata;
                }
            }

            // Add variant URLs to metadata
            foreach ($variants as $size => $info) {
                $metadata[$size] = $info['url'];
            }

            // Add dimensions if available
            if (isset($variants['original']) && function_exists('getimagesize')) {
                $dimensions = getimagesize($variants['original']['path']);
                if ($dimensions) {
                    $metadata['width'] = $dimensions[0];
                    $metadata['height'] = $dimensions[1];
                }
            }

            // Add alt text if not already present
            if (!isset($metadata['alt'])) {
                $metadata['alt'] = pathinfo($mediaRecord['filename'], PATHINFO_FILENAME);
            }

            // Prepare update statement
            $sql = "UPDATE media SET
                    file_path = :file_path,
                    file_size = :file_size,
                    file_type = :file_type,
                    metadata = :metadata
                    WHERE id = :id";

            $stmt = $db->prepare($sql);

            // Bind parameters
            $stmt->bindValue(':file_path', $variants['medium']['url'] ?? $variants['original']['url'] ?? $mediaRecord['file_path']);
            $stmt->bindValue(':file_size', $variants['medium']['size'] ?? $variants['original']['size'] ?? $mediaRecord['file_size']);
            $stmt->bindValue(':file_type', $fileType);
            $stmt->bindValue(':metadata', json_encode($metadata));
            $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);
        }
        // If we have legacy columns, use them
        else if ($hasLegacyColumns) {
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

            // Bind parameters
            $stmt->bindValue(':file_path', $variants['medium']['url'] ?? $variants['original']['url'] ?? $mediaRecord['file_path']);
            $stmt->bindValue(':file_size', $variants['medium']['size'] ?? $variants['original']['size'] ?? $mediaRecord['file_size']);
            $stmt->bindValue(':file_type', $fileType);
            $stmt->bindValue(':thumbnail_url', $variants['thumbnail']['url'] ?? $mediaRecord['thumbnail_url'] ?? '');
            $stmt->bindValue(':small_url', $variants['small']['url'] ?? $mediaRecord['small_url'] ?? '');
            $stmt->bindValue(':medium_url', $variants['medium']['url'] ?? $mediaRecord['medium_url'] ?? '');
            $stmt->bindValue(':large_url', $variants['large']['url'] ?? $mediaRecord['large_url'] ?? '');
            $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);
        }
        // If we don't have either schema, just update the basic fields
        else {
            // Prepare update statement
            $sql = "UPDATE media SET
                    file_path = :file_path,
                    file_size = :file_size,
                    file_type = :file_type
                    WHERE id = :id";

            $stmt = $db->prepare($sql);

            // Bind parameters
            $stmt->bindValue(':file_path', $variants['medium']['url'] ?? $variants['original']['url'] ?? $mediaRecord['file_path']);
            $stmt->bindValue(':file_size', $variants['medium']['size'] ?? $variants['original']['size'] ?? $mediaRecord['file_size']);
            $stmt->bindValue(':file_type', $fileType);
            $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);
        }

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

        // Connect to database using the existing connection if available
        try {
            global $db;

            // If $db is not available, try to include the database connection
            if (!isset($db) || !$db) {
                // Try to include the database connection file
                $dbConnectPath = dirname(__FILE__) . '/../admin/includes/db-connect.php';
                if (file_exists($dbConnectPath)) {
                    require_once $dbConnectPath;
                } else {
                    error_log("Database connection file not found: $dbConnectPath");
                    return $originalUrl;
                }
            }

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