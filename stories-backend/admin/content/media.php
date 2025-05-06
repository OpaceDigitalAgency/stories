<?php
/**
 * Media Admin Page
 *
 * This page displays a list of all media files and allows for uploading, searching, and managing media.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include enhanced table component
require_once '../includes/enhanced-table-component.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include image optimizer library
require_once '../../includes/image_optimizer.php';

// Function to handle file paths for display and access
function getDisplayUrl($filePath) {
    // If it's already an absolute URL
    if (strpos($filePath, 'http') === 0) {
        return $filePath;
    }

    // Check for optimized versions
    if (isset($GLOBALS['media']) && isset($GLOBALS['media']['id'])) {
        $mediaId = $GLOBALS['media']['id'];
        $db = $GLOBALS['db'];

        // Check if we have optimized versions
        if (!empty($GLOBALS['media']['medium_url'])) {
            return $GLOBALS['media']['medium_url'];
        }

        if (!empty($GLOBALS['media']['large_url'])) {
            return $GLOBALS['media']['large_url'];
        }
    }

    // If it's a relative URL starting with /
    if (strpos($filePath, '/') === 0) {
        return 'https://' . $_SERVER['HTTP_HOST'] . $filePath;
    }

    // If it's a server path
    if (file_exists($filePath)) {
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
        return 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
    }

    return $filePath;
}

// Helper function to format file sizes
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Check if viewing/editing a specific media item
if (isset($_GET['id'])) {
    $mediaId = (int)$_GET['id'];
    
    // Get media details
    $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch();

    if ($media) {
        $pageTitle = $media['filename'];
        $currentPage = 'media';
        
        // Include header
        require_once '../includes/header.php';
        
        // Get display URL and file info
        $displayUrl = getDisplayUrl($media['file_path']);
        $isImage = strpos($media['file_type'], 'image/') === 0;
        $fileExtension = pathinfo($media['filename'], PATHINFO_EXTENSION);
        $fileExists = file_exists($media['file_path']);
        ?>
        <div class="content-wrapper">
            <div class="container-fluid">
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title"><?php echo htmlspecialchars($media['filename']); ?></h1>
                        <p class="page-description">
                            <a href="media.php" class="text-primary">← Back to Media Library</a>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?php echo htmlspecialchars($displayUrl); ?>" target="_blank" class="btn btn-primary">
                            <span class="icon-download"></span> Download
                        </a>
                        <form method="GET" style="display: inline;">
                            <input type="hidden" name="delete" value="<?php echo $media['id']; ?>">
                            <button type="submit" formaction="media.php" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this file?')">
                                <span class="icon-delete"></span> Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div class="content-section mb-4">
                    <div class="section-header">
                        <h2 class="section-title">File Details</h2>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="media-preview mb-4">
                                    <?php if ($isImage && $fileExists): ?>
                                        <img src="<?php echo htmlspecialchars($displayUrl); ?>"
                                             alt="<?php echo htmlspecialchars($media['alt_text'] ?? $media['filename']); ?>"
                                             class="img-preview">
                                    <?php else: ?>
                                        <div class="file-icon-large">
                                            <span><?php echo htmlspecialchars(strtoupper($fileExtension)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isImage): ?>
                                <div class="optimize-actions mb-4">
                                    <a href="../../public/optimize_image.php?id=<?php echo $media['id']; ?>" class="btn btn-success">
                                        <span class="icon-image"></span> Optimize This Image
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="file-details">
                                    <div class="mb-3">
                                        <strong>Filename:</strong><br>
                                        <?php echo htmlspecialchars($media['filename']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Type:</strong><br>
                                        <?php echo htmlspecialchars($media['file_type']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Size:</strong><br>
                                        <?php echo formatFileSize($media['file_size']); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Uploaded:</strong><br>
                                        <?php echo date('M d, Y H:i', strtotime($media['created_at'])); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>File Path:</strong><br>
                                        <code><?php echo htmlspecialchars($media['file_path']); ?></code>
                                    </div>
                                    <div class="mb-3">
                                        <strong>URL:</strong><br>
                                        <code><?php echo htmlspecialchars($displayUrl); ?></code>
                                    </div>
                                    <?php if ($isImage): ?>
                                    <div class="mb-3">
                                        <strong>Alt Text:</strong><br>
                                        <form method="POST" class="mt-2">
                                            <div class="input-group">
                                                <input type="text" name="alt_text" class="form-control" 
                                                       value="<?php echo htmlspecialchars($media['alt_text'] ?? ''); ?>">
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } else {
        header("Location: media.php");
        exit;
    }
} else {
    // List view
    $pageTitle = 'Media Library';
    $currentPage = 'media';
    
    // Include header
    require_once '../includes/header.php';
    ?>
    <div class="content-wrapper">
        <div class="container-fluid">
            <?php
            try {
                // Get all media
                $stmt = $db->query("SELECT * FROM media ORDER BY created_at DESC");
                $mediaItems = $stmt->fetchAll();

                // Define columns for enhanced table
                $columns = [
                    'filename' => 'Filename',
                    'file_type' => 'Type',
                    'file_size' => 'Size',
                    'created_at' => 'Uploaded'
                ];

                // Custom formatters
                $formatters = [
                    'file_size' => function($value) {
                        return formatFileSize($value);
                    },
                    'created_at' => function($value) {
                        return date('M d, Y H:i', strtotime($value));
                    }
                ];

                // Render enhanced table
                renderEnhancedTable(
                    $mediaItems,
                    $columns,
                    'media',
                    'media-table',
                    [
                        'showCheckboxes' => true,
                        'showActions' => true,
                        'actions' => ['view', 'edit', 'delete'],
                        'formatters' => $formatters,
                        'bulkActions' => ['delete']
                    ]
                );

            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error loading media: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Include footer
require_once '../includes/footer.php';
?>
