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

    // Try multiple approaches to get the document root
    $possibleDocRoots = [
        $_SERVER['DOCUMENT_ROOT'],
        realpath($_SERVER['DOCUMENT_ROOT']),
        dirname(dirname(dirname(__DIR__))), // Go up three levels from current file
        realpath(dirname(dirname(dirname(__DIR__)))), // Resolved absolute path
        dirname(__FILE__) . '/../../', // Relative to current file
        realpath(dirname(__FILE__) . '/../../'), // Absolute path relative to current file
    ];

    // Log all possible document roots for debugging
    foreach ($possibleDocRoots as $index => $root) {
        error_log("Possible document root $index: " . ($root ?: 'empty'));
    }

    // Log the absolute paths for debugging
    $absUploadDir = realpath(dirname(__FILE__) . '/../../uploads/') ?: dirname(__FILE__) . '/../../uploads/';
    $absOptimizedDir = realpath(dirname(__FILE__) . '/../../uploads/optimized/') ?: dirname(__FILE__) . '/../../uploads/optimized/';
    $absAiGeneratedDir = realpath(dirname(__FILE__) . '/../../uploads/ai-generated/') ?: dirname(__FILE__) . '/../../uploads/ai-generated/';

    // Try to find a working document root for the web-accessible path
    $webRoot = null;
    foreach ($possibleDocRoots as $root) {
        if (!empty($root) && is_dir($root)) {
            $testPath = $root . '/uploads';
            error_log("Testing web root path: $testPath");

            if (is_dir($testPath) || @mkdir($testPath, 0755, true)) {
                $webRoot = $root;
                error_log("Found working web root: $root");
                break;
            }
        }
    }

    // Log the web root for debugging
    error_log("Selected web root: " . ($webRoot ?: 'None found, using fallback'));

    error_log("Upload directory absolute path: $absUploadDir");
    error_log("Optimized directory absolute path: $absOptimizedDir");
    error_log("AI Generated directory absolute path: $absAiGeneratedDir");

    // Check if parent directory is writable
    $parentDir = dirname($absUploadDir);
    if (!is_writable($parentDir)) {
        error_log("Parent directory is not writable: $parentDir");
    }

    // Create directories with more detailed error handling
    foreach ([$uploadDir, $optimizedDir, $aiGeneratedDir] as $dir) {
        if (!is_dir($dir)) {
            error_log("Creating directory: $dir");
            if (!mkdir($dir, 0755, true)) {
                $error = error_get_last();
                error_log("Failed to create directory: $dir - Error: " . ($error['message'] ?? 'Unknown error'));

                // Try with absolute path as fallback
                $absDir = realpath(dirname(__FILE__) . '/../../') . '/' . basename($dir);
                error_log("Trying with absolute path: $absDir");
                if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
                    $error = error_get_last();
                    error_log("Failed to create directory with absolute path: $absDir - Error: " . ($error['message'] ?? 'Unknown error'));
                }
            }
        }

        // Check if directory is writable
        if (is_dir($dir) && !is_writable($dir)) {
            error_log("Directory exists but is not writable: $dir");
            // Try to make it writable
            chmod($dir, 0755);
        }
    }

    // Generate a unique filename with timestamp
    $timestamp = date('Ymd-His');
    $filename = 'ai-generated-' . $timestamp . '-' . uniqid() . '.png';

    // Try different paths to find one that works
    $filepath = $aiGeneratedDir . $filename;
    $absFilepath = $absAiGeneratedDir . '/' . $filename;

    // Log the file paths we're trying
    error_log("Trying to save AI image to relative path: $filepath");
    error_log("Trying to save AI image to absolute path: $absFilepath");

    // Check if the directory exists and is writable
    $saveDir = is_dir($aiGeneratedDir) && is_writable($aiGeneratedDir) ? $aiGeneratedDir : $absAiGeneratedDir;
    if (!is_dir($saveDir)) {
        error_log("Final save directory doesn't exist: $saveDir");
        // Try to create it one more time
        if (!mkdir($saveDir, 0755, true)) {
            throw new Exception("Cannot create directory for saving images: $saveDir");
        }
    }

    if (!is_writable($saveDir)) {
        error_log("Final save directory is not writable: $saveDir");
        // Try to make it writable
        chmod($saveDir, 0755);
        if (!is_writable($saveDir)) {
            throw new Exception("Directory exists but is not writable: $saveDir");
        }
    }

    // Set the final filepath
    $filepath = $saveDir . '/' . $filename;
    error_log("Final filepath for saving: $filepath");

    // Download the image with error context
    error_log("Downloading image from URL: $imageUrl");
    $imageData = @file_get_contents($imageUrl);
    if ($imageData === false) {
        $error = error_get_last();
        throw new Exception('Failed to download image from URL: ' . $imageUrl . ' - Error: ' . ($error['message'] ?? 'Unknown error'));
    }

    // Check if we got valid image data
    if (empty($imageData)) {
        throw new Exception('Downloaded image data is empty from URL: ' . $imageUrl);
    }

    error_log("Downloaded image data size: " . strlen($imageData) . " bytes");

    // Save the image to the server with error context
    error_log("Saving image to: $filepath");
    $bytesWritten = @file_put_contents($filepath, $imageData);
    if ($bytesWritten === false) {
        $error = error_get_last();
        throw new Exception('Failed to save image to server at path: ' . $filepath . ' - Error: ' . ($error['message'] ?? 'Unknown error'));
    }

    error_log("Successfully wrote $bytesWritten bytes to $filepath");

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
    // Always use a consistent web-accessible path format
    $relativeFilePath = '/uploads/ai-generated/' . $filename;

    // Create a full URL for testing
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $fullUrl = $protocol . $host . $relativeFilePath;

    error_log("Full URL for testing: $fullUrl");

    // Try to verify the file is accessible
    $fileExists = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $fileExists = ($httpCode == 200);
        error_log("CURL check for file accessibility: HTTP code $httpCode");
    } else {
        error_log("CURL not available for file accessibility check");
    }

    if (!$fileExists) {
        error_log("Warning: File may not be accessible via web. Check permissions and paths.");
    }

    error_log("Relative file path for database: $relativeFilePath");

    // Save to media library
    try {
        // Check database connection
        if (!$db) {
            throw new Exception("Database connection is not available");
        }

        // Check if the media table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'media'");
        if ($tableCheck->rowCount() === 0) {
            error_log("Media table does not exist, creating it");
            // Create the media table if it doesn't exist
            $db->exec("CREATE TABLE IF NOT EXISTS media (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL,
                alt_text VARCHAR(255),
                width INT,
                height INT,
                thumbnail_url VARCHAR(255),
                small_url VARCHAR(255),
                medium_url VARCHAR(255),
                large_url VARCHAR(255),
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )");
        }

        // Check if the width and height columns exist
        $columnCheck = $db->query("SHOW COLUMNS FROM media LIKE 'width'");
        if ($columnCheck->rowCount() === 0) {
            error_log("Width and height columns do not exist, adding them");
            $db->exec("ALTER TABLE media ADD COLUMN width INT AFTER alt_text");
            $db->exec("ALTER TABLE media ADD COLUMN height INT AFTER width");
        }

        // Check if the URL columns exist
        $columnCheck = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
        if ($columnCheck->rowCount() === 0) {
            error_log("URL columns do not exist, adding them");
            $db->exec("ALTER TABLE media ADD COLUMN thumbnail_url VARCHAR(255) AFTER height");
            $db->exec("ALTER TABLE media ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url");
            $db->exec("ALTER TABLE media ADD COLUMN medium_url VARCHAR(255) AFTER small_url");
            $db->exec("ALTER TABLE media ADD COLUMN large_url VARCHAR(255) AFTER medium_url");
        }

        // Prepare the SQL statement
        $sql = "INSERT INTO media (filename, file_path, file_type, file_size, alt_text, width, height, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        error_log("Preparing SQL: $sql");
        $stmt = $db->prepare($sql);

        if (!$stmt) {
            $error = $db->errorInfo();
            throw new Exception("Failed to prepare statement: " . ($error[2] ?? 'Unknown error'));
        }

        // Execute the statement
        error_log("Executing SQL with parameters: " . json_encode([
            $filename,
            $relativeFilePath,
            'image/png',
            $fileSize,
            $altText,
            $width,
            $height
        ]));

        $result = $stmt->execute([
            $filename,
            $relativeFilePath,
            'image/png',
            $fileSize,
            $altText,
            $width,
            $height
        ]);

        if (!$result) {
            $error = $stmt->errorInfo();
            throw new Exception("Failed to execute statement: " . ($error[2] ?? 'Unknown error'));
        }

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
        $response = [
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
        ];

        error_log("Sending success response: " . json_encode($response));
        echo json_encode($response);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $trace = $e->getTraceAsString();
    error_log("Save AI image error: " . $errorMessage);
    error_log("Error trace: " . $trace);

    // Check if we can get more error details
    $lastError = error_get_last();
    if ($lastError) {
        error_log("Last PHP error: " . json_encode($lastError));
    }

    // Return a detailed error response
    $response = [
        'success' => false,
        'error' => $errorMessage,
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'php_error' => $lastError ? $lastError['message'] : null
        ]
    ];

    error_log("Sending error response: " . json_encode($response));
    echo json_encode($response);
}
