<?php
/**
 * Image Upload Handler
 *
 * Processes image uploads for the admin interface.
 * Features:
 * - File validation
 * - Image optimization
 * - Multiple size generation
 * - Database integration
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image optimizer with error handling
try {
    if (file_exists('../../includes/image_optimizer.php')) {
        require_once '../../includes/image_optimizer.php';
    } else {
        error_log("image_optimizer.php file not found in upload-image.php");
        // Define fallback functions to prevent errors
        if (!function_exists('createImageVariants')) {
            function createImageVariants($sourcePath, $destinationDir, $options = []) {
                error_log("createImageVariants function not available");
                return false;
            }
        }
        if (!function_exists('updateMediaRecord')) {
            function updateMediaRecord($db, $mediaId, $variants) {
                error_log("updateMediaRecord function not available");
                return false;
            }
        }
        if (!function_exists('getImageDimensions')) {
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
        }
    }
} catch (Exception $e) {
    error_log("Error including image_optimizer.php in upload-image.php: " . $e->getMessage());
}

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'url' => '',
    'dimensions' => ''
];

try {
    // Check if file was uploaded (support both 'file' from our component and 'upload' from CKEditor)
    $fileInputName = isset($_FILES['upload']) ? 'upload' : 'file';

    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred.');
    }

    // Get file info
    $file = $_FILES[$fileInputName];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];

    // Check if this is a CKEditor upload
    $isForEditor = isset($_POST['for_editor']) && $_POST['for_editor'] === 'true';

    // Get entity info
    $entityType = $_POST['entity_type'] ?? 'general';
    $entityId = $_POST['entity_id'] ?? '0';
    $fieldName = $_POST['field_name'] ?? '';
    $altText = $_POST['alt_text'] ?? '';

    // If no alt text provided, generate one from the filename
    if (empty($altText)) {
        $altText = pathinfo($fileName, PATHINFO_FILENAME);
        // Clean up the alt text - replace hyphens and underscores with spaces
        $altText = str_replace(['-', '_'], ' ', $altText);
        // Capitalize first letter of each word
        $altText = ucwords($altText);
    }

    // Validate file type
    if (strpos($fileType, 'image/') !== 0) {
        throw new Exception('Only image files are allowed.');
    }

    // Validate file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($fileSize > $maxSize) {
        throw new Exception('File size exceeds the maximum limit of 10MB.');
    }

    // Create upload directory
    $uploadDir = '../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create entity-specific directory if needed
    if ($entityType !== 'general') {
        $entityDir = $uploadDir . $entityType . 's/';
        if (!file_exists($entityDir)) {
            mkdir($entityDir, 0755, true);
        }
        $uploadDir = $entityDir;
    }

    // Generate unique filename
    $fileNameNew = uniqid('', true) . '_' . $fileName;
    $fileDestination = $uploadDir . $fileNameNew;

    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $fileDestination)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Get image dimensions
    $dimensions = getImageDimensions($fileDestination);
    $dimensionsStr = $dimensions ? $dimensions['width'] . 'x' . $dimensions['height'] : '';

    // Create optimized directory if it doesn't exist
    $optimizedDir = '../../uploads/optimized/';
    if (!is_dir($optimizedDir)) {
        mkdir($optimizedDir, 0755, true);
    }

    // Log the optimization attempt
    error_log("Attempting to optimize image: " . $fileDestination);

    // Optimize the image
    $variants = createImageVariants($fileDestination, $optimizedDir);

    if (!$variants) {
        error_log("Image optimization failed for: " . $fileDestination);

        // If optimization fails, use the original file
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $fileDestination);
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;

        // Save to media table
        $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $fileName,
            $fileDestination,
            $fileType,
            $fileSize,
            $altText
        ]);

        $mediaId = $db->lastInsertId();

        error_log("Created media record ID: " . $mediaId . " with original file: " . $url);
    } else {
        error_log("Image optimization successful. Variants created: " . implode(", ", array_keys($variants)));

        // Use the optimized medium size as the default
        $url = $variants['medium']['url'] ?? $variants['original']['url'];

        // Log the thumbnail URL
        if (isset($variants['thumbnail'])) {
            error_log("Thumbnail URL: " . $variants['thumbnail']['url']);
        }

        // Save to media table
        $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at, thumbnail_url) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)");
        $stmt->execute([
            $fileName,
            $url,
            'image/webp', // Assuming WebP conversion
            $variants['medium']['size'] ?? $fileSize,
            $altText,
            $variants['thumbnail']['url'] ?? null
        ]);

        $mediaId = $db->lastInsertId();
        error_log("Created media record ID: " . $mediaId . " with optimized file: " . $url);

        // Update the media record with optimized URLs
        $updateResult = updateMediaRecord($db, $mediaId, $variants);
        error_log("Media record update result: " . ($updateResult ? "Success" : "Failed"));

        // Check if the media table has the necessary columns
        try {
            $columnsQuery = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
            if ($columnsQuery->rowCount() === 0) {
                error_log("Media table is missing thumbnail_url column. Adding it now.");
                $db->exec("ALTER TABLE media ADD COLUMN thumbnail_url VARCHAR(255) AFTER file_path");
            }

            $columnsQuery = $db->query("SHOW COLUMNS FROM media LIKE 'small_url'");
            if ($columnsQuery->rowCount() === 0) {
                error_log("Media table is missing small_url column. Adding it now.");
                $db->exec("ALTER TABLE media ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url");
            }

            $columnsQuery = $db->query("SHOW COLUMNS FROM media LIKE 'medium_url'");
            if ($columnsQuery->rowCount() === 0) {
                error_log("Media table is missing medium_url column. Adding it now.");
                $db->exec("ALTER TABLE media ADD COLUMN medium_url VARCHAR(255) AFTER small_url");
            }

            $columnsQuery = $db->query("SHOW COLUMNS FROM media LIKE 'large_url'");
            if ($columnsQuery->rowCount() === 0) {
                error_log("Media table is missing large_url column. Adding it now.");
                $db->exec("ALTER TABLE media ADD COLUMN large_url VARCHAR(255) AFTER medium_url");
            }
        } catch (Exception $e) {
            error_log("Error checking/adding columns to media table: " . $e->getMessage());
        }
    }

    // Set response based on whether this is a CKEditor upload or not
    if ($isForEditor) {
        // Make sure the URL is absolute for CKEditor
        if (strpos($url, 'http') !== 0) {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $url = "$protocol://$host" . (strpos($url, '/') === 0 ? $url : "/$url");
        }

        // Log the URL for debugging
        error_log("CKEditor image upload URL: " . $url);

        // CKEditor expects a specific response format
        echo json_encode([
            'url' => $url,
            'mediaId' => $mediaId,
            'width' => $dimensions ? $dimensions['width'] : 0,
            'height' => $dimensions ? $dimensions['height'] : 0,
            'alt' => $altText
        ]);
        exit; // Exit early to avoid the standard response
    } else {
        // Standard component response
        $response['success'] = true;
        $response['message'] = 'File uploaded successfully.';
        $response['url'] = $url;
        $response['dimensions'] = $dimensionsStr;
        $response['media_id'] = $mediaId;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
