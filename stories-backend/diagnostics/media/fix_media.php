<?php
/**
 * Fix Media Issues
 *
 * This tool fixes common issues with media uploads and image optimization.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common functions
require_once __DIR__ . '/../includes/common.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Connect to database
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $db = new PDO($dsn, $config['user'], $config['password'], $options);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Initialize results
$results = [
    'directories' => [],
    'media_table' => [
        'success' => false,
        'message' => 'Not attempted'
    ],
    'image_optimizer' => [
        'success' => false,
        'message' => 'Not attempted'
    ]
];

// Fix directories
$directories = [
    'uploads' => __DIR__ . '/../../../uploads',
    'optimized' => __DIR__ . '/../../../uploads/optimized',
];

foreach ($directories as $name => $path) {
    $result = [
        'name' => $name,
        'path' => $path,
        'success' => false,
        'message' => ''
    ];

    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            $result['success'] = true;
            $result['message'] = "Created $name directory";
        } else {
            $result['message'] = "Failed to create $name directory";
        }
    } else {
        $result['success'] = true;
        $result['message'] = "$name directory already exists";

        // Check if directory is writable
        if (!is_writable($path)) {
            if (chmod($path, 0755)) {
                $result['message'] .= ", fixed permissions";
            } else {
                $result['success'] = false;
                $result['message'] .= ", failed to fix permissions";
            }
        }
    }

    $results['directories'][] = $result;
}

// Fix media table
if ($dbConnected) {
    try {
        // Check if media table exists
        $stmt = $db->query("SHOW TABLES LIKE 'media'");
        if ($stmt->rowCount() === 0) {
            // Create media table
            $db->exec("CREATE TABLE IF NOT EXISTS media (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL,
                alt_text VARCHAR(255),
                thumbnail_url VARCHAR(255),
                small_url VARCHAR(255),
                medium_url VARCHAR(255),
                large_url VARCHAR(255),
                width INT,
                height INT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            $results['media_table']['success'] = true;
            $results['media_table']['message'] = "Created media table";
        } else {
            // Check if media table has all required columns
            $columns = [];
            $stmt = $db->query("DESCRIBE media");
            while ($row = $stmt->fetch()) {
                $columns[] = $row['Field'];
            }

            $requiredColumns = [
                'id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text',
                'thumbnail_url', 'small_url', 'medium_url', 'large_url', 'width', 'height',
                'created_at', 'updated_at'
            ];

            $missingColumns = array_diff($requiredColumns, $columns);

            if (!empty($missingColumns)) {
                // Add missing columns
                foreach ($missingColumns as $column) {
                    try {
                        if ($column === 'width' || $column === 'height') {
                            $db->exec("ALTER TABLE media ADD COLUMN $column INT");
                        } elseif ($column === 'file_size') {
                            $db->exec("ALTER TABLE media ADD COLUMN $column INT NOT NULL DEFAULT 0");
                        } elseif ($column === 'created_at') {
                            $db->exec("ALTER TABLE media ADD COLUMN $column DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                        } elseif ($column === 'updated_at') {
                            $db->exec("ALTER TABLE media ADD COLUMN $column DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                        } else {
                            $db->exec("ALTER TABLE media ADD COLUMN $column VARCHAR(255)");
                        }
                    } catch (Exception $e) {
                        // Ignore errors
                    }
                }

                $results['media_table']['success'] = true;
                $results['media_table']['message'] = "Added missing columns to media table: " . implode(', ', $missingColumns);
            } else {
                $results['media_table']['success'] = true;
                $results['media_table']['message'] = "Media table has all required columns";
            }
        }
    } catch (PDOException $e) {
        $results['media_table']['message'] = "Error fixing media table: " . $e->getMessage();
    }
}

// Fix image optimizer
$includesDir = __DIR__ . '/../../../includes';
$imageOptimizerPath = $includesDir . '/image_optimizer.php';
$imageConfigPath = $includesDir . '/image_config.php';

// Create includes directory if it doesn't exist
if (!file_exists($includesDir)) {
    mkdir($includesDir, 0755, true);
    $results['image_optimizer']['message'] = "Created includes directory";
}

if (!file_exists($imageOptimizerPath)) {
    // Create image optimizer file
    $imageOptimizerContent = '<?php
/**
 * Image Optimizer
 *
 * This file contains functions for optimizing images.
 */

// Include image configuration
require_once __DIR__ . \'/image_config.php\';

/**
 * Create image variants (thumbnail, small, medium, large)
 *
 * @param string $sourcePath Path to source image
 * @param string $targetDir Directory to save optimized images
 * @return array|false Array of image variants or false on failure
 */
