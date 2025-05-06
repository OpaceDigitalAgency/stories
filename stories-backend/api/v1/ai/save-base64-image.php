<?php
/**
 * Save Base64 Image API Endpoint
 *
 * This endpoint saves a base64-encoded image to the server and returns the URL.
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if (!$data || !isset($data['image_data']) || !isset($data['filename'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data. Required fields: image_data, filename']);
    exit();
}

// Include database connection
require_once '../../includes/db-connect.php';

/**
 * Create a thumbnail version of an image
 *
 * @param string $sourcePath Path to the source image
 * @param string $destPath Path to save the thumbnail
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @return bool True on success, false on failure
 */
function createThumbnail($sourcePath, $destPath, $width, $height) {
    // Get image type
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }

    $imageType = $imageInfo[2];

    // Create image resource based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    // Get original dimensions
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);

    // Create thumbnail image
    $thumbnailImage = imagecreatetruecolor($width, $height);

    // Preserve transparency for PNG images
    if ($imageType === IMAGETYPE_PNG) {
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);
        $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbnailImage, 0, 0, $width, $height, $transparent);
    }

    // Resize image
    imagecopyresampled(
        $thumbnailImage,
        $sourceImage,
        0, 0, 0, 0,
        $width, $height,
        $originalWidth, $originalHeight
    );

    // Save thumbnail
    $result = false;
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumbnailImage, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumbnailImage, $destPath, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumbnailImage, $destPath);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($thumbnailImage, $destPath, 85);
            break;
    }

    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($thumbnailImage);

    return $result;
}

try {
    // Extract base64 data - remove data:image/png;base64, prefix if present
    $base64Data = $data['image_data'];
    if (strpos($base64Data, ',') !== false) {
        $base64Data = explode(',', $base64Data)[1];
    }

    // Decode base64 data
    $imageData = base64_decode($base64Data);
    if ($imageData === false) {
        throw new Exception('Invalid base64 data');
    }

    // Create upload directories if they don't exist
    $uploadDir = __DIR__ . '/../../../public/uploads/ai-generated/';
    $thumbnailDir = $uploadDir . 'thumbnail/';
    $smallDir = $uploadDir . 'small/';

    foreach ([$uploadDir, $thumbnailDir, $smallDir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Sanitize filename and ensure it has .png extension
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($data['filename'], PATHINFO_FILENAME));
    $filename = $filename ?: 'ai-generated-image';
    $filename .= '-' . uniqid();
    $filename .= '.png';

    // Save the original image
    $filepath = $uploadDir . $filename;
    if (!file_put_contents($filepath, $imageData)) {
        throw new Exception('Failed to save image to server');
    }

    // Get image dimensions
    $imageSize = getimagesize($filepath);
    $width = $imageSize[0] ?? 0;
    $height = $imageSize[1] ?? 0;

    // Create thumbnail version (50x50)
    $thumbnailPath = $thumbnailDir . $filename;
    createThumbnail($filepath, $thumbnailPath, 50, 50);

    // Create small version (300x300)
    $smallPath = $smallDir . $filename;
    createThumbnail($filepath, $smallPath, 300, 300);

    // Prepare the URLs (relative to the site root)
    $url = '/uploads/ai-generated/' . $filename;
    $thumbnailUrl = '/uploads/ai-generated/thumbnail/' . $filename;
    $smallUrl = '/uploads/ai-generated/small/' . $filename;

    // Insert into media table
    $altText = $data['alt_text'] ?? 'AI generated image';
    $stmt = $db->prepare("INSERT INTO media (filename, file_path, thumbnail_url, small_url, file_type, file_size, alt_text, width, height, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $filename,
        $url,
        $thumbnailUrl,
        $smallUrl,
        'image/png',
        strlen($imageData),
        $altText,
        $width,
        $height
    ]);

    $mediaId = $db->lastInsertId();

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $mediaId,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'small_url' => $smallUrl,
            'filename' => $filename,
            'alt_text' => $altText,
            'width' => $width,
            'height' => $height
        ]
    ]);

} catch (Exception $e) {
    error_log("Save base64 image error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save image: ' . $e->getMessage()
    ]);
}
