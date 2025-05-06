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

// Log the request data for debugging
error_log("save-base64-image.php received data: " . json_encode([
    'filename' => $data['filename'] ?? 'not set',
    'alt_text' => $data['alt_text'] ?? 'not set',
    'prompt' => substr($data['prompt'] ?? 'not set', 0, 100) . '...'
]));

if (!$data || !isset($data['image_data']) || !isset($data['filename'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data. Required fields: image_data, filename']);
    exit();
}

// Include database connection
require_once '../../../admin/includes/db-connect.php';

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

    // Save thumbnail as WebP regardless of source format
    $result = imagewebp($thumbnailImage, $destPath, 85);

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
    $uploadDir = __DIR__ . '/../../../uploads/ai-generated/';
    $optimizedDir = __DIR__ . '/../../../uploads/optimized/';

    foreach ([$uploadDir, $optimizedDir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Create a meaningful filename from the alt text or prompt
    $baseFilename = '';

    // If we have alt text, use that for the filename
    if (!empty($data['alt_text'])) {
        // Extract story title if it's in the format "Story written by children: [Title]"
        if (preg_match('/Story written by children: ([^,]+)/', $data['alt_text'], $matches)) {
            $baseFilename = $matches[1];
        } else {
            // Otherwise use the first part of the alt text
            $baseFilename = substr($data['alt_text'], 0, 50);
        }
    }
    // If no alt text, try to extract a title from the prompt
    else if (!empty($data['prompt'])) {
        // Try to extract a title from the prompt
        if (preg_match('/Base this on: ([^,\.]+)/', $data['prompt'], $matches)) {
            $baseFilename = $matches[1];
        } else {
            // Use the first part of the prompt
            $baseFilename = substr($data['prompt'], 0, 50);
        }
    }

    // If we still don't have a filename, use the provided filename or default
    if (empty($baseFilename)) {
        $baseFilename = !empty($data['filename']) ? $data['filename'] : 'ai-generated-image';
    }

    // Convert to URL-friendly format
    $baseFilename = strtolower(trim($baseFilename));
    $baseFilename = preg_replace('/[^a-z0-9]+/', '-', $baseFilename);
    $baseFilename = trim($baseFilename, '-');

    // Add story-written-by-children suffix if it's a children's story
    if (strpos(strtolower($data['alt_text'] ?? ''), 'story written by children') !== false) {
        $baseFilename .= '-story-written-by-children';
    }

    // Add date and unique ID
    $filename = $baseFilename . '-' . date('Ymd') . '-' . uniqid();
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

    // Create thumbnail version (150x150)
    $thumbnailFilename = pathinfo($filename, PATHINFO_FILENAME) . '-thumbnail.webp';
    $thumbnailPath = $optimizedDir . $thumbnailFilename;
    createThumbnail($filepath, $thumbnailPath, 150, 150);

    // Create small version (300x300)
    $smallFilename = pathinfo($filename, PATHINFO_FILENAME) . '-small.webp';
    $smallPath = $optimizedDir . $smallFilename;
    createThumbnail($filepath, $smallPath, 300, 300);

    // Prepare the URLs (relative to the site root)
    $url = '/uploads/ai-generated/' . $filename;
    $thumbnailUrl = '/uploads/optimized/' . pathinfo($filename, PATHINFO_FILENAME) . '-thumbnail.webp';
    $smallUrl = '/uploads/optimized/' . pathinfo($filename, PATHINFO_FILENAME) . '-small.webp';

    // Create a meaningful alt text for SEO
    $altText = $data['alt_text'] ?? 'AI generated image';

    // If the alt text starts with "AI generated image:", extract the story title
    if (preg_match('/AI generated image: (.+)/', $altText, $matches)) {
        $promptText = $matches[1];

        // Extract story title if it's in the format "Base this on: [Title]"
        if (preg_match('/Base this on: ([^,\.]+)/', $promptText, $titleMatches)) {
            $storyTitle = trim($titleMatches[1]);

            // Check if it includes author information
            if (preg_match('/(.*?)\s+by\s+(.*?)(?:,\s+aged\s+(\d+))?(?:,\s+from\s+(.*?))?(?:,\s+(.*?))?/i', $storyTitle, $authorMatches)) {
                $title = trim($authorMatches[1]);
                $author = trim($authorMatches[2]);
                $age = isset($authorMatches[3]) ? trim($authorMatches[3]) : '';
                $location = isset($authorMatches[4]) ? trim($authorMatches[4]) : '';

                // Create a more SEO-friendly alt text
                $altText = "Story written by children: $title by $author";
                if (!empty($age)) {
                    $altText .= ", aged $age";
                }
                if (!empty($location)) {
                    $altText .= ", from $location";
                }
            } else {
                // Just use the title if no author info
                $altText = "Story written by children: $storyTitle";
            }
        }
    }

    // Check if the medium_url and large_url columns exist
    try {
        $stmt = $db->prepare("INSERT INTO media (filename, file_path, thumbnail_url, small_url, medium_url, large_url, file_type, file_size, alt_text, width, height, created_at, updated_at)
                              VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, NOW(), NOW())");
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
    } catch (PDOException $e) {
        // If the insert fails, try with fewer columns
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
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
        } else {
            // Re-throw the exception if it's not a column issue
            throw $e;
        }
    }

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
