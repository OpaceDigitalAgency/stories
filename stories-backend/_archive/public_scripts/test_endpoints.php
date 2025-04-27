<?php
/**
 * Simple API Endpoint Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Endpoint Test</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>API Endpoint Test</h1>
    
    <?php
    $endpoints = [
        'stories',
        'authors',
        'games',
        'directory-items',
        'ai-tools'
    ];
    
    foreach ($endpoints as $endpoint) {
        echo "<h2>Testing /$endpoint</h2>";
        
        $url = "/api/v1/$endpoint";
        $response = file_get_contents("http://" . $_SERVER['HTTP_HOST'] . $url);
        $data = json_decode($response, true);
        
        if ($data === null) {
            echo "<p class='error'>Error: Invalid JSON response</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        } else {
            echo "<p class='success'>Success: Valid JSON response</p>";
            echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        }
        
        echo "<hr>";
    }
    ?>
    
    <h2>Next Steps</h2>
    <ul>
        <li>Check that each endpoint returns valid JSON</li>
        <li>Verify the response format matches the API specification</li>
        <li>Check for proper error handling</li>
    </ul>
</body>
</html>