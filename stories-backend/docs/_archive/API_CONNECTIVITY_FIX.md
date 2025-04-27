# API Connectivity Fix

This document outlines the steps to fix the API connectivity issues between the frontend (Netlify) and backend (PHP API).

## Problem

The frontend deployed at https://storiesfromtheweb.netlify.app/ is not displaying any content from the backend API. The issues include:

1. API URL configuration
2. CORS (Cross-Origin Resource Sharing) issues
3. Error handling
4. Authentication token management

## Solution

### 1. Environment Variable Configuration

#### Update API URL in src/lib/api.ts

```typescript
// Change from hardcoded URL
// const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

// To environment variable based URL
const API_URL = import.meta.env.PUBLIC_API_URL || 'https://api.storiesfromtheweb.org/api/v1';
```

#### Add Environment Variable to netlify.toml

```toml
[build.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"

[context.production.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"

[context.deploy-preview.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"
```

#### Update TypeScript Definitions in src/env.d.ts

```typescript
/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly PUBLIC_API_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
```

### 2. CORS Configuration

#### Add CORS Headers to .htaccess

```apache
# Add CORS headers
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "https://storiesfromtheweb.netlify.app"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
    Header set Access-Control-Allow-Credentials "true"
    
    # Handle preflight OPTIONS requests
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
```

### 3. API Status Endpoint

#### Create api-status.php

```php
<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://storiesfromtheweb.netlify.app');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Check database connection
$dbStatus = 'unknown';
$dbMessage = '';

try {
    // Database configuration
    $config = [
        'host' => 'localhost',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
    
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Test query
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    
    if ($result) {
        $dbStatus = 'connected';
        $dbMessage = 'Database connection successful';
    } else {
        $dbStatus = 'error';
        $dbMessage = 'Database query failed';
    }
} catch (PDOException $e) {
    $dbStatus = 'error';
    $dbMessage = 'Database connection failed: ' . $e->getMessage();
}

// Check API endpoints
$endpoints = [
    'stories' => false,
    'authors' => false,
    'tags' => false,
    'games' => false,
    'directory-items' => false,
    'ai-tools' => false
];

foreach ($endpoints as $endpoint => $status) {
    try {
        // Check if the endpoint file exists
        $file = __DIR__ . "/api/v1/Endpoints/" . ucfirst(str_replace('-', '', $endpoint)) . "Controller.php";
        $endpoints[$endpoint] = file_exists($file);
    } catch (Exception $e) {
        $endpoints[$endpoint] = false;
    }
}

// Return status
echo json_encode([
    'status' => 'online',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => [
        'status' => $dbStatus,
        'message' => $dbMessage
    ],
    'endpoints' => $endpoints,
    'version' => '1.0.0'
]);
```

### 4. Enhanced Error Handling

#### Update fetchFromApi in src/lib/api.ts

