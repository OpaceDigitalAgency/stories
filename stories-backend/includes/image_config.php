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
        'quality' => 85, // Higher quality for display images
        'extension' => 'jpg'
    ],
    'png' => [
        'mime' => 'image/png',
        'quality' => 9, // Maximum PNG compression
        'extension' => 'png'
    ],
    'webp' => [
        'mime' => 'image/webp',
        'quality' => 85, // Higher quality for display images
        'extension' => 'webp'
    ]
];

// Default format to convert to (for better compression)
$DEFAULT_CONVERT_FORMAT = 'webp';

// Image types that should remain in PNG format (cartoons, illustrations, etc.)
$PRESERVE_PNG_TYPES = ['cartoon', 'illustration', 'logo', 'text-heavy'];

// Maximum file size before optimization is required (in bytes)
$MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB - allow larger high-quality images

// Directory for optimized images
$OPTIMIZED_DIR = 'optimized';

// Function to get image size configuration
function getImageSizeConfig($size = 'medium') {
    global $IMAGE_SIZES;

    // Check if $IMAGE_SIZES is defined and is an array
    if (!isset($IMAGE_SIZES) || !is_array($IMAGE_SIZES) || empty($IMAGE_SIZES)) {
        error_log("IMAGE_SIZES is not defined or is empty in getImageSizeConfig. Using default sizes.");
        // Define default sizes
        $IMAGE_SIZES = [
            'original' => [
                'width' => null,
                'height' => null,
                'crop' => false
            ],
            'thumbnail' => [
                'width' => 150,
                'height' => 150,
                'crop' => true
            ],
            'small' => [
                'width' => 300,
                'height' => 300,
                'crop' => false
            ],
            'medium' => [
                'width' => 600,
                'height' => 600,
                'crop' => false
            ],
            'large' => [
                'width' => 1200,
                'height' => 1200,
                'crop' => false
            ]
        ];
    }

    // If size is null or invalid, use medium as default
    if ($size === null || !isset($IMAGE_SIZES[$size])) {
        return $IMAGE_SIZES['medium'];
    }

    return $IMAGE_SIZES[$size];
}

// Function to get image format configuration
function getImageFormatConfig($format = 'webp') {
    global $IMAGE_FORMATS;

    // Check if $IMAGE_FORMATS is defined and is an array
    if (!isset($IMAGE_FORMATS) || !is_array($IMAGE_FORMATS) || empty($IMAGE_FORMATS)) {
        error_log("IMAGE_FORMATS is not defined or is empty. Using default formats.");
        // Define default formats
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
    }

    // If format is null or invalid, use webp as default
    if ($format === null || !isset($IMAGE_FORMATS[$format])) {
        return $IMAGE_FORMATS['webp'];
    }

    return $IMAGE_FORMATS[$format];
}