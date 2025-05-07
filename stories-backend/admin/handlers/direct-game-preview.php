<?php
/**
 * Direct Game Preview Handler
 *
 * This script renders a game preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the game ID from the query string
$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$game = null;
$error = null;

try {
    // Get game data
    if ($gameId > 0) {
        $stmt = $db->prepare("
            SELECT *
            FROM games
            WHERE id = ?
        ");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game) {
            $error = "Game not found.";
        }
    } else {
        $error = "Invalid game ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Function to render the game description with proper styling
function renderGameDescription($description) {
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
    <title><?php echo htmlspecialchars($game['title'] ?? 'Game Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .game-header {
            margin-bottom: 30px;
        }
        .game-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .game-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .game-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .game-details {
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
        .game-description {
            line-height: 1.8;
        }
        .game-description img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .game-cta {
            margin-top: 30px;
            text-align: center;
        }
        .game-cta a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .game-cta a:hover {
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
    <?php elseif ($game): ?>
        <div class="game-header">
            <h1 class="game-title"><?php echo htmlspecialchars($game['title']); ?></h1>
            <div class="game-meta">
                <?php if (!empty($game['developer'])): ?>
                    <span>Developer: <?php echo htmlspecialchars($game['developer']); ?></span>
                <?php endif; ?>
                <?php if (!empty($game['release_date'])): ?>
                    <span> | Released: <?php echo date('F j, Y', strtotime($game['release_date'])); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($game['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($game['cover_url']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" class="game-cover">
            <?php endif; ?>
        </div>

        <div class="game-details">
            <?php if (!empty($game['genre'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Genre:</div>
                    <div><?php echo htmlspecialchars($game['genre']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($game['platform'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Platform:</div>
                    <div><?php echo htmlspecialchars($game['platform']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($game['publisher'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Publisher:</div>
                    <div><?php echo htmlspecialchars($game['publisher']); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($game['price'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Price:</div>
                    <div>
                        <?php 
                        if ($game['price'] == 0) {
                            echo 'Free';
                        } else {
                            echo '$' . number_format($game['price'], 2);
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="game-description">
            <?php echo renderGameDescription($game['description']); ?>
        </div>

        <?php if (!empty($game['website_url'])): ?>
            <div class="game-cta">
                <a href="<?php echo htmlspecialchars($game['website_url']); ?>" target="_blank">Play Game</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error-message">
            <h2>No Game Found</h2>
            <p>The requested game could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
