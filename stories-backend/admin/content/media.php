<?php
require_once '../includes/auth.php';
$auth = new Auth($db);
$auth->requireLogin();

// Get action from URL
$action = $_GET['action'] ?? 'list';

// Define allowed file types
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];

// Define upload directory
$uploadDir = __DIR__ . '/../../uploads/media/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    switch ($_POST['action']) {
        case 'upload':
            if (!isset($_FILES['file'])) {
                $message = '<div class="error">No file uploaded</div>';
                break;
            }
            
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $message = '<div class="error">Upload failed: ' . htmlspecialchars($file['error']) . '</div>';
                break;
            }
            
            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!isset($allowedTypes[$mimeType])) {
                $message = '<div class="error">Invalid file type. Allowed types: JPG, PNG, GIF, WebP</div>';
                break;
            }
            
            // Generate safe filename
            $extension = $allowedTypes[$mimeType];
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Get image dimensions
            $dimensions = getimagesize($file['tmp_name']);
            if ($dimensions === false) {
                $message = '<div class="error">Invalid image file</div>';
                break;
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                $stmt = $db->prepare("INSERT INTO media (entity_type, type, url, width, height, alt_text) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([
                    $_POST['entity_type'],
                    $mimeType,
                    '/uploads/media/' . $filename,
                    $dimensions[0],
                    $dimensions[1],
                    $_POST['alt_text']
                ])) {
                    $message = '<div class="success">File uploaded successfully</div>';
                    header('Location: /admin/content/media.php?message=' . urlencode($message));
                    exit;
                } else {
                    unlink($filepath); // Remove file if database insert fails
                    $message = '<div class="error">Failed to save file information</div>';
                }
            } else {
                $message = '<div class="error">Failed to save uploaded file</div>';
            }
            break;
            
        case 'update':
            $stmt = $db->prepare("UPDATE media SET entity_type = ?, alt_text = ? WHERE id = ?");
            if ($stmt->execute([
                $_POST['entity_type'],
                $_POST['alt_text'],
                $_POST['id']
            ])) {
                $message = '<div class="success">Media updated successfully</div>';
                header('Location: /admin/content/media.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'delete':
            // Get file path
            $stmt = $db->prepare("SELECT url FROM media WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $media = $stmt->fetch();
            
            if ($media) {
                $filepath = __DIR__ . '/../../' . ltrim($media['url'], '/');
                
                // Delete from database
                $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    // Delete file
                    if (file_exists($filepath)) {
                        unlink($filepath);
                    }
                    $message = '<div class="success">Media deleted successfully</div>';
                    header('Location: /admin/content/media.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get media item for edit form
$media = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM media WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$media) {
        header('Location: /admin/content/media.php?message=' . urlencode('<div class="error">Media not found</div>'));
        exit;
    }
}

// Get all media for list view
$mediaItems = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM media ORDER BY created_at DESC");
    $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Define entity types
$entityTypes = [
    'story' => 'Story',
    'blog' => 'Blog Post',
    'author' => 'Author',
    'game' => 'Game',
    'general' => 'General'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Media - Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/main.css">
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .media-item {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 10px;
        }
        .media-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .media-info {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="/admin/index.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link">Content</a>
                <div class="dropdown-content">
                    <a href="/admin/content/stories.php" class="nav-link">Stories</a>
                    <a href="/admin/content/blog-posts.php" class="nav-link">Blog Posts</a>
                    <a href="/admin/content/games.php" class="nav-link">Games</a>
                </div>
            </li>
            <li class="nav-item"><a href="/admin/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if (isset($_GET['message'])): ?>
            <?php echo $_GET['message']; ?>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="card">
                <h1 class="card-title">Media Library</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Upload New Media</a>
                
                <div class="media-grid">
                    <?php foreach ($mediaItems as $item): ?>
                        <div class="media-item">
                            <img src="<?php echo htmlspecialchars($item['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['alt_text']); ?>"
                                 class="media-preview">
                            <div class="media-info">
                                <div><?php echo htmlspecialchars($entityTypes[$item['entity_type']] ?? $item['entity_type']); ?></div>
                                <div><?php echo $item['width']; ?> × <?php echo $item['height']; ?></div>
                            </div>
                            <div style="margin-top: 10px;">
                                <a href="?action=edit&id=<?php echo $item['id']; ?>" class="form-submit">Edit</a>
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="form-submit" style="background: #dc3545;">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="card">
                <h1 class="card-title"><?php echo $action === 'add' ? 'Upload New Media' : 'Edit Media'; ?></h1>
                
                <form method="POST" <?php echo $action === 'add' ? 'enctype="multipart/form-data"' : ''; ?>>
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'upload' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $media['id']; ?>">
                    <?php endif; ?>
                    
                    <?php if ($action === 'add'): ?>
                        <div class="form-group">
                            <label class="form-label" for="file">File</label>
                            <input type="file" id="file" name="file" class="form-input" required 
                                   accept=".jpg,.jpeg,.png,.gif,.webp">
                            <small class="form-help">Allowed types: JPG, PNG, GIF, WebP</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="entity_type">Content Type</label>
                        <select id="entity_type" name="entity_type" class="form-input" required>
                            <?php foreach ($entityTypes as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php 
                                    echo ($action === 'edit' && $media['entity_type'] === $value) ? 'selected' : ''; 
                                ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="alt_text">Alt Text</label>
                        <input type="text" id="alt_text" name="alt_text" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($media['alt_text']) : ''; ?>">
                        <small class="form-help">Descriptive text for accessibility</small>
                    </div>
                    
                    <?php if ($action === 'edit'): ?>
                        <div class="form-group">
                            <label class="form-label">Current Image</label>
                            <img src="<?php echo htmlspecialchars($media['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($media['alt_text']); ?>"
                                 style="max-width: 300px; margin-top: 10px;">
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="form-submit">
                        <?php echo $action === 'add' ? 'Upload Media' : 'Save Changes'; ?>
                    </button>
                    <a href="/admin/content/media.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>