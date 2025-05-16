<?php
/**
 * Debug Logs
 *
 * This page displays debug logs and HTML files for review fetchers.
 */

// Set page title and current page
$pageTitle = 'Debug Logs';
$currentPage = 'debug-logs';
$pageDescription = 'View debug logs and HTML files for review fetchers';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Define the debug directory
$debugDir = dirname(dirname(dirname(__FILE__))) . '/services/ReviewFetcher/debug';

// Create the debug directory if it doesn't exist
if (!is_dir($debugDir)) {
    mkdir($debugDir, 0777, true);
}

// Make sure the directory is writable
chmod($debugDir, 0777);

// Handle file deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $fileName = basename($_GET['delete']);
    $filePath = $debugDir . '/' . $fileName;

    if (file_exists($filePath) && is_file($filePath)) {
        unlink($filePath);
        echo '<div class="alert alert-success">File deleted: ' . htmlspecialchars($fileName) . '</div>';
    } else {
        echo '<div class="alert alert-danger">File not found: ' . htmlspecialchars($fileName) . '</div>';
    }
}

// Handle clearing all files
if (isset($_GET['clear_all']) && $_GET['clear_all'] === 'yes') {
    $files = glob($debugDir . '/*');
    $count = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }

    echo '<div class="alert alert-success">' . $count . ' files deleted from the debug directory.</div>';
}

// Get all files in the debug directory
$htmlFiles = glob($debugDir . '/*.html');
$logFiles = glob($debugDir . '/*.log');
$txtFiles = glob($debugDir . '/*.txt');

// Sort files by modification time (newest first)
$sortFiles = function($files) {
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    return $files;
};

$htmlFiles = $sortFiles($htmlFiles);
$logFiles = $sortFiles(array_merge($logFiles, $txtFiles));

// Add page actions
$pageActions = '
<div class="btn-group">
    <a href="book-import-tool.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Import Tool
    </a>
    <a href="?clear_all=yes" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete all debug files?\');">
        <i class="fas fa-trash"></i> Clear All Files
    </a>
</div>';

?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Debug Information</h5>
            </div>
            <div class="card-body">
                <p>This page displays debug logs and HTML files for review fetchers. These files are useful for diagnosing issues with the review fetching process.</p>

                <div class="alert alert-info">
                    <strong>Debug Directory:</strong> <?php echo htmlspecialchars($debugDir); ?>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Log Files (<?php echo count($logFiles); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (empty($logFiles)): ?>
                                        <div class="list-group-item">No log files found.</div>
                                    <?php else: ?>
                                        <?php foreach ($logFiles as $file): ?>
                                            <?php
                                            $fileName = basename($file);
                                            $fileSize = filesize($file);
                                            $fileDate = date('Y-m-d H:i:s', filemtime($file));
                                            $fileUrl = 'view-debug-file.php?file=' . urlencode($fileName);
                                            ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <i class="fas fa-file-alt text-primary"></i>
                                                            <?php echo htmlspecialchars($fileName); ?>
                                                        </h5>
                                                        <p class="mb-1 text-muted">
                                                            <small>
                                                                Size: <?php echo number_format($fileSize / 1024, 2); ?> KB |
                                                                Modified: <?php echo $fileDate; ?>
                                                            </small>
                                                        </p>
                                                    </div>
                                                    <div class="btn-group">
                                                        <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="?delete=<?php echo urlencode($fileName); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this file?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">HTML Files (<?php echo count($htmlFiles); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php if (empty($htmlFiles)): ?>
                                        <div class="list-group-item">No HTML files found.</div>
                                    <?php else: ?>
                                        <?php foreach ($htmlFiles as $file): ?>
                                            <?php
                                            $fileName = basename($file);
                                            $fileSize = filesize($file);
                                            $fileDate = date('Y-m-d H:i:s', filemtime($file));
                                            $fileUrl = 'view-debug-file.php?file=' . urlencode($fileName);
                                            ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <i class="fas fa-file-code text-info"></i>
                                                            <?php echo htmlspecialchars($fileName); ?>
                                                        </h5>
                                                        <p class="mb-1 text-muted">
                                                            <small>
                                                                Size: <?php echo number_format($fileSize / 1024, 2); ?> KB |
                                                                Modified: <?php echo $fileDate; ?>
                                                            </small>
                                                        </p>
                                                    </div>
                                                    <div class="btn-group">
                                                        <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="?delete=<?php echo urlencode($fileName); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this file?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
