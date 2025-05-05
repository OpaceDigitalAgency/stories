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

// Include image optimizer
require_once '../../includes/image_optimizer.php';

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
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred.');
    }
    
    // Get file info
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    // Get entity info
    $entityType = $_POST['entity_type'] ?? 'general';
    $entityId = $_POST['entity_id'] ?? '0';
    $fieldName = $_POST['field_name'] ?? '';
    
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
    $optimizedDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
    if (!is_dir($optimizedDir)) {
        mkdir($optimizedDir, 0755, true);
    }
    
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
    }
    
    // Set response
    $response['success'] = true;
    $response['message'] = 'File uploaded successfully.';
    $response['url'] = $url;
    $response['dimensions'] = $dimensionsStr;
    $response['media_id'] = $mediaId;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
