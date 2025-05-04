<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'View Media';
$currentPage = 'view-media';

require_once '../../simple_auth.php';

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

// Include the image optimizer library
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

// Function to check if a file exists, handling both local paths and URLs
function fileExistsCheck($filePath) {
    // If it's a local path
    if (strpos($filePath, 'http') !== 0) {
        return file_exists($filePath);
    }
    
    // If it's a URL, try to fetch headers
    $headers = @get_headers($filePath);
    return $headers && strpos($headers[0], '200') !== false;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid media ID.";
    header("Location: media.php");
    exit;
}

$mediaId = (int)$_GET['id'];

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

    // Get media details
    $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch();

    if (!$media) {
        $_SESSION['error'] = "Media not found.";
        header("Location: media.php");
        exit;
    }

    // Get file size in human-readable format
    function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    $fileSize = formatFileSize($media['file_size']);
    $isImage = strpos($media['file_type'], 'image/') === 0;
    $fileExtension = pathinfo($media['filename'], PATHINFO_EXTENSION);

    // Check if file exists (handling both local paths and URLs)
    $fileExists = fileExistsCheck($media['file_path']);
    
    // Get display URL for the file
    $displayUrl = getDisplayUrl($media['file_path']);

} catch (PDOException $e) {
    error_log("View media error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading media details. Please try again.";
    header("Location: media.php");
    exit;
}
?>

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
                <h1 class="page-title">View Media</h1>
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
                <h2 class="section-title"><?php echo htmlspecialchars($media['filename']); ?></h2>
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
                        <div class="media-details">
                            <div class="detail-item">
                                <strong>Filename:</strong> 
                                <?php echo htmlspecialchars($media['filename']); ?>
                            </div>
                            
                            <div class="detail-item">
                                <strong>File Type:</strong> 
                                <?php echo htmlspecialchars($media['file_type']); ?>
                            </div>
                            
                            <div class="detail-item">
                                <strong>File Size:</strong> 
                                <?php echo $fileSize; ?>
                            </div>
                            
                            <div class="detail-item">
                                <strong>File Path:</strong>
                                <?php echo htmlspecialchars($media['file_path']); ?>
                                <?php if (!$fileExists): ?>
                                    <span class="text-danger">(File not found on server)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="detail-item">
                                <strong>Display URL:</strong>
                                <?php echo htmlspecialchars($displayUrl); ?>
                            </div>
                            
                            <?php if ($isImage): ?>
                            <div class="detail-item">
                                <strong>Available Sizes:</strong>
                                <ul class="image-sizes-list">
                                    <?php if (!empty($media['thumbnail_url'])): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($media['thumbnail_url']); ?>" target="_blank">Thumbnail</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($media['small_url'])): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($media['small_url']); ?>" target="_blank">Small</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($media['medium_url'])): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($media['medium_url']); ?>" target="_blank">Medium</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($media['large_url'])): ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($media['large_url']); ?>" target="_blank">Large</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($media['thumbnail_url']) && empty($media['small_url']) &&
                                             empty($media['medium_url']) && empty($media['large_url'])): ?>
                                    <li>No optimized sizes available</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($media['alt_text'])): ?>
                            <div class="detail-item">
                                <strong>Alt Text:</strong> 
                                <?php echo htmlspecialchars($media['alt_text']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <strong>Uploaded:</strong> 
                                <?php echo date('F j, Y, g:i a', strtotime($media['created_at'])); ?>
                            </div>
                            
                            <div class="detail-item">
                                <strong>Last Updated:</strong> 
                                <?php echo date('F j, Y, g:i a', strtotime($media['updated_at'])); ?>
                            </div>
                            
                            <div class="detail-item mt-4">
                                <strong>Usage in HTML:</strong>
                                <?php if ($isImage): ?>
                                <pre class="code-block"><code>&lt;img src="<?php echo htmlspecialchars($displayUrl); ?>" alt="<?php echo htmlspecialchars($media['alt_text'] ?? $media['filename']); ?>"&gt;</code></pre>
                                <?php else: ?>
                                <pre class="code-block"><code>&lt;a href="<?php echo htmlspecialchars($displayUrl); ?>"&gt;Download <?php echo htmlspecialchars($media['filename']); ?>&lt;/a&gt;</code></pre>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="media.php" class="btn btn-secondary">
                Back to Media Library
            </a>
            <div>
                <a href="<?php echo htmlspecialchars($displayUrl); ?>" target="_blank" class="btn btn-primary">
                    <span class="icon-download"></span> Download
                </a>
            </div>
        </div>
    </div>
    
    <style>
        .media-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            min-height: 300px;
        }
        
        .img-preview {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }
        
        .file-icon-large {
            width: 150px;
            height: 150px;
            background: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            color: var(--gray-700);
            border-radius: var(--radius-md);
        }
        
        .media-details {
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .code-block {
            background-color: var(--gray-100);
            padding: 15px;
            border-radius: var(--radius-sm);
            overflow-x: auto;
            font-family: monospace;
            margin-top: 10px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }
        
        .col-md-6 {
            flex: 0 0 calc(50% - 20px);
            padding: 10px;
        }
        
        @media (max-width: 768px) {
            .col-md-6 {
                flex: 0 0 calc(100% - 20px);
            }
        }
        
        .mt-4 {
            margin-top: 1.5rem;
        }
        
        .icon-download:before {
            content: "↓";
        }
        
        .image-sizes-list {
            list-style: none;
            padding-left: 0;
            margin-top: 10px;
        }
        
        .image-sizes-list li {
            margin-bottom: 5px;
            padding: 5px 10px;
            background-color: var(--gray-50);
            border-radius: var(--radius-sm);
            display: inline-block;
            margin-right: 5px;
        }
        
        .image-sizes-list li a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .image-sizes-list li a:hover {
            text-decoration: underline;
        }
        
        .icon-image:before {
            content: "🖼️";
        }
    </style>

// Include footer
include '../includes/footer.php';
