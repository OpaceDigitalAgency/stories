<?php
/**
 * Simple Image Optimization Wrapper
 *
 * This script provides a simple interface to optimize images using the
 * modular image optimization library. It can be used to optimize a single
 * image or all images in the media table.
 */

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

// Include the image optimization library
// Try multiple possible paths to find the image_optimizer.php file
$possiblePaths = [
    __DIR__ . '/../includes/image_optimizer.php',                   // ../includes/image_optimizer.php
    __DIR__ . '/../../includes/image_optimizer.php',                // ../../includes/image_optimizer.php
    __DIR__ . '/../stories-backend/includes/image_optimizer.php',   // ../stories-backend/includes/image_optimizer.php
    __DIR__ . '/../../stories-backend/includes/image_optimizer.php' // ../../stories-backend/includes/image_optimizer.php
];

$foundPath = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $foundPath = true;
        break;
    }
}

if (!$foundPath) {
    die("Error: Could not find image_optimizer.php file. Please check the server file structure and update the path in optimize_image.php.");
}

// Initialize global variable for current media filename
$GLOBALS['current_media_filename'] = '';

// Database connection function
function optimizerConnectToDatabase() {
    try {
        $db = new PDO(
            'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
            'stories_user',
            '$tw1cac3*sOt',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        echo "<p style='color:green'>Database connection successful</p>";
        return $db;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
        return null;
    }
}

// Function to optimize a single image
function optimizeSingleImage($imagePath, $destinationDir = null) {
    if (!file_exists($imagePath)) {
        echo "<p style='color:red'>Image not found: $imagePath</p>";
        return false;
    }

    if ($destinationDir === null) {
        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
    }

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
        echo "<p style='color:blue'>Created destination directory: $destinationDir</p>";
    }

    echo "<p style='color:blue'>Optimizing image: " . basename($imagePath) . "</p>";

    // Create image variants with aggressive optimization
    $variants = createImageVariants($imagePath, $destinationDir, [
        'convert_format' => 'webp',
        'include_original' => true,
        'quality' => 85,
        'max_width' => 300,
        'strip_metadata' => true,
        'optimize' => true
    ]);

    if ($variants) {
        echo "<p style='color:green'>Successfully created image variants:</p>";
        echo "<ul>";
        foreach ($variants as $size => $info) {
            echo "<li>$size: " . basename($info['path']) . " (" . round($info['size'] / 1024) . " KB)</li>";
        }
        echo "</ul>";
        return $variants;
    } else {
        echo "<p style='color:red'>Failed to create image variants</p>";
        return false;
    }
}

// Use the updateMediaRecord function from the image_optimizer.php library

/**
 * Delete all optimized images and reset media records
 *
 * @param PDO $db Database connection
 * @param bool $deleteUploads Whether to also delete original uploaded images
 * @return array Statistics about the operation
 */
function nukeImages($db, $deleteUploads = false) {
    $stats = [
        'optimized_deleted' => 0,
        'uploads_deleted' => 0,
        'records_reset' => 0,
        'errors' => 0
    ];

    try {
        // 1. Delete all files in the optimized directory
        $optimizedDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
        if (is_dir($optimizedDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($optimizedDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    if (unlink($file->getRealPath())) {
                        $stats['optimized_deleted']++;
                    } else {
                        $stats['errors']++;
                        echo "<p style='color:red'>Failed to delete: " . $file->getRealPath() . "</p>";
                    }
                }
            }

            echo "<p style='color:green'>Deleted {$stats['optimized_deleted']} optimized images</p>";
        } else {
            echo "<p style='color:blue'>Optimized directory not found: $optimizedDir</p>";
        }

        // 2. Delete original uploads if requested
        if ($deleteUploads) {
            $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
            if (is_dir($uploadsDir)) {
                // Skip the optimized directory since we already processed it
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($files as $file) {
                    // Skip directories and the optimized directory
                    if ($file->isFile() && strpos($file->getRealPath(), $optimizedDir) === false) {
                        if (unlink($file->getRealPath())) {
                            $stats['uploads_deleted']++;
                        } else {
                            $stats['errors']++;
                            echo "<p style='color:red'>Failed to delete: " . $file->getRealPath() . "</p>";
                        }
                    }
                }

                echo "<p style='color:green'>Deleted {$stats['uploads_deleted']} original uploaded images</p>";
            } else {
                echo "<p style='color:blue'>Uploads directory not found: $uploadsDir</p>";
            }
        }

        // 3. Reset media records in the database
        // First check if the media table has the required columns
        $hasMetadataColumn = false;
        $hasLegacyColumns = false;

        try {
            $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'metadata'");
            $hasMetadataColumn = $stmt->rowCount() > 0;

            $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
            $hasLegacyColumns = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error checking media table structure: " . $e->getMessage() . "</p>";
            $stats['errors']++;
        }

        // Reset the appropriate columns based on the schema
        if ($hasMetadataColumn) {
            $sql = "UPDATE media SET metadata = NULL";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $stats['records_reset'] += $stmt->rowCount();
        }

        if ($hasLegacyColumns) {
            $sql = "UPDATE media SET
                    thumbnail_url = NULL,
                    small_url = NULL,
                    medium_url = NULL,
                    large_url = NULL";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $stats['records_reset'] += $stmt->rowCount();
        }

        echo "<p style='color:green'>Reset {$stats['records_reset']} media records</p>";

        // 4. Recreate the directories
        if (!is_dir($optimizedDir)) {
            mkdir($optimizedDir, 0755, true);
            echo "<p style='color:green'>Recreated optimized directory</p>";
        }

        if ($deleteUploads && !is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
            echo "<p style='color:green'>Recreated uploads directory</p>";
        }

        return $stats;
    } catch (Exception $e) {
        echo "<p style='color:red'>Error during nuke operation: " . $e->getMessage() . "</p>";
        $stats['errors']++;
        return $stats;
    }
}

