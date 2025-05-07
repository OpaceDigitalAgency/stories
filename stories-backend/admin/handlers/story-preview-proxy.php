<?php
/**
 * Story Preview Proxy
 *
 * This script acts as a proxy for loading external content in the story preview.
 * It helps bypass Content Security Policy (CSP) restrictions by fetching the content
 * and displaying it directly, rather than using an iframe.
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

// Fetch the content from the URL
$options = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
        ]
    ]
];

$context = stream_context_create($options);

try {
    // Attempt to fetch the content
    $content = @file_get_contents($url, false, $context);

    if ($content === false) {
        throw new Exception("Failed to fetch content from URL");
    }

    // Extract the story content from the fetched HTML
    // This is a simplified approach - you might need to adjust based on the actual structure
    if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $content, $matches)) {
        $storyContent = $matches[1];
    } else if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $content, $matches)) {
        $storyContent = $matches[1];
    } else if (preg_match('/<div[^>]*class="[^"]*story-content[^"]*"[^>]*>(.*?)<\/div>/is', $content, $matches)) {
        $storyContent = $matches[1];
    } else {
        // If we can't find specific content, use the whole body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
            $storyContent = $matches[1];
        } else {
            $storyContent = $content;
        }
    }

    // Create a clean preview page with the extracted content
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Preview</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .preview-banner {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .preview-content {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 20px;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        h1, h2, h3 {
            color: #212529;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            text-decoration: none;
        }
        .btn:hover {
            color: #fff;
            background-color: #0069d9;
            border-color: #0062cc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="preview-banner">
        <p><strong>Preview Mode:</strong> This is a preview of how the story will appear on the frontend website. Some interactive elements may not work in this preview.</p>
        <p><a href="' . htmlspecialchars($url) . '" target="_blank" class="btn">Open in New Tab</a> for the full experience.</p>
    </div>

    <div class="preview-content">
        ' . $storyContent . '
    </div>

    <script>
        // Fix relative URLs in the content
        document.addEventListener("DOMContentLoaded", function() {
            const baseUrl = "' . htmlspecialchars($url) . '";
            const baseUrlObj = new URL(baseUrl);
            const baseOrigin = baseUrlObj.origin;
            const basePath = baseUrlObj.pathname.substring(0, baseUrlObj.pathname.lastIndexOf("/") + 1);

            // Fix links
            document.querySelectorAll("a").forEach(function(a) {
                if (a.href && a.href.startsWith(window.location.origin)) {
                    const relPath = a.getAttribute("href");
                    if (relPath && !relPath.startsWith("http")) {
                        if (relPath.startsWith("/")) {
                            a.href = baseOrigin + relPath;
                        } else {
                            a.href = baseOrigin + basePath + relPath;
                        }
                    }
                }
            });

            // Fix images
            document.querySelectorAll("img").forEach(function(img) {
                const src = img.getAttribute("src");
                if (src && !src.startsWith("http")) {
                    if (src.startsWith("/")) {
                        img.src = baseOrigin + src;
                    } else {
                        img.src = baseOrigin + basePath + src;
                    }
                }
            });
        });
    </script>
</body>
</html>';

} catch (Exception $e) {
    // If fetching fails, show an error and a link to open in a new tab
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Preview Error</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .error-container {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            text-decoration: none;
        }
        .btn:hover {
            color: #fff;
            background-color: #0069d9;
            border-color: #0062cc;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>Preview Error</h2>
        <p>We couldn\'t load the preview for this story. This could be due to:</p>
        <ul>
            <li>The story page is not accessible</li>
            <li>The content security policy is blocking the preview</li>
            <li>The story URL is incorrect</li>
        </ul>
        <p>Please try opening the story directly in a new tab:</p>
        <p><a href="' . htmlspecialchars($url) . '" target="_blank" class="btn">Open in New Tab</a></p>
    </div>
</body>
</html>';
}
