<?php
/**
 * Direct AI Tool Preview Handler
 *
 * This script renders an AI tool preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the AI tool ID from the query string
$toolId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$tool = null;
$category = null;
$error = null;

try {
    // Get AI tool data
    if ($toolId > 0) {
        $stmt = $db->prepare("
            SELECT a.*, c.name as category_name
            FROM ai_tools a
            LEFT JOIN ai_tool_categories c ON a.category_id = c.id
            WHERE a.id = ?
        ");
        $stmt->execute([$toolId]);
        $tool = $stmt->fetch();

        if (!$tool) {
            $error = "AI tool not found.";
        }
    } else {
        $error = "Invalid AI tool ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Function to render the AI tool description with proper styling
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

// Function to format pricing type
function formatPricingType($type) {
    switch ($type) {
        case 'free':
            return 'Free';
        case 'freemium':
            return 'Freemium';
        case 'paid':
            return 'Paid';
        case 'subscription':
            return 'Subscription';
        default:
            return ucfirst($type);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tool['title'] ?? 'AI Tool Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .tool-header {
            margin-bottom: 30px;
        }
        .tool-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .tool-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .tool-category {
            display: inline-block;
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .tool-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .tool-details {
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
        .tool-description {
            line-height: 1.8;
        }
        .tool-description img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .tool-cta {
            margin-top: 30px;
            text-align: center;
        }
        .tool-cta a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .tool-cta a:hover {
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
        .rating {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .stars {
            color: #ffc107;
            font-size: 18px;
            margin-right: 5px;
        }
        .rating-value {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php elseif ($tool): ?>
        <div class="tool-header">
            <h1 class="tool-title"><?php echo htmlspecialchars($tool['title']); ?></h1>
            
            <?php if (!empty($tool['category_name'])): ?>
                <div class="tool-category"><?php echo htmlspecialchars($tool['category_name']); ?></div>
            <?php endif; ?>

            <?php if (!empty($tool['rating']) && $tool['rating'] > 0): ?>
                <div class="rating">
                    <div class="stars">
                        <?php
                        $rating = floatval($tool['rating']);
                        $fullStars = floor($rating);
                        $halfStar = $rating - $fullStars >= 0.5;
                        
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '★';
                        }
                        
                        if ($halfStar) {
                            echo '☆';
                        }
                        ?>
                    </div>
                    <div class="rating-value"><?php echo number_format($rating, 1); ?>/5</div>
                </div>
            <?php endif; ?>

            <?php if (!empty($tool['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($tool['cover_url']); ?>" alt="<?php echo htmlspecialchars($tool['title']); ?>" class="tool-cover">
            <?php endif; ?>
        </div>

        <div class="tool-details">
            <?php if (!empty($tool['pricing_type'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Pricing:</div>
                    <div><?php echo formatPricingType($tool['pricing_type']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($tool['price_info'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Price Info:</div>
                    <div><?php echo htmlspecialchars($tool['price_info']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tool-description">
            <?php echo renderDescription($tool['description']); ?>
        </div>

        <?php if (!empty($tool['tool_url'])): ?>
            <div class="tool-cta">
                <a href="<?php echo htmlspecialchars($tool['tool_url']); ?>" target="_blank">Try This AI Tool</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error-message">
            <h2>No AI Tool Found</h2>
            <p>The requested AI tool could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