// Function to optimize all media files
function optimizeAllMedia($db) {
    // Check if the media table has the required columns
    try {
        $stmt = $db->query("SHOW COLUMNS FROM media LIKE 'thumbnail_url'");
        if ($stmt->rowCount() === 0) {
            echo "<p style='color:red'>Media table is missing required columns. Please run update_media_schema.php first.</p>";
            return false;
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking media table structure: " . $e->getMessage() . "</p>";
        return false;
    }

    // Get all media entries
    $stmt = $db->query("SELECT * FROM media");
    $media = $stmt->fetchAll();

    echo "<h2>Found " . count($media) . " media files to optimize</h2>";

    $stats = [
        'total' => count($media),
        'optimized' => 0,
        'skipped' => 0,
        'failed' => 0
    ];

    $count = 0;
    foreach ($media as $item) {
        $count++;
        $percent = round(($count / $stats['total']) * 100);

        // Update progress bar
        echo "<script>
            document.getElementById('progress').style.width = '{$percent}%';
            document.getElementById('progress-text').innerText = 'Processing {$count} of {$stats['total']} ({$percent}%)';
        </script>";
        ob_flush();
        flush();

        echo "<h3>Processing: " . htmlspecialchars($item['filename']) . " (ID: {$item['id']})</h3>";

        // Store the current media filename in a global variable for the createOptimizedFilename function
        $GLOBALS['current_media_filename'] = $item['filename'];

        // Skip default images
        if (strpos($item['file_path'], 'default-') !== false) {
            echo "<p style='color:blue'>Skipping default image</p>";
            $stats['skipped']++;
            continue;
        }

        // Get the file path
        $filePath = $item['file_path'];
        if (strpos($filePath, 'http') === 0) {
            // For URLs, download the file first
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            if (copy($filePath, $tempFile)) {
                echo "<p style='color:blue'>Downloaded file from URL</p>";
                $filePath = $tempFile;
            } else {
                echo "<p style='color:red'>Failed to download file from URL: $filePath</p>";
                $stats['failed']++;
                continue;
            }
        } else {
            // For local paths, make sure it's an absolute path
            if (strpos($filePath, '/') !== 0) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($filePath, '/');
            }

            if (!file_exists($filePath)) {
                echo "<p style='color:red'>File not found: $filePath</p>";
                $stats['failed']++;
                continue;
            }
        }

        // Optimize the image
        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
        $variants = optimizeSingleImage($filePath, $destinationDir);

        // Clean up temp file if needed
        if (isset($tempFile) && file_exists($tempFile)) {
            unlink($tempFile);
        }

        if ($variants) {
            // Update the media record
            if (updateMediaRecord($db, $item['id'], $variants)) {
                $stats['optimized']++;
            } else {
                $stats['failed']++;
            }
        } else {
            $stats['failed']++;
        }
    }

    echo "<h2>Optimization Summary</h2>";
    echo "<p>Total files: {$stats['total']}</p>";
    echo "<p>Optimized: {$stats['optimized']}</p>";
    echo "<p>Skipped: {$stats['skipped']}</p>";
    echo "<p>Failed: {$stats['failed']}</p>";

    return $stats;
}

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Image Optimization Tool';
$currentPage = 'image-optimization';
$pageDescription = 'Optimize images for better performance and quality.';

