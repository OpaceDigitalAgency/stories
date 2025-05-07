<?php
/**
 * Story Preview
 *
 * Displays a preview of a story before saving.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include header
require_once '../includes/header.php';

// Get the posted data
$title = $_POST['title'] ?? 'Preview';
$summary = $_POST['summary'] ?? '';
$content = $_POST['content'] ?? '';
$coverUrl = $_POST['cover_url'] ?? '';

// Ensure the cover URL is absolute
if ($coverUrl && strpos($coverUrl, 'http') !== 0) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $coverUrl = "$protocol://$host" . (strpos($coverUrl, '/') === 0 ? $coverUrl : "/$coverUrl");
}

// Custom CSS for the preview
echo '
<style>
    .preview-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background-color: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
    }

    .preview-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .preview-title {
        font-size: 2.5rem;
        margin-bottom: 10px;
        color: #333;
    }

    .preview-cover {
        max-width: 100%;
        height: auto;
        margin-bottom: 20px;
        border-radius: 5px;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
    }

    .preview-summary {
        font-size: 1.2rem;
        font-style: italic;
        color: #666;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }

    .preview-content {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #333;
    }

    .preview-content img {
        max-width: 100%;
        height: auto;
        margin: 10px 0;
        border-radius: 5px;
    }

    .preview-content h2 {
        font-size: 1.8rem;
        margin-top: 30px;
        margin-bottom: 15px;
        color: #444;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .preview-content h3 {
        font-size: 1.5rem;
        margin-top: 25px;
        margin-bottom: 10px;
        color: #555;
    }

    .preview-content p {
        margin-bottom: 15px;
    }

    .preview-content ul, .preview-content ol {
        margin-bottom: 15px;
        padding-left: 20px;
    }

    .preview-content blockquote {
        border-left: 3px solid #ddd;
        padding-left: 15px;
        margin-left: 0;
        color: #666;
        font-style: italic;
    }

    .preview-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }

    .preview-content table th, .preview-content table td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    .preview-content table th {
        background-color: #f2f2f2;
        text-align: left;
    }

    .preview-actions {
        margin-top: 30px;
        text-align: center;
    }

    .preview-actions .btn {
        margin: 0 5px;
    }
</style>
';
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h1>Story Preview</h1>
        <p class="text-muted">This is a preview of how your story will appear. It does not save any changes.</p>
    </div>

    <div class="section-body">
        <div class="preview-container">
            <div class="preview-header">
                <h1 class="preview-title"><?php echo htmlspecialchars($title); ?></h1>

                <?php if ($coverUrl): ?>
                <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($title); ?> Cover" class="preview-cover">
                <?php endif; ?>

                <?php if ($summary): ?>
                <div class="preview-summary">
                    <?php echo htmlspecialchars($summary); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="preview-content">
                <?php
                // Check if the content contains HTML tags
                if (strpos($content, '<') !== false && strpos($content, '>') !== false) {
                    // It's HTML content, output directly
                    echo $content;
                } else {
                    // It's plain text, convert newlines to <br> tags
                    echo nl2br(htmlspecialchars($content));
                }
                ?>
            </div>

            <div class="preview-actions">
                <button type="button" class="btn btn-secondary" onclick="window.close()">Close Preview</button>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