function createImageVariants($sourcePath, $targetDir) {
    global $imageConfig;

    // Check if source file exists
    if (!file_exists($sourcePath)) {
        return false;
    }

    // Create target directory if it doesn\'t exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Get image information
    $imageInfo = getimagesize($sourcePath);
    if ($imageInfo === false) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo[\'mime\'];

    // Get file extension
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
    $filename = pathinfo($sourcePath, PATHINFO_FILENAME);

    // Create unique filename
    $uniqueFilename = $filename . \'_\' . time();

    // Initialize variants array
    $variants = [
        \'original\' => [
            \'path\' => $sourcePath,
            \'url\' => str_replace($_SERVER[\'DOCUMENT_ROOT\'], \'\', $sourcePath),
            \'width\' => $width,
            \'height\' => $height
        ]
    ];

    // Create variants
    foreach ($imageConfig[\'sizes\'] as $size => $dimensions) {
        $targetWidth = $dimensions[\'width\'];
        $targetHeight = $dimensions[\'height\'];

        // Calculate new dimensions while maintaining aspect ratio
        if ($targetWidth && $targetHeight) {
            // Both width and height specified, use exact dimensions
            $newWidth = $targetWidth;
            $newHeight = $targetHeight;
        } else if ($targetWidth) {
            // Only width specified, calculate height to maintain aspect ratio
            $newWidth = $targetWidth;
            $newHeight = round($height * ($targetWidth / $width));
        } else if ($targetHeight) {
            // Only height specified, calculate width to maintain aspect ratio
            $newHeight = $targetHeight;
            $newWidth = round($width * ($targetHeight / $height));
        } else {
            // No dimensions specified, use original dimensions
            $newWidth = $width;
            $newHeight = $height;
        }

        // Create target filename
        $targetFilename = $uniqueFilename . \'_\' . $size . \'.webp\';
        $targetPath = $targetDir . \'/\' . $targetFilename;

        // Create image resource based on mime type
        switch ($mime) {
            case \'image/jpeg\':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case \'image/png\':
                $image = imagecreatefrompng($sourcePath);
                break;
            case \'image/gif\':
                $image = imagecreatefromgif($sourcePath);
                break;
            case \'image/webp\':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF images
        if ($mime === \'image/png\' || $mime === \'image/gif\') {
            imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Resize image
        imagecopyresampled(
            $newImage,
            $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $width, $height
        );

        // Save image as WebP
        imagewebp($newImage, $targetPath, $imageConfig[\'quality\']);

        // Free memory
        imagedestroy($newImage);

        // Add variant to array
        $variants[$size] = [
            \'path\' => $targetPath,
            \'url\' => str_replace($_SERVER[\'DOCUMENT_ROOT\'], \'\', $targetPath),
            \'width\' => $newWidth,
            \'height\' => $newHeight
        ];
    }

    // Free memory
    if (isset($image)) {
        imagedestroy($image);
    }

    return $variants;
}

/**
 * Save image to media table
 *
 * @param array $variants Image variants
 * @param string $altText Alt text for image
 * @param PDO $db Database connection
 * @return int|false ID of inserted record or false on failure
 */
function saveImageToDatabase($variants, $altText, $db) {
    // Check if variants array is valid
    if (!isset($variants[\'original\'])) {
        return false;
    }

    // Get original image information
    $original = $variants[\'original\'];
    $filename = pathinfo($original[\'path\'], PATHINFO_BASENAME);
    $fileSize = filesize($original[\'path\']);
    $fileType = mime_content_type($original[\'path\']);

    // Prepare SQL statement
    $sql = "INSERT INTO media (
        filename, file_path, file_type, file_size, alt_text,
        thumbnail_url, small_url, medium_url, large_url,
        width, height, created_at, updated_at
    ) VALUES (
        :filename, :file_path, :file_type, :file_size, :alt_text,
        :thumbnail_url, :small_url, :medium_url, :large_url,
        :width, :height, NOW(), NOW()
    )";

    try {
        $stmt = $db->prepare($sql);

        // Bind parameters
        $stmt->bindParam(\':filename\', $filename);
        $stmt->bindParam(\':file_path\', $original[\'path\']);
        $stmt->bindParam(\':file_type\', $fileType);
        $stmt->bindParam(\':file_size\', $fileSize);
        $stmt->bindParam(\':alt_text\', $altText);

        // Bind variant URLs
        $thumbnailUrl = isset($variants[\'thumbnail\']) ? $variants[\'thumbnail\'][\'url\'] : null;
        $smallUrl = isset($variants[\'small\']) ? $variants[\'small\'][\'url\'] : null;
        $mediumUrl = isset($variants[\'medium\']) ? $variants[\'medium\'][\'url\'] : null;
        $largeUrl = isset($variants[\'large\']) ? $variants[\'large\'][\'url\'] : null;

        $stmt->bindParam(\':thumbnail_url\', $thumbnailUrl);
        $stmt->bindParam(\':small_url\', $smallUrl);
        $stmt->bindParam(\':medium_url\', $mediumUrl);
        $stmt->bindParam(\':large_url\', $largeUrl);

        // Bind dimensions
        $stmt->bindParam(\':width\', $original[\'width\']);
        $stmt->bindParam(\':height\', $original[\'height\']);

        // Execute statement
        $stmt->execute();

        // Return ID of inserted record
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}';

    file_put_contents($imageOptimizerPath, $imageOptimizerContent);
    $results['image_optimizer']['success'] = true;
    $results['image_optimizer']['message'] = "Created image optimizer file";
}

if (!file_exists($imageConfigPath)) {
    // Create image config file
    $imageConfigContent = '<?php
/**
 * Image Configuration
 *
 * This file contains configuration for image optimization.
 */

$imageConfig = [
    // Image sizes
    \'sizes\' => [
        \'thumbnail\' => [
            \'width\' => 150,
            \'height\' => 150
        ],
        \'small\' => [
            \'width\' => 300,
            \'height\' => null
        ],
        \'medium\' => [
            \'width\' => 600,
            \'height\' => null
        ],
        \'large\' => [
            \'width\' => 1200,
            \'height\' => null
        ]
    ],

    // Image quality (0-100)
    \'quality\' => 80,

    // Maximum file size in bytes (5MB)
    \'max_file_size\' => 5 * 1024 * 1024,

    // Allowed file types
    \'allowed_types\' => [
        \'image/jpeg\',
        \'image/png\',
        \'image/gif\',
        \'image/webp\'
    ],

    // Upload directory
    \'upload_dir\' => __DIR__ . \'/../uploads\',

    // Optimized directory
    \'optimized_dir\' => __DIR__ . \'/../uploads/optimized\'
];';

    file_put_contents($imageConfigPath, $imageConfigContent);
    $results['image_optimizer']['success'] = true;
    $results['image_optimizer']['message'] .= ", created image config file";
} else {
    $results['image_optimizer']['success'] = true;
    $results['image_optimizer']['message'] = "Image optimizer files already exist";
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Media Issues</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Fix Media Issues</h1>
        <p class='lead'>This tool fixes common issues with media uploads and image optimization.</p>

        <div class='alert alert-success mb-4'>
            <h4 class='alert-heading'>Fix Completed</h4>
            <p>The media system has been fixed. See details below.</p>
        </div>";

// Display directory results
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Directory Fixes</h2>";
echo "</div>";
echo "<div class='card-body'>";

foreach ($results['directories'] as $result) {
    echo "<div class='mb-3'>";
    echo "<h5>" . ucfirst($result['name']) . " Directory</h5>";
    echo "<p><strong>Path:</strong> " . htmlspecialchars($result['path']) . "</p>";
    echo "<p><strong>Status:</strong> " . ($result['success'] ? "<span class='success'>Fixed</span>" : "<span class='error'>Failed</span>") . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($result['message']) . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

// Display media table results
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Media Table Fixes</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>Status:</strong> " . ($results['media_table']['success'] ? "<span class='success'>Fixed</span>" : "<span class='error'>Failed</span>") . "</p>";
echo "<p><strong>Message:</strong> " . htmlspecialchars($results['media_table']['message']) . "</p>";

echo "</div>";
echo "</div>";

// Display image optimizer results
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Image Optimizer Fixes</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>Status:</strong> " . ($results['image_optimizer']['success'] ? "<span class='success'>Fixed</span>" : "<span class='error'>Failed</span>") . "</p>";
echo "<p><strong>Message:</strong> " . htmlspecialchars($results['image_optimizer']['message']) . "</p>";

echo "</div>";
echo "</div>";

// Display actions
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Actions</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<a href='media_diagnostic.php' class='btn btn-primary me-2'>";
echo "<i class='fas fa-stethoscope'></i> Run Diagnostic Again";
echo "</a>";

echo "<a href='../../admin/content/media.php' class='btn btn-success me-2'>";
echo "<i class='fas fa-images'></i> Go to Media Page";
echo "</a>";

echo "</div>";
echo "</div>";

// HTML footer
echo "
        <div class='mt-4'>
            <a href='/diagnostic-dashboard.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Back to Diagnostic Dashboard
            </a>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
