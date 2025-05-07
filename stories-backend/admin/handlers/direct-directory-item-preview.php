<?php
/**
 * Direct Directory Item Preview Handler
 *
 * This script renders a directory item preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the directory item ID from the query string
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$item = null;
$category = null;
$error = null;

try {
    // Get directory item data
    if ($itemId > 0) {
        $stmt = $db->prepare("
            SELECT d.*, c.name as category_name
            FROM directory_items d
            LEFT JOIN directory_categories c ON d.category_id = c.id
            WHERE d.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            $error = "Directory item not found.";
        }
    } else {
        $error = "Invalid directory item ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Function to render the directory item description with proper styling
function renderDescription($description) {
    // Check if the content is HTML
    if (strpos($description, '<') !== false && strpos($description, '>') !== false) {
        // It's HTML, process it to ensure images have absolute URLs
        $dom = new DOMDocument();

        // Use error suppression to avoid warnings about HTML5 tags
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $description);

        // Process all images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // If the src is not an absolute URL, make it absolute
            if ($src && strpos($src, 'http') !== 0) {
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $newSrc = "$protocol://$host" . (strpos($src, '/') === 0 ? $src : "/$src");
                $img->setAttribute('src', $newSrc);
            }
        }

        // Extract the body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            // Convert back to HTML string
            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
            return $html;
        }

        // Fallback if DOM processing fails
        return $description;
    } else {
        // It's plain text, convert newlines to <br> tags
        return nl2br(htmlspecialchars($description));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title'] ?? 'Directory Item Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .item-header {
            margin-bottom: 30px;
        }
        .item-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .item-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .item-category {
            display: inline-block;
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .item-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .item-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .item-description {
            line-height: 1.8;
        }
        .item-description img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .item-cta {
            margin-top: 30px;
            text-align: center;
        }
        .item-cta a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .item-cta a:hover {
            background-color: #45a049;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php elseif ($item): ?>
        <div class="item-header">
            <h1 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h1>
            
            <?php if (!empty($item['category_name'])): ?>
                <div class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
            <?php endif; ?>

            <?php if (!empty($item['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($item['cover_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-cover">
            <?php endif; ?>
        </div>

        <div class="item-details">
            <?php if (!empty($item['contact_email'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Email:</div>
                    <div><?php echo htmlspecialchars($item['contact_email']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($item['contact_phone'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Phone:</div>
                    <div><?php echo htmlspecialchars($item['contact_phone']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($item['address'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Address:</div>
                    <div><?php echo nl2br(htmlspecialchars($item['address'])); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($item['price_range'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Price Range:</div>
                    <div><?php echo htmlspecialchars($item['price_range']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="item-description">
            <?php echo renderDescription($item['description']); ?>
        </div>

        <?php if (!empty($item['website_url'])): ?>
            <div class="item-cta">
                <a href="<?php echo htmlspecialchars($item['website_url']); ?>" target="_blank">Visit Website</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error-message">
            <h2>No Directory Item Found</h2>
            <p>The requested directory item could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
