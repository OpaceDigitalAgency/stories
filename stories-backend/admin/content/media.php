<?php
require_once '../../simple_auth.php';
require_once '../../includes/image_optimizer.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: ../login.php");
    exit;
}

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
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media - Admin</title>
    <link rel="stylesheet" href="../assets/css/modern-admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <form method="POST" action="../logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="nav-menu">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link active">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Media</h1>
                <p class="page-description">Manage all your media files from here.</p>
            </div>
            <div>
                <a href="../../public/optimize_image.php" class="btn btn-success">
                    <span class="icon-image"></span> Optimize All Media
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Upload New Media</h2>
            </div>
            <div class="section-body">
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group mb-3">
                        <label class="form-label" for="media_file">File <span class="required">*</span></label>
                        <input type="file" id="media_file" name="media_file" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label" for="alt_text">Alt Text</label>
                        <input type="text" id="alt_text" name="alt_text" class="form-control" placeholder="Describe the image for accessibility">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <span class="icon-upload"></span> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-section">
            <div class="section-header d-flex justify-content-between align-items-center">
                <h2 class="section-title">Media Library</h2>
                <form method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by filename..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <span class="icon-search"></span> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="media.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="section-body">
                <?php if (empty($media)): ?>
                    <p class="no-items">No media files found.</p>
                <?php else: ?>
                    <div class="media-grid">
                        <?php foreach ($media as $item): ?>
                            <div class="media-card">
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
                                        <form method="GET" action="view-media.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-info btn-sm">
                                                <span class="icon-view"></span> View
                                            </button>
                                        </form>
                                        <a href="<?php echo htmlspecialchars(getDisplayUrl($item['file_path'])); ?>" target="_blank" class="btn btn-primary btn-sm">
                                            <span class="icon-download"></span> Download
                                        </a>
                                        <form method="GET" style="display: inline;">
                                            <input type="hidden" name="delete" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this file?')">
                                                <span class="icon-delete"></span> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">First</a>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                                   class="pagination-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Next</a>
                                <a href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Last</a>
                            <?php endif; ?>
                        </div>
                        <div class="pagination-info">
                            Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $totalItems); ?> of <?php echo $totalItems; ?> items
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-transform: uppercase;
            color: var(--gray-700);
            border-radius: var(--radius-sm);
            font-size: 1.2rem;
        }
        
        .media-info {
            padding: 15px;
        }
        
        .media-filename {
            margin: 0 0 8px 0;
            font-weight: 600;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--gray-900);
        }
        
        .media-date {
            margin: 0 0 15px 0;
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        .media-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .no-items {
            text-align: center;
            padding: 30px;
            color: var(--gray-600);
            background: var(--gray-50);
            border-radius: var(--radius-md);
            font-size: 1.1rem;
        }
        
        .required {
            color: var(--danger);
            margin-left: 3px;
        }
        
        .icon-upload:before {
            content: "↑";
        }
        
        .icon-download:before {
            content: "↓";
        }
        
        .icon-image:before {
            content: "🖼️";
        }
        
        .icon-search:before {
            content: "🔍";
        }
        
        .icon-view:before {
            content: "👁️";
        }
        
        .icon-delete:before {
            content: "🗑️";
        }
        
        .search-form {
            max-width: 400px;
        }
        
        .input-group {
            display: flex;
            gap: 5px;
        }
        
        .pagination-container {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        .pagination {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .pagination-link {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--gray-700);
            background-color: white;
            transition: all 0.2s ease;
        }
        
        .pagination-link:hover {
            background-color: var(--gray-100);
        }
        
        .pagination-link.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination-info {
            color: var(--gray-600);
            font-size: 0.9rem;
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
    
    <!-- Progress Indicator -->
    <div id="progressOverlay" class="progress-overlay">
        <div class="progress-container">
            <div class="progress-spinner"></div>
            <h3 id="progressTitle">Processing...</h3>
            <p id="progressMessage">Please wait while we optimize your images.</p>
        </div>
    </div>
    
    <script>
        // Show progress indicator when optimizing images
        document.addEventListener('DOMContentLoaded', function() {
            // Get all buttons that trigger optimization
            const optimizeButtons = document.querySelectorAll('a[href*="optimize_image.php"]');
            
            optimizeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Show the progress overlay
                    const overlay = document.getElementById('progressOverlay');
                    overlay.style.visibility = 'visible';
                    overlay.style.opacity = '1';
                    
                    // Set appropriate message based on button text
                    const title = document.getElementById('progressTitle');
                    const message = document.getElementById('progressMessage');
                    
                    if (this.textContent.includes('All Media')) {
                        title.textContent = 'Optimizing All Media';
                        message.textContent = 'This may take several minutes. Please do not close this page.';
                    } else {
                        title.textContent = 'Optimizing Image';
                        message.textContent = 'Please wait while we optimize your image.';
                    }
                    
                    // Don't prevent default - let the link work normally
                });
            });
            
            // Also add progress indicator to file upload
            const uploadForm = document.querySelector('form.upload-form');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    // Only show progress if a file is selected
                    const fileInput = document.getElementById('media_file');
                    if (fileInput && fileInput.files.length > 0) {
                        const overlay = document.getElementById('progressOverlay');
                        overlay.style.visibility = 'visible';
                        overlay.style.opacity = '1';
                        
                        const title = document.getElementById('progressTitle');
                        const message = document.getElementById('progressMessage');
                        
                        title.textContent = 'Uploading and Optimizing';
                        message.textContent = 'Please wait while we upload and optimize your image.';
                    }
                });
            }
        });
    </script>
</body>
</html>