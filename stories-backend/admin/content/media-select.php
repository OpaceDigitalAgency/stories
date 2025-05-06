<?php
/**
 * Media Select Mode
 *
 * This page displays a simplified media library for selecting media files.
 * It is designed to be used in an iframe or popup window.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

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
    error_log("Media select mode error: " . $e->getMessage());
    $error = "Error loading media data. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select from Media Library</title>
    <link rel="stylesheet" href="../assets/css/enhanced-admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            padding: 0;
            margin: 0;
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .select-container {
            padding: 20px;
        }
        .select-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .select-title {
            font-size: 1.5rem;
            margin: 0;
        }
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .search-button {
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .media-card {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            background-color: white;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .media-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .media-thumbnail {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f1f3f5;
            overflow: hidden;
        }
        .media-thumbnail img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .file-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            color: #495057;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .media-info {
            padding: 10px;
        }
        .media-filename {
            font-size: 0.9rem;
            margin: 0 0 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .media-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .pagination-link {
            display: block;
            padding: 8px 12px;
            margin: 0 3px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #4361ee;
            text-decoration: none;
        }
        .pagination-link.active {
            background-color: #4361ee;
            color: white;
            border-color: #4361ee;
        }
        .upload-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .upload-title {
            font-size: 1.2rem;
            margin: 0 0 10px;
        }
        .dropzone {
            border: 2px dashed #ced4da;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .dropzone:hover {
            border-color: #4361ee;
        }
        .dropzone-icon {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .browse-button {
            display: inline-block;
            background-color: #4361ee;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
        }
        .file-input {
            display: none;
        }
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .bulk-select {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            min-width: 200px;
        }
        .apply-button {
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
        }
        .selected-count {
            margin-left: auto;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="select-container">
        <div class="select-header">
            <h1 class="select-title">Select from Media Library</h1>
        </div>

        <div class="upload-section">
            <h2 class="upload-title">Upload New Media</h2>
            <div class="dropzone" id="upload-dropzone">
                <div class="dropzone-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <p>Drag & drop an image here or</p>
                <label for="file-input" class="browse-button">Browse Files</label>
                <input type="file" id="file-input" class="file-input" accept="image/*">
            </div>
        </div>

        <div class="bulk-actions">
            <select class="bulk-select">
                <option value="">-- Select Action --</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="apply-button">Apply</button>
            <span class="selected-count">0 items selected</span>
        </div>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search Media..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="search-button">
                <i class="fas fa-search"></i> Search
            </button>
        </div>

        <?php if (empty($media)): ?>
            <div class="empty-state">
                <p>No media files found. Upload some files to get started.</p>
            </div>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($media as $item):
                    $isImage = strpos($item['file_type'], 'image/') === 0;
                ?>
                <div class="media-card" data-id="<?php echo $item['id']; ?>">
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
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
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
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle search
        const searchInput = document.querySelector('.search-input');
        const searchButton = document.querySelector('.search-button');

        searchButton.addEventListener('click', function() {
            const searchValue = searchInput.value.trim();
            window.location.href = '?search=' + encodeURIComponent(searchValue);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchButton.click();
            }
        });

        // Handle media selection
        const mediaCards = document.querySelectorAll('.media-card');
        mediaCards.forEach(card => {
            card.addEventListener('click', function() {
                const mediaId = this.getAttribute('data-id');
                const imgElement = this.querySelector('img');

                if (imgElement) {
                    const url = imgElement.getAttribute('src');
                    const dimensions = imgElement.naturalWidth + 'x' + imgElement.naturalHeight;

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
                }
            });
        });

        // Handle file upload
        const fileInput = document.getElementById('file-input');
        const dropzone = document.getElementById('upload-dropzone');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                uploadFile(this.files[0]);
            }
        });

        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            if (e.dataTransfer.files.length > 0) {
                uploadFile(e.dataTransfer.files[0]);
            }
        });

        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('media_file_file', file);

            fetch('media.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text().then(text => {
                        try {
                            // Try to parse as JSON
                            const json = JSON.parse(text);
                            if (json.success) {
                                window.location.reload();
                            } else {
                                alert(json.message || 'Upload failed. Please try again.');
                            }
                        } catch (e) {
                            // If not JSON, just reload
                            window.location.reload();
                        }
                    });
                } else {
                    alert('Upload failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Upload failed. Please try again.');
            });
        }

        // Handle bulk actions
        const bulkSelect = document.querySelector('.bulk-select');
        const applyButton = document.querySelector('.apply-button');
        const selectedCount = document.querySelector('.selected-count');
        let selectedItems = [];

        mediaCards.forEach(card => {
            card.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                this.classList.toggle('selected');

                const mediaId = this.getAttribute('data-id');
                if (this.classList.contains('selected')) {
                    if (!selectedItems.includes(mediaId)) {
                        selectedItems.push(mediaId);
                    }
                } else {
                    selectedItems = selectedItems.filter(id => id !== mediaId);
                }

                selectedCount.textContent = selectedItems.length + ' items selected';
            });
        });

        applyButton.addEventListener('click', function() {
            const action = bulkSelect.value;
            if (!action) {
                alert('Please select an action');
                return;
            }

            if (selectedItems.length === 0) {
                alert('Please select at least one item');
                return;
            }

            if (action === 'delete') {
                if (confirm('Are you sure you want to delete the selected items?')) {
                    // Implement delete functionality
                    alert('Delete functionality will be implemented soon');
                }
            }
        });
    });
    </script>
</body>
</html>
