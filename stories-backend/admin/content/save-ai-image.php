<?php
/**
 * Save AI Image to Media Library
 *
 * This script handles saving AI-generated images to the media library.
 * It accepts a URL and alt text, downloads the image, and saves it to the media library.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image optimizer
require_once '../../includes/image_optimizer.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['image_url']) || empty($_POST['image_url'])) {
    echo json_encode(['success' => false, 'error' => 'Image URL is required']);
    exit;
}

// Log the request for debugging
error_log("save-ai-image.php received request: " . json_encode([
    'image_url' => substr($_POST['image_url'], 0, 50) . '...',
    'alt_text' => $_POST['alt_text'] ?? 'not set'
]));

// Get parameters
$imageUrl = $_POST['image_url'];
$altText = $_POST['alt_text'] ?? 'AI-generated image';

try {
    // Create upload directories if they don't exist
    $uploadDir = '../../uploads/';
    $optimizedDir = '../../uploads/optimized/';
    $aiGeneratedDir = '../../uploads/ai-generated/';

    foreach ([$uploadDir, $optimizedDir, $aiGeneratedDir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                error_log("Failed to create directory: $dir");
            }
        }
    }

    // Generate a unique filename with timestamp
    $timestamp = date('Ymd-His');
    $filename = 'ai-generated-' . $timestamp . '-' . uniqid() . '.png';
    $filepath = $aiGeneratedDir . $filename;

    // Log the file path
    error_log("Saving AI image to: $filepath");

    // Download the image
    $imageData = file_get_contents($imageUrl);
    if ($imageData === false) {
        throw new Exception('Failed to download image from URL: ' . $imageUrl);
    }

    // Save the image to the server
    if (file_put_contents($filepath, $imageData) === false) {
        throw new Exception('Failed to save image to server at path: ' . $filepath);
    }

    // Get image dimensions
    $imageSize = getimagesize($filepath);
    if (!$imageSize) {
        throw new Exception('Failed to get image dimensions. The file may not be a valid image.');
    }

    $width = $imageSize[0] ?? 0;
    $height = $imageSize[1] ?? 0;
    $fileSize = filesize($filepath);

    // Log image details
    error_log("Image saved successfully. Size: {$width}x{$height}, {$fileSize} bytes");

    // Create optimized variants
    error_log("Creating optimized image variants...");
    $variants = createImageVariants($filepath, $optimizedDir);

    if ($variants) {
        error_log("Successfully created " . count($variants) . " image variants");
    } else {
        error_log("Failed to create image variants");
    }

    // Prepare file path for database - make it relative to web root
    $relativeFilePath = '/uploads/ai-generated/' . $filename;

    // Save to media library
    try {
        $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, width, height, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $filename,
            $relativeFilePath,
            'image/png',
            $fileSize,
            $altText,
            $width,
            $height
        ]);

        $mediaId = $db->lastInsertId();
        error_log("Image saved to media library with ID: $mediaId");

        // Update the media record with optimized URLs if available
        if ($variants) {
            if (updateMediaRecord($db, $mediaId, $variants)) {
                error_log("Updated media record with optimized variants");
            } else {
                error_log("Failed to update media record with optimized variants");
            }
        }

        // Return success response with proper URLs
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $mediaId,
                'filename' => $filename,
                'filepath' => $relativeFilePath,
                'url' => $relativeFilePath,
                'thumbnail_url' => $variants['thumbnail']['url'] ?? '',
                'small_url' => $variants['small']['url'] ?? '',
                'medium_url' => $variants['medium']['url'] ?? '',
                'large_url' => $variants['large']['url'] ?? '',
                'alt_text' => $altText,
                'width' => $width,
                'height' => $height
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Save AI image error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
