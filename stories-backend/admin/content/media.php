<?php
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

// Initialize variables
$media = [];
$error = null;
$success = null;

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
                
                $success = "File uploaded successfully";
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

    // Get all media files
    $media = $db->query("SELECT * FROM media ORDER BY created_at DESC")->fetchAll();

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
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <form method="POST" action="../logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="content-header">
            <h1>Media</h1>
            <form method="GET" action="../dashboard.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Dashboard</button>
            </form>
        </div>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section">
            <h2>Upload New Media</h2>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label class="form-label" for="media_file">File <span class="required">*</span></label>
                    <input type="file" id="media_file" name="media_file" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="alt_text">Alt Text</label>
                    <input type="text" id="alt_text" name="alt_text" class="form-input" placeholder="Describe the image for accessibility">
                </div>
                <div class="form-group">
                    <button type="submit" class="form-submit">Upload</button>
                </div>
            </form>
        </div>

        <div class="content-section">
            <h2>Media Library</h2>
            <?php if (empty($media)): ?>
                <p class="no-items">No media files found.</p>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($media as $item): ?>
                        <div class="media-item">
                            <?php 
                            $isImage = strpos($item['file_type'], 'image/') === 0;
                            $thumbnailPath = $isImage ? $item['file_path'] : '../assets/images/file-icon.png';
                            ?>
                            <div class="media-thumbnail">
                                <?php if ($isImage): ?>
                                    <img src="<?php echo htmlspecialchars($item['file_path']); ?>" alt="<?php echo htmlspecialchars($item['alt_text'] ?? $item['filename']); ?>">
                                <?php else: ?>
                                    <div class="file-icon"><?php echo pathinfo($item['filename'], PATHINFO_EXTENSION); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="media-info">
                                <p class="media-filename"><?php echo htmlspecialchars($item['filename']); ?></p>
                                <p class="media-date"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></p>
                                <div class="media-actions">
                                    <a href="<?php echo htmlspecialchars($item['file_path']); ?>" target="_blank" class="action-link">View</a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="action-link delete" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .nav-link {
            background: none;
            border: none;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .nav-link:hover {
            background: #f5f5f5;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .content-header h1 {
            margin: 0;
        }
        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .content-section h2 {
            margin-top: 0;
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .upload-form {
            max-width: 600px;
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .media-item {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .media-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .media-thumbnail {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
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
            background: #e9e9e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            border-radius: 4px;
        }
        .media-info {
            padding: 10px;
        }
        .media-filename {
            margin: 0 0 5px 0;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .media-date {
            margin: 0 0 10px 0;
            font-size: 0.8rem;
            color: #666;
        }
        .media-actions {
            display: flex;
            justify-content: space-between;
        }
        .action-link {
            color: #4a6cf7;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .action-link.delete {
            color: #dc3545;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        .no-items {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
    </style>
</body>
</html>