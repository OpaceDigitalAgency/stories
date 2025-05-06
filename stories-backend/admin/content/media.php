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

// Include image optimizer
require_once '../../includes/image_optimizer.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

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
            thumbnail_url VARCHAR(255),
            small_url VARCHAR(255),
            medium_url VARCHAR(255),
            large_url VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if we have a direct file upload or a URL from the image component
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

                // If it's an image, try to optimize it
                if (strpos($fileType, 'image/') === 0) {
                    $variants = optimizeImage($fileDestination);
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
        }
    }

    // Handle file deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $mediaId = (int)$_GET['delete'];
        
        // Get file info
        $stmt = $db->prepare("SELECT file_path FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $file = $stmt->fetch();
        
        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
        $stmt->execute([$mediaId]);
        
        $_SESSION['success'] = "File deleted successfully";
        header("Location: media.php");
        exit;
    }

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM media";
    if (!empty($search)) {
        $countSql .= " WHERE filename LIKE ?";
    }
    $stmt = $db->prepare($countSql);
    if (!empty($search)) {
        $stmt->execute(["%$search%"]);
    } else {
        $stmt->execute();
    }
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($page - 1) * $perPage;

    // Get media items with all size variants
    $sql = "SELECT *, 
            thumbnail_url, small_url, medium_url, large_url 
            FROM media";
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
$pageTitle = 'Media';
$currentPage = 'media';
$pageDescription = 'Manage all your media files from here.';
$pageActions = '
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

    .image-sizes {
        margin-top: 0.5rem;
        font-size: 0.875rem;
    }

    .image-sizes a {
        color: var(--primary);
        text-decoration: none;
        margin-right: 1rem;
    }

    .image-sizes a:hover {
        text-decoration: underline;
    }

    /* Upload tabs */
    .upload-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .upload-tab {
        padding: 0.5rem 1rem;
        border: none;
        background: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        color: var(--gray-600);
    }

    .upload-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    /* Bulk upload */
    .bulk-dropzone {
        border: 2px dashed var(--border-color);
        border-radius: var(--radius-lg);
        padding: 2rem;
        text-align: center;
        background: var(--gray-50);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .bulk-dropzone:hover {
        border-color: var(--primary);
        background: var(--gray-100);
    }

    .dropzone-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .dropzone-message i {
        font-size: 3rem;
        color: var(--gray-400);
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

<script>
    // Show progress indicator when optimizing images
    document.addEventListener("DOMContentLoaded", function() {
        // Get all buttons that trigger optimization
        const optimizeButtons = document.querySelectorAll("a[href*=\"optimize_image.php\"]");

        optimizeButtons.forEach(button => {
            button.addEventListener("click", function(e) {
                // Show the progress overlay
                const overlay = document.getElementById("progressOverlay");
                overlay.style.visibility = "visible";
                overlay.style.opacity = "1";

                // Set appropriate message based on button text
                const title = document.getElementById("progressTitle");
                const message = document.getElementById("progressMessage");

                if (this.textContent.includes("All Media")) {
                    title.textContent = "Optimizing All Media";
                    message.textContent = "This may take several minutes. Please do not close this page.";
                } else {
                    title.textContent = "Optimizing Image";
                    message.textContent = "Please wait while we optimize your image.";
                }
            });
        });

        // Handle upload tabs
        const tabs = document.querySelectorAll(".upload-tab");
        const tabContents = document.querySelectorAll(".upload-tab-content");

        tabs.forEach(tab => {
            tab.addEventListener("click", function() {
                const tabId = this.getAttribute("data-tab");

                // Update active tab
                tabs.forEach(t => t.classList.remove("active"));
                this.classList.add("active");

                // Show corresponding content
                tabContents.forEach(content => {
                    if (content.id === tabId + "-upload") {
                        content.style.display = "block";
                    } else {
                        content.style.display = "none";
                    }
                });
            });
        });

        // Handle bulk upload
        const dropzone = document.getElementById("bulk-dropzone");
        const fileInput = document.getElementById("bulk-file-input");

        if (dropzone && fileInput) {
            dropzone.addEventListener("dragover", function(e) {
                e.preventDefault();
                this.style.borderColor = "var(--primary)";
                this.style.background = "var(--gray-100)";
            });

            dropzone.addEventListener("dragleave", function(e) {
                e.preventDefault();
                this.style.borderColor = "var(--border-color)";
                this.style.background = "var(--gray-50)";
            });

            dropzone.addEventListener("drop", function(e) {
                e.preventDefault();
                this.style.borderColor = "var(--border-color)";
                this.style.background = "var(--gray-50)";

                const files = e.dataTransfer.files;
                handleFiles(files);
            });

            fileInput.addEventListener("change", function() {
                handleFiles(this.files);
            });
        }

        function handleFiles(files) {
            const overlay = document.getElementById("progressOverlay");
            const title = document.getElementById("progressTitle");
            const message = document.getElementById("progressMessage");

            overlay.style.visibility = "visible";
            overlay.style.opacity = "1";
            title.textContent = "Uploading Files";
            message.textContent = `Uploading ${files.length} file${files.length !== 1 ? 's' : ''}...`;

            // Create FormData and append files
            const formData = new FormData();
            Array.from(files).forEach(file => {
                formData.append("files[]", file);
            });

            // Upload files
            fetch("upload-bulk.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Error uploading files: " + data.error);
                    overlay.style.visibility = "hidden";
                    overlay.style.opacity = "0";
                }
            })
            .catch(error => {
                alert("Error uploading files: " + error);
                overlay.style.visibility = "hidden";
                overlay.style.opacity = "0";
            });
        }
    });
