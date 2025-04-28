<?php
/**
 * Image Configuration
 * 
 * Defines standard image sizes and formats for different contexts
 */

// Standard image sizes for different contexts
$IMAGE_SIZES = [
    'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    'small'     => ['width' => 300, 'height' => 300, 'crop' => false],
    'medium'    => ['width' => 640, 'height' => 640, 'crop' => false],
    'large'     => ['width' => 1200, 'height' => 800, 'crop' => false],
    'original'  => ['width' => null, 'height' => null, 'crop' => false]
];

// Image format configuration
$IMAGE_FORMATS = [
    'jpg' => [
        'mime' => 'image/jpeg',
        'quality' => 85,
        'extension' => 'jpg'
    ],
    'png' => [
        'mime' => 'image/png',
        'quality' => 9, // PNG compression level (0-9)
        'extension' => 'png'
    ],
    'webp' => [
        'mime' => 'image/webp',
        'quality' => 80,
        'extension' => 'webp'
    ]
];

// Default format to convert to (for better compression)
$DEFAULT_CONVERT_FORMAT = 'jpg';

// Maximum file size before optimization is required (in bytes)
$MAX_FILE_SIZE = 300 * 1024; // 300KB

// Directory for optimized images
$OPTIMIZED_DIR = 'optimized';

// Function to get image size configuration
function getImageSizeConfig($size = 'medium') {
    global $IMAGE_SIZES;
    return $IMAGE_SIZES[$size] ?? $IMAGE_SIZES['medium'];
}

// Function to get image format configuration
function getImageFormatConfig($format = 'jpg') {
    global $IMAGE_FORMATS;
    return $IMAGE_FORMATS[$format] ?? $IMAGE_FORMATS['jpg'];
}