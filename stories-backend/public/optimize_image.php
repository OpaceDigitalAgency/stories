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
require_once __DIR__ . '/../includes/image_optimizer.php';

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
require_once '../stories-backend/admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-image"></i> Image Optimization Tool</h2>
                </div>
                <div class="card-body">

    <div>
        <h2>Optimize a Single Image</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="image">Select an image to optimize:</label>
                <input type="file" name="image" id="image">
            </div>
            <button type="submit" name="action" value="optimize_single" class="button">Optimize Image</button>
        </form>
    </div>

    <div>
        <h2>Optimize All Media</h2>
        <form method="post">
            <button type="submit" name="action" value="optimize_all" class="button">Optimize All Media</button>
        </form>
    </div>

    <div>
        <h2>Results</h2>
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
                }
            }
        }
        ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../admin/includes/footer.php';
?>