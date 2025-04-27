<?php
/**
 * API Test Suite
 * 
 * This script provides comprehensive testing for the API endpoints, including:
 * - Testing all API endpoints for availability
 * - Checking response format consistency
 * - Validating story structure and required fields
 * - Testing API operations (GET, PUT)
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>API Test Suite</title>
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
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tabs { display: flex; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #f0f0f0; cursor: pointer; margin-right: 5px; border-radius: 5px 5px 0 0; }
        .tab.active { background: #0066cc; color: white; }
        .action-button { display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Test Suite</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab(\'format-test\')">API Format Test</div>
            <div class="tab" onclick="showTab(\'endpoint-test\')">Endpoint Tests</div>
            <div class="tab" onclick="showTab(\'story-validation\')">Story Validation</div>
            <div class="tab" onclick="showTab(\'api-operations\')">API Operations</div>
        </div>
', true);
}

// Base URL for API
$baseUrl = "https://api.storiesfromtheweb.org";

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $isWeb, $baseUrl;
    
    $url = $baseUrl . $endpoint;
    
    output("Making $method request to: $url");
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set data if provided
    if ($data) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        
        output("Request data:");
        output(json_encode($data, JSON_PRETTY_PRINT));
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    output("HTTP Status: $httpCode");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        if ($isWeb) output("<div class='success'>Success</div>", true);
        else output("Success");
    } else {
        if ($isWeb) output("<div class='error'>Error</div>", true);
        else output("Error");
    }
    
    // Try to parse JSON
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse !== null) {
        output("Response (JSON):");
        output(json_encode($jsonResponse, JSON_PRETTY_PRINT));
    } else {
        output("Response (Raw):");
        output($response);
    }
    
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'response' => $jsonResponse,
        'raw' => $response
    ];
}

// Function to test an endpoint format
function testEndpointFormat($name, $path) {
    global $baseUrl;
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

// Function to validate story structure
function validateStoryStructure($story) {
    // Required fields to check
    $requiredFields = [
        'id', 'title', 'slug', 'content', 'excerpt', 'is_published',
        'featured', 'average_rating', 'cover_url', 'created_at', 'updated_at'
    ];
    
    // Check all required fields exist
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($story[$field])) {
            $missingFields[] = $field;
        }
    }
    
    $results = [
        'success' => empty($missingFields),
        'missing_fields' => $missingFields,
        'author_check' => false,
        'tags_check' => false
    ];
    
    // Check author structure if available
    if (isset($story['author']) && is_array($story['author'])) {
        $results['author_check'] = isset($story['author']['name']) && 
                                  isset($story['author']['slug']);
    }
    
    // Check tags structure if available
    if (isset($story['tags']) && is_array($story['tags'])) {
        $results['tags_check'] = true;
        foreach ($story['tags'] as $tag) {
            if (!isset($tag['name']) || !isset($tag['slug'])) {
                $results['tags_check'] = false;
                break;
            }
        }
    }
    
    return $results;
}

// Start API Format Test
if ($isWeb) {
    output('<div id="format-test" class="tab-content active">', true);
    output('<h2>API Format Test</h2>', true);
}

output("Testing API Endpoints Format");
output("-------------------------");

// Test endpoints
$endpoints = [
    'stories' => '/api/v1/stories',
    'authors' => '/api/v1/authors',
    'games' => '/api/v1/games',
    'directory-items' => '/api/v1/directory-items',
    'ai-tools' => '/api/v1/ai-tools',
    'blog-posts' => '/api/v1/blog-posts',
    'tags' => '/api/v1/tags'
];

// Create a table for results
output("<table>", true);
output("<tr><th>Endpoint</th><th>Status</th><th>Format</th><th>Details</th></tr>", true);

// Test each endpoint
$results = [];
foreach ($endpoints as $name => $path) {
    $results[$name] = testEndpointFormat($name, $path);
}

output("</table>", true);

// Check for inconsistencies
output("<h3>Analysis</h3>");

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

if ($isWeb) {
    output('</div>', true);
}

// Start Endpoint Tests
if ($isWeb) {
    output('<div id="endpoint-test" class="tab-content">', true);
    output('<h2>Endpoint Tests</h2>', true);
}

output("Testing API Endpoints");
output("-------------------");

// Test GET /stories
output("<div class='endpoint'>", true);
output("<h3>GET /stories</h3>", true);
$storiesResult = makeApiRequest('/api/v1/stories');
output("</div>", true);

// Test GET /authors
output("<div class='endpoint'>", true);
output("<h3>GET /authors</h3>", true);
$authorsResult = makeApiRequest('/api/v1/authors');
output("</div>", true);

// Test GET /tags
output("<div class='endpoint'>", true);
output("<h3>GET /tags</h3>", true);
$tagsResult = makeApiRequest('/api/v1/tags');
output("</div>", true);

if ($isWeb) {
    output('</div>', true);
}

// Start Story Validation
if ($isWeb) {
    output('<div id="story-validation" class="tab-content">', true);
    output('<h2>Story Validation</h2>', true);
}

output("Validating Story Structure");
output("------------------------");

// Get a story to validate
$storyResult = makeApiRequest('/api/v1/stories/1');

if ($storyResult['success'] && isset($storyResult['response'])) {
    $story = $storyResult['response'];
    
    // If we have a story, validate its structure
    if (is_array($story)) {
        output("<h3>Story Structure Validation</h3>", true);
        
        $validation = validateStoryStructure($story);
        
        if ($validation['success']) {
            output("<div class='success'>Story has all required fields</div>", true);
        } else {
            output("<div class='error'>Story is missing required fields: " . implode(', ', $validation['missing_fields']) . "</div>", true);
        }
        
        if ($validation['author_check']) {
            output("<div class='success'>Author structure is valid</div>", true);
        } else {
            output("<div class='warning'>Author structure is invalid or missing</div>", true);
        }
        
        if ($validation['tags_check']) {
            output("<div class='success'>Tags structure is valid</div>", true);
        } else {
            output("<div class='warning'>Tags structure is invalid or missing</div>", true);
        }
        
        output("<h3>Story Fields</h3>", true);
        output("<pre>" . htmlspecialchars(json_encode($story, JSON_PRETTY_PRINT)) . "</pre>", true);
    } else {
        output("<div class='error'>Invalid story format</div>", true);
    }
} else {
    output("<div class='error'>Failed to get story for validation</div>", true);
}

if ($isWeb) {
    output('</div>', true);
}

// Start API Operations
if ($isWeb) {
    output('<div id="api-operations" class="tab-content">', true);
    output('<h2>API Operations</h2>', true);
}

output("Testing API Operations");
output("--------------------");

// Test PUT /stories/1
output("<div class='endpoint'>", true);
output("<h3>PUT /stories/1</h3>", true);
output("<p>This test will update a story with new data. <strong>Note:</strong> This is a write operation that will modify data.</p>", true);

if (isset($_GET['run_put_test']) && $_GET['run_put_test'] === 'true') {
    $updateData = [
        'title' => 'Updated Story Title ' . date('Y-m-d H:i:s'),
        'excerpt' => 'This is an updated excerpt from the API test suite.',
        'content' => 'This is updated content for the story from the API test suite.'
    ];
    $updatedStory = makeApiRequest('/api/v1/stories/1', 'PUT', $updateData);
} else {
    output("<p><a href='?run_put_test=true' class='action-button'>Run PUT Test</a></p>", true);
    output("<p>Click the button above to run the PUT test. This will update story ID 1 with new data.</p>", true);
}
output("</div>", true);

if ($isWeb) {
    output('</div>', true);
    
    // Add JavaScript for tabs
    output("<script>
function showTab(tabId) {
    // Hide all tab contents
    var tabContents = document.getElementsByClassName('tab-content');
    for (var i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    // Show the selected tab content
    document.getElementById(tabId).classList.add('active');
    
    // Update tab styles
    var tabs = document.getElementsByClassName('tab');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Find the clicked tab and make it active
    var tabs = document.getElementsByClassName('tab');
    for (var i = 0; i < tabs.length; i++) {
        if (tabs[i].getAttribute('onclick').includes(tabId)) {
            tabs[i].classList.add('active');
        }
    }
}

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
    output('</div></body></html>', true);
}