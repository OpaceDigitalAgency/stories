<?php
/**
 * API Connectivity Test Script
 * 
 * This script tests the connectivity between the frontend and backend API.
 * It checks CORS headers, API endpoints, and database connection.
 */

// Set headers
header('Content-Type: text/html');

// Function to test an API endpoint
function testEndpoint($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'url' => $url,
        'status' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'error' => $error
    ];
}

// Test API status endpoint
$apiUrl = 'https://api.storiesfromtheweb.org';
$statusResult = testEndpoint("$apiUrl/api-status.php");

// Test API endpoints
$endpoints = [
    'stories' => testEndpoint("$apiUrl/api/v1/stories"),
    'authors' => testEndpoint("$apiUrl/api/v1/authors"),
    'games' => testEndpoint("$apiUrl/api/v1/games"),
    'directory-items' => testEndpoint("$apiUrl/api/v1/directory-items"),
    'ai-tools' => testEndpoint("$apiUrl/api/v1/ai-tools")
];

// Check CORS headers
function checkCorsHeaders($headers) {
    $corsHeaders = [
        'Access-Control-Allow-Origin' => false,
        'Access-Control-Allow-Methods' => false,
        'Access-Control-Allow-Headers' => false,
        'Access-Control-Allow-Credentials' => false
    ];
    
    $headerLines = explode("\n", $headers);
    foreach ($headerLines as $line) {
        foreach ($corsHeaders as $header => $value) {
            if (stripos($line, $header) !== false) {
                $corsHeaders[$header] = true;
            }
        }
    }
    
    return $corsHeaders;
}

$corsStatus = checkCorsHeaders($statusResult['headers']);

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Connectivity Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .warning {
            color: #f39c12;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .status-code {
            font-weight: bold;
        }
        .status-code.success {
            color: #27ae60;
        }
        .status-code.error {
            color: #e74c3c;
        }
        .status-code.warning {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Connectivity Test</h1>
        
        <div class="card">
            <h2>API Status</h2>
            <p>URL: <?php echo $statusResult['url']; ?></p>
            <p>Status: 
                <span class="status-code <?php echo ($statusResult['status'] >= 200 && $statusResult['status'] < 300) ? 'success' : 'error'; ?>">
                    <?php echo $statusResult['status']; ?>
                </span>
            </p>
            <?php if ($statusResult['error']): ?>
                <p class="error">Error: <?php echo $statusResult['error']; ?></p>
            <?php endif; ?>
            
            <h3>Response Body</h3>
            <pre><?php echo htmlspecialchars($statusResult['body']); ?></pre>
        </div>
        
        <div class="card">
            <h2>CORS Headers</h2>
            <table>
                <tr>
                    <th>Header</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($corsStatus as $header => $status): ?>
                <tr>
                    <td><?php echo $header; ?></td>
                    <td class="<?php echo $status ? 'success' : 'error'; ?>">
                        <?php echo $status ? 'Present' : 'Missing'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>API Endpoints</h2>
            <table>
                <tr>
                    <th>Endpoint</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
                <?php foreach ($endpoints as $name => $result): ?>
                <tr>
                    <td><?php echo $name; ?></td>
                    <td class="status-code <?php echo ($result['status'] >= 200 && $result['status'] < 300) ? 'success' : 'error'; ?>">
                        <?php echo $result['status']; ?>
                    </td>
                    <td>
                        <?php if ($result['error']): ?>
                            <span class="error"><?php echo $result['error']; ?></span>
                        <?php else: ?>
                            <details>
                                <summary>View Response</summary>
                                <pre><?php echo htmlspecialchars(substr($result['body'], 0, 500) . (strlen($result['body']) > 500 ? '...' : '')); ?></pre>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>Recommendations</h2>
            <ul>
                <?php if (!$corsStatus['Access-Control-Allow-Origin']): ?>
                    <li class="error">Add <code>Access-Control-Allow-Origin</code> header to allow requests from the frontend.</li>
                <?php endif; ?>
                
                <?php if (!$corsStatus['Access-Control-Allow-Methods']): ?>
                    <li class="error">Add <code>Access-Control-Allow-Methods</code> header to specify allowed HTTP methods.</li>
                <?php endif; ?>
                
                <?php if (!$corsStatus['Access-Control-Allow-Headers']): ?>
                    <li class="error">Add <code>Access-Control-Allow-Headers</code> header to allow necessary request headers.</li>
                <?php endif; ?>
                
                <?php if (!$corsStatus['Access-Control-Allow-Credentials']): ?>
                    <li class="error">Add <code>Access-Control-Allow-Credentials</code> header to allow credentials in cross-origin requests.</li>
                <?php endif; ?>
                
                <?php if ($statusResult['status'] != 200): ?>
                    <li class="error">Fix the API status endpoint to return a 200 status code.</li>
                <?php endif; ?>
                
                <?php 
                $hasEndpointErrors = false;
                foreach ($endpoints as $result) {
                    if ($result['status'] < 200 || $result['status'] >= 300) {
                        $hasEndpointErrors = true;
                        break;
                    }
                }
                if ($hasEndpointErrors): 
                ?>
                    <li class="error">Fix the API endpoints that are returning error status codes.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>