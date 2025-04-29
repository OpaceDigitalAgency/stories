<?php
/**
 * API Verification Script
 * 
 * Tests all components after case sensitivity fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Verification</title>
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
        .warning { color: orange; }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .test-result.pass {
            background: #e8f5e9;
        }
        .test-result.fail {
            background: #ffebee;
        }
    </style>
</head>
<body>
    <h1>API Verification</h1>
    
    <div class="box">
        <?php
        // Load config
        $config = require __DIR__ . '/../api/v1/Config/config.php';
        
        function testComponent($name, $test) {
            try {
                $result = $test();
                echo "<div class='test-result pass'>";
                echo "<h3>✓ $name</h3>";
                echo "<p class='success'>Test passed successfully</p>";
                if (is_string($result)) {
                    echo "<pre>$result</pre>";
                }
                echo "</div>";
                return true;
            } catch (Exception $e) {
                echo "<div class='test-result fail'>";
                echo "<h3>✗ $name</h3>";
                echo "<p class='error'>Test failed: " . $e->getMessage() . "</p>";
                echo "</div>";
                return false;
            }
        }
        
        // Test Database Connection
        testComponent('Database Connection', function() use ($config) {
            $db = new StoriesAPI\Core\Database($config['db']);
            $stmt = $db->query("SELECT NOW() as time");
            $result = $stmt->fetch();
            return "Connected successfully. Server time: " . $result['time'];
        });
        
        // Test Auth Token Generation
        testComponent('Auth Token Generation', function() use ($config) {
            $auth = new StoriesAPI\Core\Auth($config);
            $token = $auth->generateToken(1);
            if (!$token) {
                throw new Exception("Failed to generate token");
            }
            $payload = $auth->validateToken($token);
            if (!$payload) {
                throw new Exception("Failed to validate token");
            }
            return "Token generated and validated successfully";
        });
        
        // Test Router
        testComponent('Router', function() use ($config) {
            $router = new StoriesAPI\Core\Router($config);
            $router->get('test', 'TestController', 'index');
            $routes = $router->getRoutes();
            if (empty($routes)) {
                throw new Exception("Failed to add route");
            }
            return "Router initialized successfully";
        });
        
        // Test CORS Headers
        testComponent('CORS Headers', function() use ($config) {
            $cors = new StoriesAPI\Middleware\CorsMiddleware($config);
            $cors->handle();
            $headers = headers_list();
            $found = false;
            foreach ($headers as $header) {
                if (strpos($header, 'Access-Control-Allow-Origin') !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new Exception("CORS headers not set");
            }
            return "CORS headers set successfully";
        });
        
        // Test Directory Structure
        testComponent('Directory Structure', function() {
            $requiredDirs = [
                __DIR__ . '/../api/v1/Core',
                __DIR__ . '/../api/v1/Endpoints',
                __DIR__ . '/../api/v1/Middleware',
                __DIR__ . '/../api/v1/Config'
            ];
            
            $requiredFiles = [
                __DIR__ . '/../api/v1/Core/BaseController.php',
                __DIR__ . '/../api/v1/Core/Auth.php',
                __DIR__ . '/../api/v1/Core/Database.php',
                __DIR__ . '/../api/v1/Core/Router.php',
                __DIR__ . '/../api/v1/Middleware/AuthMiddleware.php',
                __DIR__ . '/../api/v1/Middleware/CorsMiddleware.php',
                __DIR__ . '/../api/v1/Config/config.php'
            ];
            
            foreach ($requiredDirs as $dir) {
                if (!is_dir($dir)) {
                    throw new Exception("Missing directory: $dir");
                }
            }
            
            foreach ($requiredFiles as $file) {
                if (!file_exists($file)) {
                    throw new Exception("Missing file: $file");
                }
            }
            
            return "All required directories and files found";
        });
        
        // Test API Endpoints
        testComponent('API Endpoints', function() {
            $endpoints = [
                '/api/v1/ai-tools',
                '/api/v1/stories',
                '/api/v1/authors'
            ];
            
            $results = [];
            foreach ($endpoints as $endpoint) {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . $endpoint;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    throw new Exception("Endpoint $endpoint returned status code $httpCode");
                }
                
                $results[] = "Endpoint $endpoint: OK (200)";
            }
            
            return implode("\n", $results);
        });
        ?>
        
        <div class="box">
            <h2>Next Steps</h2>
            <p>If all tests passed, the API should be working correctly. You can now:</p>
            <ol>
                <li>Visit the admin interface at <a href="/admin/">/admin/</a> to verify the UI works</li>
                <li>Check the frontend site at <a href="https://storiesfromtheweb.netlify.app">storiesfromtheweb.netlify.app</a></li>
                <li>Monitor the error logs for any remaining issues</li>
            </ol>
        </div>
    </div>
</body>
</html>