// Include header
require_once '../admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-image"></i> Image Optimization Tool</h2>
                </div>
                <div class="card-body">

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h2>Optimize a Single Image</h2>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group mb-3">
                    <label for="image">Select an image to optimize:</label>
                    <input type="file" name="image" id="image" class="form-control">
                </div>
                <button type="submit" name="action" value="optimize_single" class="btn btn-primary">
                    <i class="fas fa-image"></i> Optimize Image
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h2>Optimize All Media</h2>
        </div>
        <div class="card-body">
            <p>This will process all media files in the database and create optimized versions.</p>
            <form method="post">
                <button type="submit" name="action" value="optimize_all" class="btn btn-success">
                    <i class="fas fa-sync"></i> Optimize All Media
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h2>Clear Image Cache (Nuke Option)</h2>
        </div>
        <div class="card-body">
            <form method="post" onsubmit="return confirm('WARNING: This will delete all optimized images and reset media records. This action cannot be undone. Are you sure you want to proceed?');">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This is a destructive operation that will delete optimized images and reset media records.
                </div>

                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="confirm_nuke" id="confirm-nuke" value="1" required>
                        <label class="form-check-label" for="confirm-nuke">
                            <strong>I understand this will delete all optimized images</strong>
                        </label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delete_uploads" id="delete-uploads" value="1">
                        <label class="form-check-label" for="delete-uploads">
                            <strong>Also delete original uploaded images</strong> (use with extreme caution)
                        </label>
                    </div>
                </div>
                <button type="submit" name="action" value="nuke_images" class="btn btn-danger">
                    <i class="fas fa-radiation"></i> Nuke All Images
                </button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h2>Results</h2>
        </div>
        <div class="card-body">
        <?php
        // Check if we're optimizing a specific media ID
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $mediaId = (int)$_GET['id'];
            $db = optimizerConnectToDatabase();

            if ($db) {
                // Get media details
                $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
                $stmt->execute([$mediaId]);
                $media = $stmt->fetch();

                if ($media) {
                    echo "<h3>Optimizing media: " . htmlspecialchars($media['filename']) . " (ID: {$media['id']})</h3>";

                    // Store the current media filename in a global variable for the createOptimizedFilename function
                    $GLOBALS['current_media_filename'] = $media['filename'];

                    // Get the file path
                    $filePath = $media['file_path'];
                    if (strpos($filePath, 'http') === 0) {
                        // For URLs, download the file first
                        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
                        if (copy($filePath, $tempFile)) {
                            echo "<p style='color:blue'>Downloaded file from URL</p>";
                            $filePath = $tempFile;
                        } else {
                            echo "<p style='color:red'>Failed to download file from URL: $filePath</p>";
                            exit;
                        }
                    }

                    // Optimize the image
                    $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
                    $variants = optimizeSingleImage($filePath, $destinationDir);

                    // Clean up temp file if needed
                    if (isset($tempFile) && file_exists($tempFile)) {
                        unlink($tempFile);
                    }

                    if ($variants) {
                        // Update the media record
                        if (updateMediaRecord($db, $mediaId, $variants)) {
                            echo "<p style='color:green'>Media record updated successfully</p>";
                            echo "<p><a href='../admin/content/view-media.php?id=$mediaId' class='button'>View Media</a></p>";
                        } else {
                            echo "<p style='color:red'>Failed to update media record</p>";
                        }
                    } else {
                        echo "<p style='color:red'>Failed to optimize image</p>";
                    }
                } else {
                    echo "<p style='color:red'>Media not found</p>";
                }
            }
        }
        // Process form submission
        else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'optimize_single' && isset($_FILES['image'])) {
                    // Handle single image optimization
                    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $tempPath = $_FILES['image']['tmp_name'];
                        $filename = $_FILES['image']['name'];

                        echo "<h3>Optimizing uploaded image: $filename</h3>";

                        // Store the current media filename in a global variable for the createOptimizedFilename function
                        $GLOBALS['current_media_filename'] = $filename;

                        $destinationDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
                        $variants = optimizeSingleImage($tempPath, $destinationDir);

                        if ($variants) {
                            echo "<p style='color:green'>Image optimized successfully!</p>";
                            echo "<h4>Preview:</h4>";

                            foreach ($variants as $size => $info) {
                                echo "<div style='margin-bottom: 20px;'>";
                                echo "<h5>$size (" . round($info['size'] / 1024) . " KB)</h5>";
                                echo "<img src='" . htmlspecialchars($info['url']) . "' style='max-width: 100%; max-height: 300px;'>";
                                echo "</div>";
                            }
                        }
                    } else {
                        echo "<p style='color:red'>Error uploading file: " . $_FILES['image']['error'] . "</p>";
                    }
                } else if ($_POST['action'] === 'optimize_all') {
                    // Handle optimizing all media
                    $db = optimizerConnectToDatabase();
                    if ($db) {
                        // Add JavaScript for real-time progress updates
                        echo "<script>
                            function scrollToBottom() {
                                window.scrollTo(0, document.body.scrollHeight);
                            }

                            // Auto-scroll to bottom every second to show progress
                            const scrollInterval = setInterval(scrollToBottom, 1000);

                            // Stop auto-scrolling after 5 minutes (safety measure)
                            setTimeout(() => clearInterval(scrollInterval), 300000);
                        </script>";

                        // Flush output buffer to ensure script is loaded
                        ob_flush();
                        flush();

                        echo "<div id='progress-container' style='border: 1px solid #ccc; padding: 10px; margin-top: 20px;'>";
                        echo "<h3>Processing Media Files...</h3>";
                        echo "<div id='progress-bar' style='height: 20px; background-color: #f3f3f3; margin-bottom: 10px;'>";
                        echo "<div id='progress' style='width: 0%; height: 100%; background-color: #4CAF50;'></div>";
                        echo "</div>";
                        echo "<p id='progress-text'>Starting optimization process...</p>";
                        echo "</div>";

                        // Flush output buffer to show progress container
                        ob_flush();
                        flush();

                        optimizeAllMedia($db);

                        // Complete the progress bar
                        echo "<script>
                            document.getElementById('progress').style.width = '100%';
                            document.getElementById('progress-text').innerText = 'Optimization complete!';
                            clearInterval(scrollInterval);
                        </script>";
                        ob_flush();
                        flush();
                    }
                } else if ($_POST['action'] === 'nuke_images') {
                    // Handle nuking all images
                    if (!isset($_POST['confirm_nuke']) || $_POST['confirm_nuke'] != '1') {
                        echo "<p style='color:red'>You must confirm the action by checking the confirmation box.</p>";
                    } else {
                        $db = optimizerConnectToDatabase();
                        if ($db) {
                            echo "<h3>Nuking Image Cache</h3>";
                            echo "<p style='color:red'><strong>WARNING:</strong> This operation cannot be undone!</p>";

                            // Check if we should also delete original uploads
                            $deleteUploads = isset($_POST['delete_uploads']) && $_POST['delete_uploads'] == '1';
                            if ($deleteUploads) {
                                echo "<p style='color:red'><strong>DELETING ALL IMAGES:</strong> Original uploads will also be deleted!</p>";
                            } else {
                                echo "<p style='color:blue'>Only optimized images will be deleted. Original uploads will be preserved.</p>";
                            }

                            // Perform the nuke operation
                            $stats = nukeImages($db, $deleteUploads);

                            // Display summary
                            echo "<h3>Operation Complete</h3>";
                            echo "<ul>";
                            echo "<li>Optimized images deleted: {$stats['optimized_deleted']}</li>";
                            if ($deleteUploads) {
                                echo "<li>Original uploads deleted: {$stats['uploads_deleted']}</li>";
                            }
                            echo "<li>Media records reset: {$stats['records_reset']}</li>";
                            echo "<li>Errors encountered: {$stats['errors']}</li>";
                            echo "</ul>";

                            if ($stats['errors'] > 0) {
                                echo "<p style='color:orange'>Some errors occurred during the operation. You may need to manually check the image directories.</p>";
                            } else {
                                echo "<p style='color:green'>Image cache successfully cleared. You can now re-optimize your images.</p>";
                            }

                            // Add a button to go back to the media admin
                            echo "<p><a href='../admin/content/media.php' class='button'>Go to Media Admin</a></p>";
                        }
                    }
                }
            }
        }
        ?>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../admin/includes/footer.php';
?>