```typescript
export const fetchFromApi = async (endpoint: string, params: ApiParams = {}, retryCount = 0): Promise<any> => {
  try {
    // Build query parameters
    const queryParams = new URLSearchParams();
    
    if (params.page) {
      queryParams.append('page', params.page.toString());
    }
    
    if (params.pageSize) {
      queryParams.append('pageSize', params.pageSize.toString());
    }
    
    if (params.sort) {
      queryParams.append('sort', params.sort);
    }
    
    // Add any filters
    if (params.filters) {
      Object.entries(params.filters).forEach(([key, value]) => {
        queryParams.append(key, value.toString());
      });
    }
    
    // Make the API call
    const queryString = queryParams.toString();
    const url = `${API_URL}/${endpoint}${queryString ? `?${queryString}` : ''}`;
    
    console.log(`Fetching from API: ${url}`);
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json'
    };
    
    // Get auth token from cookie if available
    const authToken = getAuthToken();
    
    if (authToken) {
      headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const res = await fetch(url, { headers });
    
    // Check for new token in response headers
    const newToken = res.headers.get('X-New-Token');
    if (newToken) {
      console.log('Received new token in response headers');
      setAuthToken(newToken);
    }
    
    if (!res.ok) {
      const errorMessage = `Error fetching from API: ${res.status} ${res.statusText}`;
      console.error(errorMessage);
      console.error(`URL: ${url}`);
      console.error(`Headers: ${JSON.stringify(headers)}`);
      
      // If we get a 401 Unauthorized error, try to refresh the token and retry
      if (res.status === 401 && retryCount < 1) {
        console.log('Received 401 Unauthorized, attempting to refresh token and retry');
        
        const refreshed = await refreshAuthToken();
        if (refreshed) {
          console.log('Token refreshed, retrying original request');
          return fetchFromApi(endpoint, params, retryCount + 1);
        } else {
          console.error('Token refresh failed, cannot retry request');
        }
      }
      
      try {
        const errorResponse = await res.text();
        console.error(`Error response: ${errorResponse}`);
      } catch (textError) {
        console.error('Could not read error response body');
      }
      
      // Check API status if we get an error
      try {
        console.log('Checking API status...');
        const statusRes = await fetch(`${API_URL.replace('/api/v1', '')}/api-status.php`);
        if (statusRes.ok) {
          const statusData = await statusRes.json();
          console.log('API Status:', statusData);
        } else {
          console.error('API status check failed:', statusRes.status, statusRes.statusText);
        }
      } catch (statusError) {
        console.error('Error checking API status:', statusError);
      }
      
      throw new Error(`Failed to fetch from API: ${res.status} ${res.statusText}`);
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from API:', error);
    console.error(`Endpoint: ${endpoint}`);
    console.error(`Params: ${JSON.stringify(params)}`);
    
    // Create a detailed error object
    const apiError = {
      message: error instanceof Error ? error.message : 'Unknown error',
      endpoint,
      params,
      timestamp: new Date().toISOString(),
      apiUrl: API_URL
    };
    
    // Log the detailed error
    console.error('API Error Details:', apiError);
    
    // On error, return empty data with error information
    if (endpoint.includes('/') && endpoint.split('/').length > 1) {
      return { data: null, error: apiError };
    } else {
      return { 
        data: [], 
        meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } },
        error: apiError
      };
    }
  }
};
```

### 5. Create ApiErrorMessage Component

#### Create src/components/ApiErrorMessage.astro

```astro
---
interface Props {
  error: any;
  message?: string;
}

const { error, message = "We're having trouble loading content right now." } = Astro.props;
---

<div class="api-error">
  <h3>Oops! Something went wrong</h3>
  <p>{message}</p>
  
  {error && import.meta.env.DEV && (
    <details>
      <summary>Technical Details (Development Only)</summary>
      <pre>{JSON.stringify(error, null, 2)}</pre>
    </details>
  )}
  
  <button class="retry-button" onclick="window.location.reload()">
    Try Again
  </button>
</div>

<style>
  .api-error {
    background-color: #fff8f8;
    border: 1px solid #ffcdd2;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
  }
  
  h3 {
    color: #d32f2f;
    margin-top: 0;
  }
  
  details {
    margin: 15px 0;
    text-align: left;
  }
  
  summary {
    cursor: pointer;
    color: #666;
    font-size: 0.9em;
  }
  
  pre {
    background-color: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 0.8em;
  }
  
  .retry-button {
    background-color: #2196f3;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
  }
  
  .retry-button:hover {
    background-color: #0d8bf2;
  }
</style>
```

### 6. Diagnostic Tool

#### Create test_api_connectivity.php

```php
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
```

## Deployment Steps

1. **Backend Deployment**:
   - Upload the `api-status.php` file to the root directory of the API server
   - Update the `.htaccess` file with the CORS headers
   - Upload the `test_api_connectivity.php` script to the root directory

2. **Frontend Deployment**:
   - Update the `src/lib/api.ts` file with the enhanced error handling
   - Create the `src/components/ApiErrorMessage.astro` component
   - Update the `netlify.toml` file with the environment variables
   - Update the `src/env.d.ts` file with the TypeScript definitions

3. **Testing**:
   - Run the `test_api_connectivity.php` script to verify the API connectivity
   - Check the frontend console for any API-related errors
   - Verify that content is properly displayed on the frontend

## References

- [DEPLOYMENT.md](./DEPLOYMENT.md) - General deployment instructions
- [FTP_DEPLOYMENT.md](./FTP_DEPLOYMENT.md) - FTP deployment details
- [GIT_DEPLOYMENT.md](./GIT_DEPLOYMENT.md) - Git deployment workflow
- [system-documentation.html](./system-documentation.html) - System architecture documentation