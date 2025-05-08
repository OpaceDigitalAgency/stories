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

// Get parameters
$imageUrl = $_POST['image_url'];
$altText = $_POST['alt_text'] ?? 'AI-generated image';

try {
    // Create upload directories if they don't exist
    $uploadDir = '../../uploads/';
    $optimizedDir = '../../uploads/optimized/';

    foreach ([$uploadDir, $optimizedDir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Generate a unique filename
    $filename = 'ai-generated-' . uniqid() . '.png';
    $filepath = $uploadDir . $filename;

    // Download the image
    $imageData = file_get_contents($imageUrl);
    if ($imageData === false) {
        throw new Exception('Failed to download image from URL');
    }

    // Save the image to the server
    if (file_put_contents($filepath, $imageData) === false) {
        throw new Exception('Failed to save image to server');
    }

    // Get image dimensions
    $imageSize = getimagesize($filepath);
    $width = $imageSize[0] ?? 0;
    $height = $imageSize[1] ?? 0;
    $fileSize = filesize($filepath);

    // Optimize the image
    $variants = createImageVariants($filepath, $optimizedDir);

    // Save to media library
    $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, width, height, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $filename,
        $filepath,
        'image/png',
        $fileSize,
        $altText,
        $width,
        $height
    ]);

    $mediaId = $db->lastInsertId();

    // Update the media record with optimized URLs if available
    if ($variants) {
        updateMediaRecord($db, $mediaId, $variants);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $mediaId,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => $filepath,
            'alt_text' => $altText,
            'width' => $width,
            'height' => $height
        ]
    ]);

} catch (Exception $e) {
    error_log("Save AI image error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
