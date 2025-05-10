<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

try {
    // Create the uploads/optimized directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../../uploads';
    $optimizedDir = $uploadsDir . '/optimized';
    
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    if (!is_dir($optimizedDir)) {
        mkdir($optimizedDir, 0755, true);
    }
    
    // Path for the default avatar
    $defaultAvatarPath = $optimizedDir . '/default-author-avatar.jpg';
    
    // Check if the file already exists
    if (file_exists($defaultAvatarPath)) {
        $response['message'] = "Default avatar already exists at: $defaultAvatarPath";
        $response['success'] = true;
        $response['debug']['file_exists'] = true;
        $response['debug']['file_path'] = $defaultAvatarPath;
    } else {
        // Create a simple default avatar image (a colored circle with initials)
        $width = 512;
        $height = 512;
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color (light blue)
        $bgColor = imagecolorallocate($image, 100, 150, 255);
        imagefill($image, 0, 0, $bgColor);
        
        // Draw a white circle
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, $width/2, $height/2, $width-40, $height-40, $white);
        
        // Add text "A" in the center
        $textColor = imagecolorallocate($image, 80, 80, 80);
        $fontSize = 200;
        $fontFile = __DIR__ . '/../assets/fonts/OpenSans-Bold.ttf';
        
        // If the font file doesn't exist, use the default font
        if (!file_exists($fontFile)) {
            // Draw text using built-in font
            $text = "A";
            $fontWidth = imagefontwidth(5) * strlen($text);
            $fontHeight = imagefontheight(5);
            imagestring($image, 5, ($width - $fontWidth) / 2, ($height - $fontHeight) / 2, $text, $textColor);
        } else {
            // Draw text using TrueType font
            $text = "A";
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            imagettftext($image, $fontSize, 0, ($width - $textWidth) / 2, ($height + $textHeight) / 2, $textColor, $fontFile, $text);
        }
        
        // Save the image
        imagejpeg($image, $defaultAvatarPath, 90);
        imagedestroy($image);
        
        // Create a thumbnail version
        $thumbnailPath = $optimizedDir . '/default-author-avatar-thumbnail.jpg';
        $thumbnailWidth = 150;
        $thumbnailHeight = 150;
        
        $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        $source = imagecreatefromjpeg($defaultAvatarPath);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbnailWidth, $thumbnailHeight, $width, $height);
        imagejpeg($thumbnail, $thumbnailPath, 90);
        imagedestroy($thumbnail);
        imagedestroy($source);
        
        // Add to media table
        $fileSize = filesize($defaultAvatarPath);
        $fileType = 'image/jpeg';
        $filePath = '/uploads/optimized/default-author-avatar.jpg';
        
        $stmt = $db->prepare("
            INSERT INTO media (
                filename, file_path, file_size, file_type,
                alt_text, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            'default-author-avatar.jpg',
            $filePath,
            $fileSize,
            $fileType,
            'Default author avatar'
        ]);
        
        $mediaId = $db->lastInsertId();
        
        $response['success'] = true;
        $response['message'] = "Default avatar created successfully at: $defaultAvatarPath and added to media table with ID: $mediaId";
        $response['debug']['file_path'] = $defaultAvatarPath;
        $response['debug']['thumbnail_path'] = $thumbnailPath;
        $response['debug']['media_id'] = $mediaId;
    }
} catch (Exception $e) {
    $response['message'] = "Error creating default avatar: " . $e->getMessage();
    $response['debug']['error'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
