<?php
/**
 * API Test Script
 * 
 * Tests all API endpoints and verifies error handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>API Test</h1>
    
    <div class="box">
        <?php
        // Function to test an API endpoint
        function testEndpoint($endpoint, $method = 'GET') {
            $url = "https://api.storiesfromtheweb.org/api/v1/$endpoint";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<h3>Testing: $endpoint</h3>";
            echo "<p>HTTP Status: $httpCode</p>";
            
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                
                if ($httpCode >= 200 && $httpCode < 300) {
                    echo "<p class='success'>✓ Success</p>";
                } else {
                    echo "<p class='error'>✗ Error</p>";
                }
            } else {
                echo "<p class='error'>✗ Invalid JSON response</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
            
            echo "<hr>";
        }
        
        // Test all endpoints
        $endpoints = [
            'stories',
            'stories/1',
            'authors',
            'authors/1',
            'games',
            'games/1',
            'directory-items',
            'directory-items/1',
            'ai-tools',
            'ai-tools/1'
        ];
        
        foreach ($endpoints as $endpoint) {
            testEndpoint($endpoint);
        }
        
        // Test error cases
        echo "<h2>Testing Error Cases</h2>";
        
        // Test invalid endpoint
        testEndpoint('invalid-endpoint');
        
        // Test invalid method
        testEndpoint('stories', 'POST');
        
        // Test invalid ID
        testEndpoint('stories/999999');
        
        // Test invalid parameters
        testEndpoint('stories?sortBy=invalid');
        
        echo "<div class='box'>";
        echo "<h2>Next Steps</h2>";
        echo "<p>Check that all responses:</p>";
        echo "<ol>";
        echo "<li>Return proper HTTP status codes (200 for success, 404 for not found, etc.)</li>";
        echo "<li>Have consistent JSON structure</li>";
        echo "<li>Include proper error messages</li>";
        echo "<li>Handle edge cases gracefully</li>";
        echo "</ol>";
        echo "</div>";
        ?>
    </div>
</body>
</html>