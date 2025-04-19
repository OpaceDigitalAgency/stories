<?php
/**
 * API Fix Test Script
 * 
 * This script tests the API after fixes to ensure it's working correctly.
 * It makes requests to various endpoints and logs the responses.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api-error.log');

// Start output buffering
ob_start();

// Function to make API requests
function makeRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = "http://{$_SERVER['HTTP_HOST']}/api/v1/$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Accept: application/json'];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($jsonData);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true),
        'error' => $error
    ];
}

// Function to log test results
function logTest($name, $result) {
    echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ddd;'>";
    echo "<h3>$name</h3>";
    
    if ($result['error']) {
        echo "<p style='color: red;'>Error: {$result['error']}</p>";
    } else {
        echo "<p>Status Code: {$result['code']}</p>";
        
        if ($result['code'] >= 200 && $result['code'] < 300) {
            echo "<p style='color: green;'>Success!</p>";
        } else {
            echo "<p style='color: red;'>Failed!</p>";
        }
        
        echo "<pre>" . print_r($result['response'], true) . "</pre>";
    }
    
    echo "</div>";
}

// Start tests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>API Fix Test Results</h1>
    
    <h2>1. Testing Public Endpoints</h2>
    
    <?php
    // Test GET /tags
    $tagsResult = makeRequest('tags');
    logTest('GET /tags', $tagsResult);
    
    // Test GET /tags/1 (assuming tag with ID 1 exists)
    $tagResult = makeRequest('tags/1');
    logTest('GET /tags/1', $tagResult);
    ?>
    
    <h2>2. Testing Protected Endpoints (Without Auth)</h2>
    
    <?php
    // Test POST /tags without auth (should fail)
    $createTagData = [
        'data' => [
            'attributes' => [
                'name' => 'Test Tag',
                'slug' => 'test-tag-' . time()
            ]
        ]
    ];
    $createTagResult = makeRequest('tags', 'POST', $createTagData);
    logTest('POST /tags (No Auth)', $createTagResult);
    ?>
    
    <h2>3. Testing Auth Endpoints</h2>
    
    <?php
    // Test login (if auth endpoints are available)
    $loginData = [
        'email' => 'admin@example.com',
        'password' => 'password123'
    ];
    $loginResult = makeRequest('auth/login', 'POST', $loginData);
    logTest('POST /auth/login', $loginResult);
    
    // Extract token if login successful
    $token = null;
    if ($loginResult['code'] === 200 && isset($loginResult['response']['token'])) {
        $token = $loginResult['response']['token'];
        echo "<p class='success'>Successfully obtained auth token!</p>";
    } else {
        echo "<p class='error'>Failed to obtain auth token. Protected endpoint tests will fail.</p>";
    }
    ?>
    
    <h2>4. Testing Protected Endpoints (With Auth)</h2>
    
    <?php
    if ($token) {
        // Test POST /tags with auth
        $createTagWithAuthResult = makeRequest('tags', 'POST', $createTagData, $token);
        logTest('POST /tags (With Auth)', $createTagWithAuthResult);
        
        // Store created tag ID for update and delete tests
        $createdTagId = null;
        if ($createTagWithAuthResult['code'] === 201 && isset($createTagWithAuthResult['response']['data']['id'])) {
            $createdTagId = $createTagWithAuthResult['response']['data']['id'];
            echo "<p class='success'>Successfully created tag with ID: $createdTagId</p>";
            
            // Test PUT /tags/{id}
            $updateTagData = [
                'data' => [
                    'attributes' => [
                        'name' => 'Updated Test Tag',
                        'slug' => 'updated-test-tag-' . time()
                    ]
                ]
            ];
            $updateTagResult = makeRequest("tags/$createdTagId", 'PUT', $updateTagData, $token);
            logTest("PUT /tags/$createdTagId", $updateTagResult);
            
            // Test DELETE /tags/{id}
            $deleteTagResult = makeRequest("tags/$createdTagId", 'DELETE', null, $token);
            logTest("DELETE /tags/$createdTagId", $deleteTagResult);
        } else {
            echo "<p class='error'>Failed to create tag. Update and delete tests skipped.</p>";
        }
    } else {
        echo "<p class='error'>No auth token available. Protected endpoint tests skipped.</p>";
    }
    ?>
    
    <h2>Summary</h2>
    
    <p>
        These tests verify that the API is working correctly after the fixes.
        If all tests pass, the API is functioning as expected.
    </p>
    
    <p>
        <strong>Note:</strong> Some tests may fail if the database doesn't have the expected data
        or if the auth credentials are incorrect.
    </p>
</body>
</html>
<?php
// End output buffering
ob_end_flush();
?>