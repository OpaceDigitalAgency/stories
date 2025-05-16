<?php
/**
 * View Debug File
 *
 * This page displays the contents of a debug file.
 */

// Set page title and current page
$pageTitle = 'View Debug File';
$currentPage = 'debug-logs';
$pageDescription = 'View the contents of a debug file';

// Include the header
require_once '../includes/auth.php';
require_once '../includes/header.php';

// Define the debug directory
$debugDir = dirname(dirname(dirname(__FILE__))) . '/services/ReviewFetcher/debug';

// Get the file name from the query string
$fileName = isset($_GET['file']) ? basename($_GET['file']) : '';
$filePath = $debugDir . '/' . $fileName;

// Check if the file exists
$fileExists = file_exists($filePath) && is_file($filePath);
$fileContent = '';
$fileType = '';
$fileSize = 0;
$fileDate = '';

if ($fileExists) {
    $fileSize = filesize($filePath);
    $fileDate = date('Y-m-d H:i:s', filemtime($filePath));
    
    // Determine file type
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($extension === 'html') {
        $fileType = 'HTML';
        // For HTML files, we'll display the source code
        $fileContent = htmlspecialchars(file_get_contents($filePath));
    } else if ($extension === 'log' || $extension === 'txt') {
        $fileType = 'Log';
        // For log files, we'll display the content with line breaks
        $fileContent = htmlspecialchars(file_get_contents($filePath));
    } else {
        $fileType = 'Unknown';
        $fileContent = 'Unsupported file type';
    }
}

// Add page actions
$pageActions = '
<div class="btn-group">
    <a href="debug-logs.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Debug Logs
    </a>
    ' . ($fileExists ? '
    <a href="?file=' . urlencode($fileName) . '&download=1" class="btn btn-success">
        <i class="fas fa-download"></i> Download File
    </a>
    <a href="debug-logs.php?delete=' . urlencode($fileName) . '" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this file?\');">
        <i class="fas fa-trash"></i> Delete File
    </a>
    ' : '') . '
</div>';

// Handle file download
if (isset($_GET['download']) && $_GET['download'] == 1 && $fileExists) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);
    readfile($filePath);
    exit;
}

?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <?php if ($fileExists): ?>
                        <?php echo htmlspecialchars($fileName); ?>
                    <?php else: ?>
                        File Not Found
                    <?php endif; ?>
                </h5>
                <?php echo $pageActions; ?>
            </div>
            
            <?php if ($fileExists): ?>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>File Type:</strong> <?php echo $fileType; ?> |
                        <strong>Size:</strong> <?php echo number_format($fileSize / 1024, 2); ?> KB |
                        <strong>Modified:</strong> <?php echo $fileDate; ?>
                    </div>
                    
                    <div class="file-content">
                        <?php if ($fileType === 'HTML'): ?>
                            <pre class="bg-light p-3 border rounded" style="max-height: 600px; overflow-y: auto;"><?php echo $fileContent; ?></pre>
                        <?php elseif ($fileType === 'Log'): ?>
                            <pre class="bg-light p-3 border rounded" style="max-height: 600px; overflow-y: auto;"><?php echo $fileContent; ?></pre>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <?php echo $fileContent; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div class="alert alert-danger">
                        The requested file does not exist or is not accessible.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
