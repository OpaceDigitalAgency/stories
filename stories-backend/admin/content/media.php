<?php
/**
 * Media Admin Page
 *
 * This page displays a list of all media files and allows for uploading, searching, and managing media.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include simple_auth.php directly
require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3+sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
$user = SimpleAuth::check();
if (!$user) {
    // Redirect to login
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Function to handle file paths for display and access
function getDisplayUrl($filePath) {
    // If it's already an absolute URL
    if (strpos($filePath, 'http') === 0) {
        return $filePath;
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

// Initialize variables
$media = [];
$error = null;
$success = null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$totalItems = 0;
$selectMode = isset($_GET['select_mode']) && $_GET['select_mode'] === 'true';

try {

    // Check if media table exists
    $stmt = $db->query("SHOW TABLES LIKE 'media'");
    if ($stmt->rowCount() === 0) {
        // Create media table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            alt_text VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if we have a direct file upload or a URL from the image component
        // First check for the file from the component (media_file_file)
        if (isset($_FILES['media_file_file']) && $_FILES['media_file_file']['error'] === 0) {
            // Handle file upload from component
            $uploadDir = '../../uploads/';

            // Create uploads directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $_FILES['media_file_file'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            $fileType = $file['type'];

            // Generate unique filename
            $fileNameNew = uniqid('', true) . '_' . $fileName;
            $fileDestination = $uploadDir . $fileNameNew;
        }
        // Then check for the traditional file input
        else if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
            // Handle direct file upload
            $uploadDir = '../../uploads/';

            // Create uploads directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $file = $_FILES['media_file'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            $fileType = $file['type'];

            // Generate unique filename
            $fileNameNew = uniqid('', true) . '_' . $fileName;
            $fileDestination = $uploadDir . $fileNameNew;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                // Save file info to database
                $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $fileName,
                    $fileDestination,
                    $fileType,
                    $fileSize,
                    $_POST['alt_text'] ?? ''
                ]);

                $mediaId = $db->lastInsertId();

                // Check if it's an image and automatically optimize it
                if (strpos($fileType, 'image/') === 0) {
                    // Create optimized directory if it doesn't exist
                    $optimizedDir = '../../uploads/optimized/';
                    if (!is_dir($optimizedDir)) {
                        mkdir($optimizedDir, 0755, true);
                    }

                    // Optimize the image
                    $variants = createImageVariants($fileDestination, $optimizedDir);

                    if ($variants) {
                        // Update the media record with optimized URLs
                        updateMediaRecord($db, $mediaId, $variants);
                        $success = "File uploaded and optimized successfully";
                    } else {
                        $success = "File uploaded successfully, but optimization failed";
                    }
                } else {
                    $success = "File uploaded successfully";
                }
            } else {
                $error = "Error moving uploaded file";
            }
        } elseif (isset($_POST['media_file']) && !empty($_POST['media_file'])) {
            // Handle URL from image component
            $fileUrl = $_POST['media_file'];

            // Check if this is already a file in our system
            if (strpos($fileUrl, $_SERVER['HTTP_HOST']) !== false) {
                $success = "File selected successfully";
            } else {
                // This is an external URL, try to download it
                $fileName = basename($fileUrl);
                $uploadDir = '../../uploads/';
                $fileDestination = $uploadDir . uniqid('', true) . '_' . $fileName;

                // Create uploads directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Try to get the file
                $fileContent = @file_get_contents($fileUrl);
                if ($fileContent !== false) {
                    if (file_put_contents($fileDestination, $fileContent)) {
                        // Determine file type
                        $fileType = mime_content_type($fileDestination);
                        $fileSize = filesize($fileDestination);

                        // Save file info to database
                        $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, alt_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                        $stmt->execute([
                            $fileName,
                            $fileDestination,
                            $fileType,
                            $fileSize,
                            $_POST['alt_text'] ?? ''
                        ]);

                        $mediaId = $db->lastInsertId();

                        // Check if it's an image and automatically optimize it
                        if (strpos($fileType, 'image/') === 0) {
                            // Create optimized directory if it doesn't exist
                            $optimizedDir = '../../uploads/optimized/';
                            if (!is_dir($optimizedDir)) {
                                mkdir($optimizedDir, 0755, true);
                            }

                            // Optimize the image
                            $variants = createImageVariants($fileDestination, $optimizedDir);

                            if ($variants) {
                                // Update the media record with optimized URLs
                                updateMediaRecord($db, $mediaId, $variants);
                                $success = "File downloaded and optimized successfully";
                            } else {
                                $success = "File downloaded successfully, but optimization failed";
                            }
                        } else {
                            $success = "File downloaded successfully";
                        }
                    } else {
                        $error = "Error saving downloaded file";
                    }
                } else {
                    $error = "Error downloading file from URL";
                }
            }
        } else {
            $error = "No file uploaded or URL provided";
        }
    }

    // Handle file deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = $_GET['delete'];

        // Get file path
        $stmt = $db->prepare("SELECT file_path FROM media WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if ($file) {
            // Delete file from filesystem
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }

            // Delete from database
            $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
            $stmt->execute([$id]);

            $success = "File deleted successfully";
        } else {
            $error = "File not found";
        }
    }

    // Count total items for pagination
    $countSql = "SELECT COUNT(*) FROM media";
    $params = [];

    if (!empty($search)) {
        $countSql .= " WHERE filename LIKE ?";
        $params[] = "%$search%";
    }

    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();

    // Calculate pagination
    $totalPages = ceil($totalItems / $perPage);
    $page = min($page, max(1, $totalPages));
    $offset = ($page - 1) * $perPage;

    // Get media files with search and pagination
    $sql = "SELECT * FROM media";
    if (!empty($search)) {
        $sql .= " WHERE filename LIKE ?";
    }
    $sql .= " ORDER BY created_at DESC LIMIT $offset, $perPage";

    $stmt = $db->prepare($sql);
    if (!empty($search)) {
        $stmt->execute(["%$search%"]);
    } else {
        $stmt->execute();
    }
    $media = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Media page error: " . $e->getMessage());
    $error = "Error loading media data. Please try again.";
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Set page variables for header
$pageTitle = $selectMode ? 'Select from Media Library' : 'Media';
$currentPage = 'media';
$pageDescription = $selectMode ? 'Select a media file to use in your content' : 'Manage all your media files from here.';
$pageActions = $selectMode ? '' : '
<a href="../../public/optimize_image.php" class="btn btn-success">
    <i class="fas fa-image" aria-hidden="true"></i> Optimize All Media
</a>
';

// Add custom CSS for media page
$extraHeadContent = '
<style>
    .upload-form {
        max-width: 600px;
    }

    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .media-card {
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
        transition: all 0.2s ease;
        background-color: white;
        position: relative;
    }

    .media-card .bulk-checkbox {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        width: 20px;
        height: 20px;
        cursor: pointer;
        background-color: white;
        border-radius: 4px;
        border: 2px solid var(--primary);
    }

    .media-card .bulk-checkbox:checked {
        background-color: var(--primary);
    }

    .media-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .media-thumbnail {
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gray-50);
        overflow: hidden;
        border-bottom: 1px solid var(--border-color);
    }

    .media-thumbnail img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .file-icon {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gray-200);
        color: var(--gray-700);
        font-weight: bold;
        text-transform: uppercase;
        border-radius: var(--radius-md);
    }

    .media-info {
        padding: 1rem;
    }

    .media-filename {
        font-size: 1rem;
        margin: 0 0 0.5rem;
        word-break: break-word;
    }

    .media-date {
        font-size: 0.875rem;
        color: var(--gray-600);
        margin-bottom: 1rem;
    }

    .media-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* Progress indicator styles */
    .progress-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0s, opacity 0.3s;
    }

    .progress-container {
        background-color: white;
        padding: 30px;
        border-radius: var(--radius-md);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        width: 80%;
        max-width: 500px;
        text-align: center;
    }

    .progress-spinner {
        border: 5px solid var(--gray-200);
        border-top: 5px solid var(--primary);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<!-- Include external JavaScript file -->
<script src="../js/media.js"></script>
';

// Include header
require_once '../includes/header.php';
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-upload" aria-hidden="true"></i> Upload New Media</h2>
        <p class="section-description">Upload images and other media files to use in your content</p>
    </div>
    <div class="section-body">
        <div class="upload-section">
            <div class="upload-tabs">
                <button type="button" class="upload-tab active" data-tab="single">Single File Upload</button>
                <button type="button" class="upload-tab" data-tab="bulk">Bulk Upload</button>
            </div>

            <div class="upload-tab-content" id="single-upload" style="display: block;">
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <?php
                    // Render the image upload component
                    renderImageUploadComponent(
                        'media_file',
                        '',
                        'Upload Media',
                        'media',
                        null
                    );
                    ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="alt_text">Alt Text</label>
                        <input type="text" id="alt_text" name="alt_text" class="form-control" placeholder="Describe the image for accessibility">
                        <div class="form-text">Providing alt text improves accessibility for screen reader users</div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload" aria-hidden="true"></i> Upload
                        </button>
                    </div>
                </form>
            </div>

            <div class="upload-tab-content" id="bulk-upload" style="display: none;">
                <form method="POST" enctype="multipart/form-data" action="../handlers/bulk-upload.php" id="bulk-upload-form">
                    <div class="bulk-dropzone" id="bulk-dropzone" style="border: 2px dashed #ccc; padding: 20px; text-align: center; background-color: #f9f9f9; margin-bottom: 20px; cursor: pointer;">
                        <div class="dropzone-message">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #4361ee; margin-bottom: 10px;"></i>
                            <p>Drag & drop multiple images here or</p>
                            <label for="bulk-file-input" class="btn btn-primary" id="browse-files-btn">
                                Browse Files
                            </label>
                            <input type="file" name="files[]" id="bulk-file-input" multiple accept="image/*" style="display: none;">
                            <input type="hidden" name="entity_type" value="media">
                            <div class="dropzone-info" style="margin-top: 10px;">
                                <small>Supported formats: JPG, PNG, GIF, WebP. Max size per file: 10MB</small>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-bottom: 20px;">
                        <button type="submit" class="btn btn-success" id="bulk-upload-submit">
                            <i class="fas fa-upload"></i> Upload Files
                        </button>
                    </div>
                </form>

                <script>
                // Direct event handler for the browse files button
                document.addEventListener('DOMContentLoaded', function() {
                    const browseBtn = document.getElementById('browse-files-btn');
                    const fileInput = document.getElementById('bulk-file-input');
                    const uploadForm = document.getElementById('bulk-upload-form');
                    const progressContainer = document.querySelector('.bulk-upload-progress');
                    const progressBar = document.querySelector('.bulk-upload-progress .progress-bar');

                    if (browseBtn && fileInput) {
                        browseBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            fileInput.click();
                        });
                    }

                    // Also make the entire dropzone clickable
                    const dropzone = document.getElementById('bulk-dropzone');
                    if (dropzone && fileInput) {
                        dropzone.addEventListener('click', function(e) {
                            // Don't trigger if they clicked the button directly
                            if (e.target !== browseBtn && !browseBtn.contains(e.target)) {
                                fileInput.click();
                            }
                        });
                    }

                    // Show selected files count
                    if (fileInput) {
                        fileInput.addEventListener('change', function() {
                            const fileCount = this.files ? this.files.length : 0;
                            const submitBtn = document.getElementById('bulk-upload-submit');

                            if (submitBtn && fileCount > 0) {
                                submitBtn.textContent = `Upload ${fileCount} Files`;
                                submitBtn.disabled = false;
                            } else if (submitBtn) {
                                submitBtn.textContent = 'Upload Files';
                                submitBtn.disabled = true;
                            }
                        });
                    }

                    // Handle form submission with progress
                    if (uploadForm && progressContainer && progressBar) {
                        uploadForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const files = fileInput.files;
                            if (!files || files.length === 0) {
                                alert('Please select at least one file to upload.');
                                return;
                            }

                            // Show progress
                            progressContainer.style.display = 'block';
                            progressBar.style.width = '0%';
                            progressBar.textContent = '0%';

                            // Create FormData from the form
                            const formData = new FormData(this);

                            // Send AJAX request
                            const xhr = new XMLHttpRequest();
                            xhr.open('POST', this.action, true);

                            // Track progress
                            xhr.upload.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    const percent = Math.round((e.loaded / e.total) * 100);
                                    progressBar.style.width = percent + '%';
                                    progressBar.textContent = percent + '%';
                                }
                            });

                            // Handle completion
                            xhr.addEventListener('load', function() {
                                if (xhr.status === 200) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.success) {
                                            alert('Files uploaded successfully!');
                                            // Reload the page after a delay
                                            setTimeout(function() {
                                                window.location.reload();
                                            }, 1000);
                                        } else {
                                            alert('Upload failed: ' + response.message);
                                        }
                                    } catch (e) {
                                        alert('Error processing server response.');
                                    }
                                } else {
                                    alert('Upload failed. Server returned status: ' + xhr.status);
                                }
                            });

                            // Handle errors
                            xhr.addEventListener('error', function() {
                                alert('Network error occurred. Please try again.');
                            });

                            // Send the request
                            xhr.send(formData);
                        });
                    }
                });
                </script>

                <div class="bulk-upload-progress" style="display: none;">
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div class="upload-status">
                        <span class="current-file"></span>
                        <span class="upload-count"></span>
                    </div>
                </div>

                <div class="bulk-upload-results" style="display: none;">
                    <h4>Upload Results</h4>
                    <div class="results-list"></div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="content-section">
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-images" aria-hidden="true"></i> Media Library</h2>
        <p class="section-description">Browse and manage your uploaded media files</p>
    </div>

    <?php
    // Include bulk actions component
    include_once '../includes/bulk-actions-component.php';
    if (function_exists('renderEnhancedBulkActionsComponent')) {
        renderEnhancedBulkActionsComponent('media', [
            'delete' => 'Delete Selected',
            'optimize' => 'Optimize Selected'
        ]);
    } else if (function_exists('renderBulkActionsComponent')) {
        renderBulkActionsComponent('media', ['delete', 'optimize']);
    }

    // Include search component
    include_once '../includes/search-component.php';
    if (function_exists('renderSearchComponent')) {
        renderSearchComponent('media', ['filename', 'alt_text', 'file_type'], $search);
    } else {
        // Fallback to original search form if component not available
        ?>
        <form method="GET" class="search-form">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by filename..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search" aria-hidden="true"></i> Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="media.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }
    ?>
    <div class="section-body">
        <?php if (empty($media)): ?>
            <p class="no-items">No media files found.</p>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($media as $item): ?>
                    <div class="media-card">
                        <input type="checkbox" class="bulk-checkbox" name="selected_ids[]" value="<?php echo $item['id']; ?>">
                        <?php
                        $isImage = strpos($item['file_type'], 'image/') === 0;
                        $thumbnailPath = $isImage ? $item['file_path'] : '../assets/images/file-icon.png';
                        ?>
                        <div class="media-thumbnail">
                            <?php if ($isImage): ?>
                                <img src="<?php echo htmlspecialchars(getDisplayUrl($item['file_path'])); ?>" alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['filename']); ?>">
                            <?php else: ?>
                                <div class="file-icon"><?php echo pathinfo($item['filename'], PATHINFO_EXTENSION); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <h3 class="media-filename"><?php echo htmlspecialchars($item['filename']); ?></h3>
                            <p class="media-date"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            <div class="media-actions">
                                <?php if ($selectMode): ?>
                                <button type="button" class="btn btn-success btn-sm select-media-item"
                                        data-url="<?php echo htmlspecialchars(getDisplayUrl($item['file_path'])); ?>"
                                        data-dimensions="<?php echo isset($item['width']) && isset($item['height']) ? $item['width'] . 'x' . $item['height'] : ''; ?>"
                                        aria-label="Select <?php echo htmlspecialchars($item['filename']); ?>">
                                    <i class="fas fa-check" aria-hidden="true"></i> Select
                                </button>
                                <?php else: ?>
                                <a href="view-media.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm" aria-label="View <?php echo htmlspecialchars($item['filename']); ?>">
                                    <i class="fas fa-eye" aria-hidden="true"></i> View
                                </a>
                                <a href="<?php echo htmlspecialchars(getDisplayUrl($item['file_path'])); ?>" target="_blank" class="btn btn-primary btn-sm" aria-label="Download <?php echo htmlspecialchars($item['filename']); ?>">
                                    <i class="fas fa-download" aria-hidden="true"></i> Download
                                </a>
                                <form method="GET" style="display: inline;">
                                    <input type="hidden" name="delete" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this file?')"
                                            aria-label="Delete <?php echo htmlspecialchars($item['filename']); ?>">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php
            // Include pagination component
            include_once '../includes/pagination-component.php';
            if (function_exists('renderPagination')) {
                renderPagination($totalItems, $perPage, $page);
            } else {
                // Fallback to original pagination if component not available
                if ($totalPages > 1):
                ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="First page">First</a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Previous page">Previous</a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>"
                               <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Next page">Next</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link" aria-label="Last page">Last</a>
                        <?php endif; ?>
                    </div>
                    <div class="pagination-info">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $totalItems); ?> of <?php echo $totalItems; ?> items
                    </div>
                </div>
                <?php
                endif;
            }
            ?>
        <?php endif; ?>
    </div>
