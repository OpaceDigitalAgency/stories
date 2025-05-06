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
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../../public/uploads/ai-generated/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Sanitize filename and ensure it has .png extension
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($data['filename'], PATHINFO_FILENAME));
    $filename = $filename ?: 'ai-generated-image';
    $filename .= '-' . uniqid();
    $filename .= '.png';
    
    // Save the image
    $filepath = $uploadDir . $filename;
    if (!file_put_contents($filepath, $imageData)) {
        throw new Exception('Failed to save image to server');
    }
    
    // Get image dimensions
    $imageSize = getimagesize($filepath);
    $width = $imageSize[0] ?? 0;
    $height = $imageSize[1] ?? 0;
    
    // Prepare the URL (relative to the site root)
    $url = '/uploads/ai-generated/' . $filename;
    
    // Insert into media table
    $altText = $data['alt_text'] ?? 'AI generated image';
    $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, width, height, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $filename,
        $url,
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
