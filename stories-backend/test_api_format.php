<?php
/**
 * Test API Format
 * 
 * This script tests the API endpoints and checks if the admin interface can handle the response format.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to output text
function output($text, $isHtml = false) {
    echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
}

// Set content type
header('Content-Type: text/html; charset=utf-8');
output('<!DOCTYPE html>
<html>
<head>
    <title>Test API Format</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .endpoint { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0066cc; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px; }
        .response { margin-top: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test API Format</h1>
', true);

output("<h2>API Endpoints</h2>");

// Test endpoints
$endpoints = [
    'stories' => '/api/v1/stories',
    'authors' => '/api/v1/authors',
    'games' => '/api/v1/games',
    'directory-items' => '/api/v1/directory-items',
    'ai-tools' => '/api/v1/ai-tools'
];

// Create a table for results
output("<table>", true);
output("<tr><th>Endpoint</th><th>Status</th><th>Format</th><th>Details</th></tr>", true);

// Function to test an endpoint
function testEndpoint($name, $path) {
    // Build the full URL
    $baseUrl = "https://api.storiesfromtheweb.org";
    $url = $baseUrl . $path;
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    // Parse response
    $isJson = false;
    $jsonError = '';
    $decodedResponse = null;
    
    try {
        $decodedResponse = json_decode($response, true);
        $jsonError = json_last_error_msg();
        $isJson = json_last_error() === JSON_ERROR_NONE;
    } catch (Exception $e) {
        $jsonError = $e->getMessage();
    }
    
    // Check response format
    $format = "Invalid";
    $details = "";
    
    if ($isJson) {
        if (isset($decodedResponse['data'])) {
            $format = "Nested";
            $details = "Response has 'data' key";
        } else if (is_array($decodedResponse) && !empty($decodedResponse) && isset($decodedResponse[0])) {
            $format = "Flat";
            $details = "Response is a flat array";
        } else {
            $format = "Unknown";
            $details = "Response format is not recognized";
        }
    } else {
        $details = "Response is not valid JSON: $jsonError";
    }
    
    // Output results
    output("<tr>", true);
    output("<td>$name</td>", true);
    output("<td>" . ($httpCode >= 200 && $httpCode < 300 ? "<span class='success'>$httpCode</span>" : "<span class='error'>$httpCode</span>") . "</td>", true);
    output("<td>" . ($format === "Invalid" ? "<span class='error'>$format</span>" : "<span class='success'>$format</span>") . "</td>", true);
    output("<td><button onclick=\"toggleResponse('$name')\">View Response</button><div id='$name-details' style='display:none;'>$details<pre class='response'>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? "..." : "") . "</pre></div></td>", true);
    output("</tr>", true);
    
    return [
        'status' => $httpCode,
        'format' => $format,
        'details' => $details,
        'response' => $decodedResponse
    ];
}

// Test each endpoint
$results = [];
foreach ($endpoints as $name => $path) {
    $results[$name] = testEndpoint($name, $path);
}

output("</table>", true);

// Check for inconsistencies
output("<h2>Analysis</h2>");

$formats = array_unique(array_column($results, 'format'));
if (count($formats) > 1) {
    output("<div class='warning'>Inconsistent response formats detected!</div>", true);
    output("<p>The API endpoints are returning different response formats:</p>", true);
    
    foreach ($formats as $format) {
        $endpointsWithFormat = array_keys(array_filter($results, function($result) use ($format) {
            return $result['format'] === $format;
        }));
        
        output("<p><strong>$format format:</strong> " . implode(', ', $endpointsWithFormat) . "</p>", true);
    }
    
    output("<p>This can cause issues with the admin interface, which may expect a consistent format.</p>", true);
} else {
    output("<div class='success'>All endpoints are using the same response format: " . reset($formats) . "</div>", true);
}

// Add JavaScript for toggling response details
output("<script>
function toggleResponse(name) {
    var details = document.getElementById(name + '-details');
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>", true);

// Close HTML
output('
    </div>
</body>
</html>', true);