</div>

<!-- Progress Indicator -->
<div id="progressOverlay" class="progress-overlay">
    <div class="progress-container">
        <div class="progress-spinner"></div>
        <h3 id="progressTitle">Processing...</h3>
        <p id="progressMessage">Please wait while we optimize your images.</p>
    </div>
</div>

<?php if ($selectMode): ?>
<script>
// Add script for select mode
document.addEventListener('DOMContentLoaded', function() {
    // Handle selection
    const selectButtons = document.querySelectorAll('.select-media-item');
    selectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const dimensions = this.getAttribute('data-dimensions');

            // Send message to parent window
            window.parent.postMessage({
                type: 'media-selected',
                url: url,
                dimensions: dimensions
            }, '*');

            // Also send to opener in case we're in a popup
            if (window.opener) {
                window.opener.postMessage({
                    type: 'media-selected',
                    url: url,
                    dimensions: dimensions
                }, '*');
            }
        });
    });

    // Make thumbnails clickable
    const thumbnails = document.querySelectorAll('.media-thumbnail');
    thumbnails.forEach(thumbnail => {
        thumbnail.style.cursor = 'pointer';
        thumbnail.addEventListener('click', function() {
            const card = this.closest('.media-card');
            const selectButton = card.querySelector('.select-media-item');
            if (selectButton) {
                selectButton.click();
            }
        });
    });
});
</script>
<?php endif; ?>

<?php
// Include footer
include_once '../includes/footer.php';
?>
