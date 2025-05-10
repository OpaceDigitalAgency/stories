<?php
/**
 * Media Details View
 *
 * This page displays detailed information about a specific media item.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include the image optimizer library
require_once '../../includes/image_optimizer.php';

// Function to handle file paths for display and access
function getDisplayUrl($filePath) {
    // If it's already an absolute URL
    if (strpos($filePath, 'http') === 0) {
        // Clean up any instances of ../../ in the URL
        return preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $filePath);
    }

    // Check for optimized versions
    if (isset($GLOBALS['media']) && isset($GLOBALS['media']['id'])) {
        $mediaId = $GLOBALS['media']['id'];
        $db = $GLOBALS['db'];

        // Check if we have optimized versions
        if (!empty($GLOBALS['media']['medium_url'])) {
            $url = $GLOBALS['media']['medium_url'];
            // Clean up any instances of ../../ in the URL
            if (strpos($url, 'http') === 0) {
                return preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $url);
            }
            return $url;
        }

        if (!empty($GLOBALS['media']['large_url'])) {
            $url = $GLOBALS['media']['large_url'];
            // Clean up any instances of ../../ in the URL
            if (strpos($url, 'http') === 0) {
                return preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $url);
            }
            return $url;
        }
    }

    // If it's a relative URL starting with ../
    if (strpos($filePath, '../') === 0) {
        $relativePath = substr($filePath, 3); // Remove the leading ../
        return 'https://' . $_SERVER['HTTP_HOST'] . '/' . $relativePath;
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

    // For paths with uploads directory
    if (strpos($filePath, 'uploads/') !== false) {
        // Extract the path after 'uploads/'
        if (preg_match('/(.*?uploads\/)(.*?)$/', $filePath, $matches)) {
            return 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $matches[2];
        }
    }

    // If all else fails, just return the path with the host
    return 'https://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($filePath, '/');
}

// Function to check if a file exists (handling both local paths and URLs)
function fileExistsCheck($path) {
    // If it's a URL
    if (strpos($path, 'http') === 0) {
        // Clean up any instances of ../../ in the URL
        $cleanPath = preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $path);

        // Try with curl first (more reliable)
        if (function_exists('curl_init')) {
            $ch = curl_init($cleanPath);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode == 200;
        }

        // Fallback to get_headers
        $headers = @get_headers($cleanPath);
        return $headers && strpos($headers[0], '200') !== false;
    }

    // If it's a relative path with ../
    if (strpos($path, '../') === 0) {
        // Try to resolve the real path
        $basePath = realpath(dirname(__FILE__) . '/../../');
        $relativePath = substr($path, 3); // Remove the leading ../
        $fullPath = $basePath . '/' . $relativePath;
        return file_exists($fullPath);
    }

    // If it's a path with /uploads/
    if (strpos($path, '/uploads/') !== false || strpos($path, 'uploads/') !== false) {
        // Try to find the file in the uploads directory
        $uploadsPath = realpath(dirname(__FILE__) . '/../../uploads/');
        if ($uploadsPath) {
            // Extract the part after /uploads/
            if (preg_match('/(\/uploads\/|uploads\/)(.*?)$/', $path, $matches)) {
                $filePath = $uploadsPath . '/' . $matches[2];
                return file_exists($filePath);
            }
        }
    }

    // If it's a local file
    return file_exists($path);
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid media ID.";
    header("Location: media.php");
    exit;
}

$mediaId = (int)$_GET['id'];

try {
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

    // Get absolute URLs for all optimized versions
    if (!empty($media['thumbnail_url'])) {
        $media['thumbnail_url'] = getDisplayUrl($media['thumbnail_url']);
    }
    if (!empty($media['small_url'])) {
        $media['small_url'] = getDisplayUrl($media['small_url']);
    }
    if (!empty($media['medium_url'])) {
        $media['medium_url'] = getDisplayUrl($media['medium_url']);
    }
    if (!empty($media['large_url'])) {
        $media['large_url'] = getDisplayUrl($media['large_url']);
    }

} catch (PDOException $e) {
    error_log("View media error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading media details. Please try again.";
    header("Location: media.php");
    exit;
}

// Set page variables for header
$pageTitle = 'Media Details';
$currentPage = 'media';
$pageDescription = 'View details for ' . htmlspecialchars($media['filename']);

// Include header
require_once '../includes/header.php';
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo htmlspecialchars($media['filename']); ?></h2>
        <p class="section-description">
            <a href="media.php" class="text-primary">← Back to Media Library</a>
        </p>
    </div>
    <div class="section-body">
        <div class="row">
            <div class="col-md-6">
                <div class="media-preview mb-4">
                    <?php if ($isImage): ?>
                        <?php
                        // Generate alt text from filename if not provided
                        $altText = !empty($media['alt_text']) ? $media['alt_text'] : pathinfo($media['filename'], PATHINFO_FILENAME);
                        $altText = str_replace(['-', '_'], ' ', $altText);
                        $altText = ucfirst($altText);
                        ?>
                        <img src="<?php echo htmlspecialchars($displayUrl); ?>"
                             alt="<?php echo htmlspecialchars($altText); ?>"
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
                        <i class="fas fa-image" aria-hidden="true"></i> Optimize This Image
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
                        <?php
                        // Clean up the file path for display
                        $displayPath = $media['file_path'];
                        if (strpos($displayPath, 'http') === 0) {
                            // Clean up any instances of ../../ in the URL
                            $displayPath = preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $displayPath);
                        }
                        echo htmlspecialchars($displayPath);
                        ?>
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
                        <div class="image-sizes-list">
                            <?php if (!empty($media['thumbnail_url'])): ?>
                            <a href="<?php echo htmlspecialchars($media['thumbnail_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">Thumbnail</a>
                            <?php endif; ?>

                            <?php if (!empty($media['small_url'])): ?>
                            <a href="<?php echo htmlspecialchars($media['small_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">Small</a>
                            <?php endif; ?>

                            <?php if (!empty($media['medium_url'])): ?>
                            <a href="<?php echo htmlspecialchars($media['medium_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">Medium</a>
                            <?php endif; ?>

                            <?php if (!empty($media['large_url'])): ?>
                            <a href="<?php echo htmlspecialchars($media['large_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-2">Large</a>
                            <?php endif; ?>

                            <?php if (empty($media['thumbnail_url']) && empty($media['small_url']) &&
                                     empty($media['medium_url']) && empty($media['large_url'])): ?>
                            <span class="text-muted">No optimized sizes available</span>
                            <?php endif; ?>
                        </div>
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
                        <pre class="code-block"><code><?php
                        // Ensure we have an absolute URL for the HTML usage example
                        $absoluteUrl = $displayUrl;
                        if (strpos($absoluteUrl, 'http') !== 0) {
                            $absoluteUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($absoluteUrl, '/');
                        }
                        // Clean up any instances of ../../ in the URL
                        $absoluteUrl = preg_replace('/(https?:\/\/[^\/]+)\/\.\.\/\.\.\//', '$1/', $absoluteUrl);

                        // Generate alt text from filename if not provided
                        $altText = !empty($media['alt_text']) ? $media['alt_text'] : pathinfo($media['filename'], PATHINFO_FILENAME);
                        $altText = str_replace(['-', '_'], ' ', $altText);
                        $altText = ucfirst($altText);

                        if ($isImage): ?>&lt;img src="<?php echo htmlspecialchars($absoluteUrl); ?>" alt="<?php echo htmlspecialchars($altText); ?>"&gt;<?php else: ?>&lt;a href="<?php echo htmlspecialchars($absoluteUrl); ?>"&gt;Download <?php echo htmlspecialchars($media['filename']); ?>&lt;/a&gt;<?php endif; ?></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-4">
    <a href="media.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Media Library
    </a>
    <div>
        <a href="<?php echo htmlspecialchars($displayUrl); ?>" download class="btn btn-primary">
            <i class="fas fa-download" aria-hidden="true"></i> Download
        </a>
        <a href="media.php?delete=<?php echo $media['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?');">
            <i class="fas fa-trash" aria-hidden="true"></i> Delete
        </a>
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
    }

    .detail-item strong {
        display: inline-block;
        min-width: 120px;
        font-weight: 600;
    }

    .code-block {
        background-color: var(--gray-100);
        padding: 15px;
        border-radius: var(--radius-sm);
        font-family: monospace;
        overflow-x: auto;
        margin-top: 10px;
    }

    .image-sizes-list {
        margin-top: 10px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
