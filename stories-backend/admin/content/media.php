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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
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

        // Validate file
        if ($fileError === 0) {
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
                    $optimizedDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
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
        } else {
            $error = "Error uploading file: " . $fileError;
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

    /* Bulk upload styles */
    .upload-tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .upload-tab {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-weight: 500;
        color: var(--gray-600);
        transition: all 0.2s ease;
    }

    .upload-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    .upload-tab:hover {
        color: var(--primary);
    }

    .bulk-dropzone {
        border: 2px dashed var(--border-color);
        border-radius: var(--radius-md);
        padding: 40px 20px;
        text-align: center;
        background-color: var(--gray-50);
        transition: all 0.3s ease;
        margin-bottom: 20px;
    }

    .bulk-dropzone.dragover {
        background-color: var(--primary-light);
        border-color: var(--primary);
    }

    .dropzone-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        color: var(--gray-600);
    }

    .dropzone-message i {
        font-size: 3rem;
        color: var(--gray-500);
    }

    .dropzone-info {
        margin-top: 10px;
        color: var(--gray-500);
    }

    .bulk-upload-results {
        margin-top: 20px;
        padding: 15px;
        background-color: var(--gray-50);
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }

    .results-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .upload-result-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .upload-result-item:last-child {
        border-bottom: none;
    }

    .upload-result-item.success {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .upload-result-item.error {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .upload-result-icon {
        margin-right: 10px;
        font-size: 1.2rem;
    }

    .upload-result-icon.success {
        color: var(--success);
    }

    .upload-result-icon.error {
        color: var(--danger);
    }

    .upload-result-info {
        flex: 1;
    }

    .upload-result-name {
        font-weight: 500;
    }

    .upload-result-message {
        font-size: 0.875rem;
        color: var(--gray-600);
    }

    .upload-status {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.875rem;
        color: var(--gray-700);
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

                // Don\'t prevent default - let the link work normally
            });
        });

        // Also add progress indicator to file upload
        const uploadForm = document.querySelector("form.upload-form");
        if (uploadForm) {
            uploadForm.addEventListener("submit", function(e) {
                // Only show progress if a file is selected
                const fileInput = document.getElementById("media_file");
                if (fileInput && fileInput.files.length > 0) {
                    const overlay = document.getElementById("progressOverlay");
                    overlay.style.visibility = "visible";
                    overlay.style.opacity = "1";

                    const title = document.getElementById("progressTitle");
                    const message = document.getElementById("progressMessage");

                    title.textContent = "Uploading and Optimizing";
                    message.textContent = "Please wait while we upload and optimize your image.";
                }
            });
        }

        // Handle upload tabs
        const uploadTabs = document.querySelectorAll('.upload-tab');
        const tabContents = document.querySelectorAll('.upload-tab-content');

        uploadTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                uploadTabs.forEach(t => t.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.add('active');

                // Hide all tab contents
                tabContents.forEach(content => {
                    content.style.display = 'none';
                });

                // Show the selected tab content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-upload').style.display = 'block';
            });
        });

        // Handle bulk upload
        const bulkDropzone = document.getElementById('bulk-dropzone');
        const bulkFileInput = document.getElementById('bulk-file-input');
        const progressBar = document.querySelector('.bulk-upload-progress .progress-bar');
        const progressContainer = document.querySelector('.bulk-upload-progress');
        const resultsContainer = document.querySelector('.bulk-upload-results');
        const resultsList = document.querySelector('.results-list');
        const currentFileSpan = document.querySelector('.current-file');
        const uploadCountSpan = document.querySelector('.upload-count');

        // Handle drag and drop
        if (bulkDropzone) {
            bulkDropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                bulkDropzone.classList.add('dragover');
            });

            bulkDropzone.addEventListener('dragleave', () => {
                bulkDropzone.classList.remove('dragover');
            });

            bulkDropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                bulkDropzone.classList.remove('dragover');

                if (e.dataTransfer.files.length) {
                    handleBulkUpload(e.dataTransfer.files);
                }
            });
        }

        // Handle file input change
        if (bulkFileInput) {
            bulkFileInput.addEventListener('change', () => {
                if (bulkFileInput.files.length) {
                    handleBulkUpload(bulkFileInput.files);
                }
            });
        }

        // Function to handle bulk upload
        function handleBulkUpload(files) {
            // Reset UI
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            progressContainer.style.display = 'block';
            resultsContainer.style.display = 'none';
            resultsList.innerHTML = '';

            // Create FormData
            const formData = new FormData();

            // Add each file to FormData
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }

            // Add entity type
            formData.append('entity_type', 'media');

            // Update status
            uploadCountSpan.textContent = `0/${files.length} files uploaded`;

            // Create AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../handlers/bulk-upload.php', true);

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent);
                }
            });

            // Handle response
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        // Show results
                        resultsContainer.style.display = 'block';

                        // Process each file result
                        if (response.files && response.files.length) {
                            response.files.forEach(file => {
                                const resultItem = document.createElement('div');
                                resultItem.className = `upload-result-item ${file.success ? 'success' : 'error'}`;

                                const iconClass = file.success ? 'success' : 'error';
                                const iconName = file.success ? 'check-circle' : 'times-circle';

                                resultItem.innerHTML = `
                                    <div class="upload-result-icon ${iconClass}">
                                        <i class="fas fa-${iconName}"></i>
                                    </div>
                                    <div class="upload-result-info">
                                        <div class="upload-result-name">${file.name}</div>
                                        <div class="upload-result-message">${file.success ? 'Uploaded successfully' : file.message}</div>
                                    </div>
                                `;

                                resultsList.appendChild(resultItem);
                            });

                            // Update count
                            const successCount = response.files.filter(f => f.success).length;
                            uploadCountSpan.textContent = `${successCount}/${response.files.length} files uploaded`;
                        }

                        // If any files were uploaded successfully, refresh the page after a delay
                        if (response.success) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        }
                    } catch (e) {
                        alert('Error parsing server response.');
                    }
                } else {
                    alert('Upload failed. Please try again.');
                }
            });

            // Handle errors
            xhr.addEventListener('error', () => {
                alert('Upload failed. Please try again.');
            });

            // Send the request
            xhr.send(formData);
        }
    });
</script>

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

<?php
// Add progress indicator HTML
?>
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
    // Handle media selection in select mode
    document.addEventListener('DOMContentLoaded', function() {
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
            });
        });

        // Also allow clicking on the image to select
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