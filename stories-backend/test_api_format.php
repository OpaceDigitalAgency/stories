<?php
/**
 * API Format Test Script
 * 
 * This script tests the API response format to ensure it matches what the frontend expects.
 */

// Set headers
header('Content-Type: text/html');

// Function to test an API endpoint
function testEndpoint($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'url' => $url,
        'status' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Test API endpoints
$apiUrl = 'https://api.storiesfromtheweb.org/api/v1';
$endpoints = [
    'stories' => testEndpoint("$apiUrl/stories"),
    'authors' => testEndpoint("$apiUrl/authors"),
    'games' => testEndpoint("$apiUrl/games"),
    'directory-items' => testEndpoint("$apiUrl/directory-items"),
    'ai-tools' => testEndpoint("$apiUrl/ai-tools")
];

// Function to check if response matches expected format
function checkResponseFormat($response) {
    if (empty($response)) {
        return [
            'valid' => false,
            'reason' => 'Empty response'
        ];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'valid' => false,
            'reason' => 'Invalid JSON: ' . json_last_error_msg()
        ];
    }
    
    if (!isset($data['data'])) {
        return [
            'valid' => false,
            'reason' => 'Missing "data" key'
        ];
    }
    
    if (!isset($data['meta'])) {
        return [
            'valid' => false,
            'reason' => 'Missing "meta" key'
        ];
    }
    
    if (!isset($data['meta']['pagination'])) {
        return [
            'valid' => false,
            'reason' => 'Missing "meta.pagination" key'
        ];
    }
    
    if (empty($data['data'])) {
        return [
            'valid' => true,
            'reason' => 'Empty data array, but format is valid'
        ];
    }
    
    $item = $data['data'][0];
    if (!isset($item['id'])) {
        return [
            'valid' => false,
            'reason' => 'Missing "id" in data item'
        ];
    }
    
    if (!isset($item['attributes'])) {
        return [
            'valid' => false,
            'reason' => 'Missing "attributes" in data item'
        ];
    }
    
    return [
        'valid' => true,
        'reason' => 'Valid format'
    ];
}

// Check response formats
$formatChecks = [];
foreach ($endpoints as $name => $result) {
    if ($result['status'] >= 200 && $result['status'] < 300) {
        $formatChecks[$name] = checkResponseFormat($result['response']);
    } else {
        $formatChecks[$name] = [
            'valid' => false,
            'reason' => 'HTTP error: ' . $result['status']
        ];
    }
}

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Format Test</title>
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
            max-height: 300px;
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
        .format-check {
            font-weight: bold;
        }
        .format-check.valid {
            color: #27ae60;
        }
        .format-check.invalid {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Format Test</h1>
        
        <div class="card">
            <h2>API Endpoints</h2>
            <table>
                <tr>
                    <th>Endpoint</th>
                    <th>Status</th>
                    <th>Format</th>
                    <th>Details</th>
                </tr>
                <?php foreach ($endpoints as $name => $result): ?>
                <tr>
                    <td><?php echo $name; ?></td>
                    <td class="status-code <?php echo ($result['status'] >= 200 && $result['status'] < 300) ? 'success' : 'error'; ?>">
                        <?php echo $result['status']; ?>
                    </td>
                    <td class="format-check <?php echo isset($formatChecks[$name]) && $formatChecks[$name]['valid'] ? 'valid' : 'invalid'; ?>">
                        <?php echo isset($formatChecks[$name]) ? ($formatChecks[$name]['valid'] ? 'Valid' : 'Invalid') : 'N/A'; ?>
                    </td>
                    <td>
                        <?php if (isset($formatChecks[$name])): ?>
                            <?php echo $formatChecks[$name]['reason']; ?>
                        <?php endif; ?>
                        
                        <?php if ($result['error']): ?>
                            <span class="error"><?php echo $result['error']; ?></span>
                        <?php else: ?>
                            <details>
                                <summary>View Response</summary>
                                <pre><?php 
                                    $response = json_decode($result['response'], true);
                                    echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                ?></pre>
                            </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h2>Expected Format</h2>
            <pre>{
  "data": [
    {
      "id": 1,
      "attributes": {
        "title": "Example Title",
        "slug": "example-title",
        "content": "Example content...",
        "publishedAt": "2025-04-20T12:00:00.000Z",
        "featured": true,
        "averageRating": 4.5,
        ...
      }
    },
    ...
  ],
  "meta": {
    "pagination": {
      "page": 1,
      "pageSize": 10,
      "pageCount": 5,
      "total": 50
    }
  }
}</pre>
        </div>
        
        <div class="card">
            <h2>Recommendations</h2>
            <ul>
                <?php 
                $hasFormatErrors = false;
                foreach ($formatChecks as $name => $check) {
                    if (!$check['valid']) {
                        $hasFormatErrors = true;
                        break;
                    }
                }
                if ($hasFormatErrors): 
                ?>
                    <li class="error">Fix the API response format to match what the frontend expects.</li>
                <?php else: ?>
                    <li class="success">API response format is valid for all endpoints.</li>
                <?php endif; ?>
                
                <li>Check the frontend console for any API-related errors.</li>
                <li>Verify that the Netlify deployment has the latest changes.</li>
                <li>Check that the environment variables are set correctly in Netlify.</li>
            </ul>
        </div>
    </div>
</body>
</html>