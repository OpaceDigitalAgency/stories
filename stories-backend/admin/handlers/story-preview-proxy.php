<?php
/**
 * Story Preview Proxy
 * 
 * This script acts as a proxy for loading external content in the story preview iframe.
 * It helps bypass Content Security Policy (CSP) restrictions by loading the content
 * from the same origin.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the URL from the query string
$url = isset($_GET['url']) ? $_GET['url'] : '';

// Validate URL
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo '<div style="padding: 20px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
            <h3>Invalid URL</h3>
            <p>The provided URL is not valid.</p>
          </div>';
    exit;
}

// Only allow specific domains
$allowedDomains = [
    'storiesfromtheweb.netlify.app',
    'storiesfromtheweb.org',
    'staging.storiesfromtheweb.org',
    'localhost:3000',
    '127.0.0.1:3000'
];

$urlParts = parse_url($url);
$domain = isset($urlParts['host']) ? $urlParts['host'] : '';
if (isset($urlParts['port'])) {
    $domain .= ':' . $urlParts['port'];
}

$allowed = false;
foreach ($allowedDomains as $allowedDomain) {
    if ($domain === $allowedDomain || preg_match('/\.' . preg_quote($allowedDomain, '/') . '$/', $domain)) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    echo '<div style="padding: 20px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
            <h3>Domain Not Allowed</h3>
            <p>The domain "' . htmlspecialchars($domain) . '" is not in the allowed list.</p>
          </div>';
    exit;
}

// Create a preview page that will load the content in an iframe with proper headers
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Preview</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        .preview-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .preview-header {
            background-color: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-header h3 {
            margin: 0;
            font-size: 16px;
            color: #495057;
        }
        .preview-header a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        .preview-iframe {
            flex-grow: 1;
            border: none;
            width: 100%;
            height: 100%;
        }
        .preview-message {
            padding: 20px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            margin: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-message">
            <p>This is a preview of how the story will appear on the frontend website. Some interactive elements may not work in this preview.</p>
            <p><a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Open in a new tab</a> for the full experience.</p>
        </div>
        <iframe 
            src="<?php echo htmlspecialchars($url); ?>" 
            class="preview-iframe" 
            allowfullscreen
            referrerpolicy="no-referrer"
            loading="lazy"
            title="Story Preview"
        ></iframe>
    </div>
    <script>
        // Handle messages from the parent window
        window.addEventListener('message', function(event) {
            // Only accept messages from our own domain
            if (event.origin !== window.location.origin) return;
            
            if (event.data.type === 'resize') {
                // Resize the iframe if needed
                const iframe = document.querySelector('.preview-iframe');
                if (iframe) {
                    iframe.style.height = event.data.height + 'px';
                }
            }
        });
    </script>
</body>
</html>