</script>
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
                    <div class="form-group mb-3">
                        <label class="form-label" for="media_file">File <span class="required" aria-hidden="true">*</span><span class="visually-hidden">required</span></label>
                        <input type="file" id="media_file" name="media_file" class="form-control" required aria-required="true">
                        <div class="form-text">Supported formats: JPG, PNG, GIF, PDF, DOC, DOCX, etc. Max size: 10MB</div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label" for="alt_text">Alt Text</label>
                        <input type="text" id="alt_text" name="alt_text" class="form-control" placeholder="Describe the image for accessibility">
                        <div class="form-text">Providing alt text improves accessibility for screen reader users</div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload" aria-hidden="true"></i> Upload
                        </button>
                        <?php
                        // Add AI image generator
                        renderAiImageGenerator('media', [], 'media_file', 'media_file_preview');
                        ?>
                    </div>
                </form>
            </div>

            <div class="upload-tab-content" id="bulk-upload" style="display: none;">
                <div class="bulk-dropzone" id="bulk-dropzone">
                    <div class="dropzone-message">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Drag & drop multiple images here or</span>
                        <label for="bulk-file-input" class="btn btn-primary">
                            Browse Files
                        </label>
                        <input type="file" id="bulk-file-input" multiple accept="image/*" style="display: none;">
                        <div class="dropzone-info">
                            <small>Supported formats: JPG, PNG, GIF, WebP. Max size per file: 10MB</small>
                        </div>
                    </div>
                </div>

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
    }

    // Include search component
    include_once '../includes/search-component.php';
    if (function_exists('renderSearchComponent')) {
        renderSearchComponent('media', ['filename', 'alt_text', 'file_type'], $search);
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
                        $thumbnailPath = $isImage ? ($item['thumbnail_url'] ?? $item['file_path']) : '../assets/images/file-icon.png';
                        ?>
                        <div class="media-thumbnail">
                            <?php if ($isImage): ?>
                                <img src="<?php echo htmlspecialchars(getDisplayUrl($thumbnailPath)); ?>" alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['filename']); ?>">
                            <?php else: ?>
                                <div class="file-icon"><?php echo pathinfo($item['filename'], PATHINFO_EXTENSION); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <h3 class="media-filename"><?php echo htmlspecialchars($item['filename']); ?></h3>
                            <p class="media-date"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                            <?php if ($isImage): ?>
                            <div class="image-sizes">
                                <?php if (!empty($item['thumbnail_url'])): ?>
                                    <a href="<?php echo htmlspecialchars(getDisplayUrl($item['thumbnail_url'])); ?>" target="_blank">Thumbnail</a>
                                <?php endif; ?>
                                <?php if (!empty($item['small_url'])): ?>
                                    <a href="<?php echo htmlspecialchars(getDisplayUrl($item['small_url'])); ?>" target="_blank">Small</a>
                                <?php endif; ?>
                                <?php if (!empty($item['medium_url'])): ?>
                                    <a href="<?php echo htmlspecialchars(getDisplayUrl($item['medium_url'])); ?>" target="_blank">Medium</a>
                                <?php endif; ?>
                                <?php if (!empty($item['large_url'])): ?>
                                    <a href="<?php echo htmlspecialchars(getDisplayUrl($item['large_url'])); ?>" target="_blank">Large</a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
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
require_once '../includes/footer.php';
?>
