<?php
/**
 * Bulk Image Upload Handler
 *
 * Processes multiple image uploads for the admin interface.
 * Features:
 * - Multiple file handling
 * - Image optimization
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
        error_log("image_optimizer.php file not found in bulk-upload.php");
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
    error_log("Error including image_optimizer.php in bulk-upload.php: " . $e->getMessage());
}

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'files' => []
];

try {
    // Check if files were uploaded
    if (!isset($_FILES['files']) && !isset($_FILES['files[]'])) {
        throw new Exception('No files uploaded.');
    }
    
    // Handle different naming conventions
    $filesKey = isset($_FILES['files[]']) ? 'files[]' : 'files';

    // Get entity info
    $entityType = $_POST['entity_type'] ?? 'general';

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

    // Create optimized directory if it doesn't exist
    $optimizedDir = '../../uploads/optimized/';
    if (!is_dir($optimizedDir)) {
        mkdir($optimizedDir, 0755, true);
    }

    // Process each file
    $fileCount = count($_FILES[$filesKey]['name']);
    $successCount = 0;

    for ($i = 0; $i < $fileCount; $i++) {
        // Get file info
        $fileName = $_FILES[$filesKey]['name'][$i];
        $fileTmpName = $_FILES[$filesKey]['tmp_name'][$i];
        $fileSize = $_FILES[$filesKey]['size'][$i];
        $fileError = $_FILES[$filesKey]['error'][$i];
        $fileType = $_FILES[$filesKey]['type'][$i];

        // Skip if there was an error
        if ($fileError !== UPLOAD_ERR_OK) {
            $response['files'][] = [
                'name' => $fileName,
                'success' => false,
                'message' => 'Upload error: ' . $fileError
            ];
            continue;
        }

        // Validate file type
        if (strpos($fileType, 'image/') !== 0) {
            $response['files'][] = [
                'name' => $fileName,
                'success' => false,
                'message' => 'Only image files are allowed.'
            ];
            continue;
        }

        // Validate file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($fileSize > $maxSize) {
            $response['files'][] = [
                'name' => $fileName,
                'success' => false,
                'message' => 'File size exceeds the maximum limit of 10MB.'
            ];
            continue;
        }

        // Generate unique filename
        $fileNameNew = uniqid('', true) . '_' . $fileName;
        $fileDestination = $uploadDir . $fileNameNew;

        // Move uploaded file
        if (!move_uploaded_file($fileTmpName, $fileDestination)) {
            $response['files'][] = [
                'name' => $fileName,
                'success' => false,
                'message' => 'Failed to move uploaded file.'
            ];
            continue;
        }

        // Get image dimensions
        $dimensions = getImageDimensions($fileDestination);
        $dimensionsStr = $dimensions ? $dimensions['width'] . 'x' . $dimensions['height'] : '';

        // Optimize the image
        $variants = createImageVariants($fileDestination, $optimizedDir);

        if (!$variants) {
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
                ''
            ]);

            $mediaId = $db->lastInsertId();

            $response['files'][] = [
                'name' => $fileName,
                'success' => true,
                'url' => $url,
                'dimensions' => $dimensionsStr,
                'media_id' => $mediaId,
                'optimized' => false
            ];
        } else {
            // Use the optimized medium size as the default
            $url = $variants['medium']['url'] ?? $variants['original']['url'];

            // Save to media table
            $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $fileName,
                $url,
                'image/webp', // Assuming WebP conversion
                $variants['medium']['size'] ?? $fileSize,
                ''
            ]);

            $mediaId = $db->lastInsertId();

            // Update the media record with optimized URLs
            updateMediaRecord($db, $mediaId, $variants);

            $response['files'][] = [
                'name' => $fileName,
                'success' => true,
                'url' => $url,
                'dimensions' => $dimensionsStr,
                'media_id' => $mediaId,
                'optimized' => true
            ];
        }

        $successCount++;
    }

    // Set overall response
    $response['success'] = $successCount > 0;
    $response['message'] = $successCount . ' of ' . $fileCount . ' files uploaded successfully.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
