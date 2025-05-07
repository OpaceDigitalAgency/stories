<?php
/**
 * Story Preview Proxy
 *
 * This script acts as a proxy for loading external content in the story preview iframe.
 * It helps bypass Content Security Policy (CSP) restrictions by creating a page that
 * can safely embed the external content with proper headers.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Set headers to allow iframe embedding and disable CSP restrictions
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-src * 'self' data:; frame-ancestors 'self'; default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

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

// Create a preview page with an iframe that will load the content
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .preview-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .preview-message {
            background-color: #fff3cd;
            border-bottom: 1px solid #ffeeba;
            padding: 10px 15px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-message p {
            margin: 0;
        }
        .preview-iframe {
            flex-grow: 1;
            border: none;
            width: 100%;
            height: 100%;
        }
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all 0.15s ease-in-out;
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-message">
            <p><strong>Preview Mode:</strong> This is how the story will appear on the frontend website.</p>
            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn">Open in New Tab</a>
        </div>

        <div id="loading-overlay" class="loading-overlay">
            <div class="spinner"></div>
            <p>Loading preview...</p>
        </div>

        <iframe
            id="preview-iframe"
            src="<?php echo htmlspecialchars($url); ?>"
            class="preview-iframe"
            allowfullscreen
            allow="fullscreen; accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            referrerpolicy="no-referrer-when-downgrade"
            loading="eager"
            title="Story Preview"
            onload="hideLoading()"
            onerror="showError()"
            sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-modals allow-top-navigation"
        ></iframe>
    </div>

    <script>
        // Hide loading overlay when iframe is loaded
        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';

            // Notify parent window that the preview is loaded
            try {
                window.parent.postMessage({ type: 'preview-loaded' }, '*');
            } catch (e) {
                console.error('Error sending message to parent window:', e);
            }
        }

        // Show error if iframe fails to load
        function showError() {
            const iframe = document.getElementById('preview-iframe');
            const container = document.querySelector('.preview-container');

            // Create error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-container';
            errorDiv.innerHTML = `
                <h3>Preview Error</h3>
                <p>We couldn't load the preview for this story. This could be due to:</p>
                <ul>
                    <li>The story page is not accessible</li>
                    <li>The content security policy is blocking the preview</li>
                    <li>The story URL is incorrect</li>
                </ul>
                <p>Please try opening the story directly in a new tab:</p>
                <p><a href="${iframe.src}" target="_blank" class="btn">Open in New Tab</a></p>
            `;

            // Hide iframe and loading overlay
            iframe.style.display = 'none';
            hideLoading();

            // Add error message
            container.appendChild(errorDiv);

            // Notify parent window of the error
            try {
                window.parent.postMessage({
                    type: 'preview-error',
                    message: 'Failed to load story preview. Please try opening in a new tab.'
                }, '*');
            } catch (e) {
                console.error('Error sending error message to parent window:', e);
            }
        }

        // Set timeout to check if iframe loaded correctly
        setTimeout(function() {
            const iframe = document.getElementById('preview-iframe');
            try {
                // Try to access iframe content - will throw error if blocked by CSP
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                // If we can access it but it's empty, show error
                if (!iframeDoc.body || !iframeDoc.body.innerHTML.trim()) {
                    showError();
                }
            } catch (e) {
                // If we can't access iframe content due to CORS, it's probably loading fine
                // This is normal for cross-origin iframes
                console.log('Cross-origin iframe detected, which is expected');
            }
        }, 5000);
    </script>
</body>
</html>
