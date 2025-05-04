<?php
/**
 * Admin Diagnostic Tool
 * 
 * This script provides comprehensive testing for the admin interface, including:
 * - Authentication testing
 * - Form submission testing
 * - API integration testing
 * - Database connectivity testing
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Include required files
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/Database.php';

// Try to include Auth class if it exists
if (file_exists(__DIR__ . '/admin/includes/Auth.php')) {
    require_once __DIR__ . '/admin/includes/Auth.php';
    $authClassExists = true;
    // Initialize Auth
    Auth::init($config['security']);
} else {
    $authClassExists = false;
}

// Try to include SimpleAuth if it exists
if (file_exists(__DIR__ . '/simple_auth.php')) {
    require_once __DIR__ . '/simple_auth.php';
    $simpleAuthExists = true;
} else {
    $simpleAuthExists = false;
}

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Function to output diagnostic information
function outputDiagnostic($title, $data, $success = true) {
    global $isWeb;
    if ($isWeb) {
        echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid " . ($success ? "#4CAF50" : "#F44336") . "; border-radius: 5px;'>";
        echo "<h3 style='margin-top: 0; color: " . ($success ? "#4CAF50" : "#F44336") . ";'>$title</h3>";
        
        if (is_array($data) || is_object($data)) {
            echo "<pre>" . print_r($data, true) . "</pre>";
        } else {
            echo "<p>$data</p>";
        }
        
        echo "</div>";
    } else {
        echo "\n=== " . ($success ? "[SUCCESS]" : "[ERROR]") . " $title ===\n";
        
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            echo "$data\n";
        }
        
        echo "\n";
    }
}

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    global $isWeb;
    
    $baseUrl = "https://api.storiesfromtheweb.org/api/v1";
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
    
    // Set headers
    $headers = [];
    
    // Add authentication token if provided
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    // Set data if provided
    if ($data) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Content-Length: ' . strlen($jsonData);
        
        output("Request data:");
        output(json_encode($data, JSON_PRETTY_PRINT));
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

// Function to decode JWT token
function decodeJwtToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    try {
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        return [
            'header' => $header,
            'payload' => $payload,
            'signature' => $parts[2]
        ];
    } catch (Exception $e) {
        return null;
    }
}

// Function to test database connection
function testDatabaseConnection($config) {
    try {
        $db = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
            $config['db']['user'],
            $config['db']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Test query
        $stmt = $db->query("SELECT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'message' => 'Database connection successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

// Function to test database write operation
function testDatabaseWrite($config) {
    try {
        $db = new PDO(
            "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
            $config['db']['user'],
            $config['db']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create test table if it doesn't exist
        $testTable = "admin_diagnostic_test";
        $db->exec("CREATE TABLE IF NOT EXISTS $testTable (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_data VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert a test record
        $stmt = $db->prepare("INSERT INTO $testTable (test_data) VALUES (?)");
        $testData = "Test data " . date('Y-m-d H:i:s');
        $stmt->execute([$testData]);
        
        $lastId = $db->lastInsertId();
        
        if ($lastId) {
            // Clean up test data
            $db->exec("DELETE FROM $testTable WHERE id = $lastId");
            
            return [
                'success' => true,
                'message' => "Database write operation successful. Inserted test record with ID: $lastId"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Database write operation failed. No ID returned.'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database write operation failed: ' . $e->getMessage()
        ];
    }
}

// Function to check form submission fix
function checkFormSubmissionFix() {
    $results = [];
    
    // Check if the form submission fix script exists
    $formFixJsPath = __DIR__ . '/admin/assets/js/form-submission-fix.js';
    $results['script_exists'] = file_exists($formFixJsPath);
    
    // Check if the form submission fix script is included in the footer
    $footerFile = __DIR__ . '/admin/views/footer.php';
    if (file_exists($footerFile)) {
        $footerContent = file_get_contents($footerFile);
        $results['included_in_footer'] = strpos($footerContent, 'form-submission-fix.js') !== false;
    } else {
        $results['included_in_footer'] = false;
        $results['footer_file_exists'] = false;
    }
    
    // Check if the form submission fix include script exists
    $includeScriptPath = __DIR__ . '/admin/form_submission_fix_include.php';
    $results['include_script_exists'] = file_exists($includeScriptPath);
    
    // Check if the .htaccess file includes the auto_prepend_file directive
    $htaccessPath = __DIR__ . '/admin/.htaccess';
    if (file_exists($htaccessPath)) {
        $htaccessContent = file_get_contents($htaccessPath);
        $results['auto_prepend_directive'] = strpos($htaccessContent, 'auto_prepend_file') !== false && 
                                            strpos($htaccessContent, 'form_submission_fix_include.php') !== false;
    } else {
        $results['auto_prepend_directive'] = false;
        $results['htaccess_file_exists'] = false;
    }
    
    return $results;
}

// HTML header for web mode
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Diagnostic Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
        .action-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .action-button.secondary {
            background-color: #2196F3;
        }
        .action-button.danger {
            background-color: #F44336;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: #f0f0f0;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background: #2196F3;
            color: white;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Admin Diagnostic Tool</h1>
        
        <div class='tabs'>
            <div class='tab active' onclick='showTab(\"auth-test\")'>Authentication Test</div>
            <div class='tab' onclick='showTab(\"form-test\")'>Form Submission Test</div>
            <div class='tab' onclick='showTab(\"api-test\")'>API Integration Test</div>
            <div class='tab' onclick='showTab(\"db-test\")'>Database Test</div>
        </div>";
}

// Check if we're performing an action
$action = $_GET['action'] ?? '';

// Handle actions
switch ($action) {
    case 'login':
        // Handle login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
            
            // Authenticate user
            $user = null;
            
            if ($authClassExists) {
                $user = Auth::authenticate($email, $password);
                if ($user) {
                    Auth::login($email, $password, $remember);
                }
            } else if ($simpleAuthExists) {
                $user = SimpleAuth::login($email, $password);
            }
            
            if ($user) {
                outputDiagnostic("Login Successful", [
                    'user' => $user,
                    'session_token' => isset($_SESSION['token']) ? 'Present' : 'Missing',
                    'cookie_token' => isset($_COOKIE['auth_token']) ? 'Present' : 'Missing'
                ]);
                
                // Redirect to diagnostic page
                echo "<p>Redirecting to diagnostic page...</p>";
                echo "<script>setTimeout(function() { window.location.href = 'admin_diagnostic.php'; }, 2000);</script>";
            } else {
                outputDiagnostic("Login Failed", "Invalid email or password", false);
                
                // Show login form again
                echo "<p>Please try again:</p>";
                showLoginForm();
            }
        } else {
            // Show login form
            showLoginForm();
        }
        break;
        
    case 'logout':
        // Handle logout
        if ($authClassExists) {
            Auth::logout();
        } else if ($simpleAuthExists) {
            SimpleAuth::logout();
        }
        
        outputDiagnostic("Logout Successful", [
            'session_token' => isset($_SESSION['token']) ? 'Present' : 'Missing',
            'cookie_token' => isset($_COOKIE['auth_token']) ? 'Present' : 'Missing'
        ]);
        
        // Redirect to diagnostic page
        echo "<p>Redirecting to diagnostic page...</p>";
        echo "<script>setTimeout(function() { window.location.href = 'admin_diagnostic.php'; }, 2000);</script>";
        break;
        
    case 'test_api':
        // Test API connection
        $apiUrl = "/api/v1";
        $token = $_SESSION['token'] ?? null;
        
        // Test unauthenticated endpoint
        $publicEndpoint = $apiUrl . '/stories?pageSize=1';
        $publicResult = makeApiRequest($publicEndpoint);
        
        outputDiagnostic(
            "Public API Endpoint Test (/stories)",
            [
                'url' => $publicEndpoint,
                'status_code' => $publicResult['code'],
                'success' => $publicResult['success'],
                'response' => $publicResult['response']
            ],
            $publicResult['success']
        );
        
        // Test authenticated endpoint
        if ($token) {
            $privateEndpoint = $apiUrl . '/auth/me';
            $privateResult = makeApiRequest($privateEndpoint, 'GET', null, $token);
            
            outputDiagnostic(
                "Private API Endpoint Test (/auth/me)",
                [
                    'url' => $privateEndpoint,
                    'token' => $token,
                    'status_code' => $privateResult['code'],
                    'success' => $privateResult['success'],
                    'response' => $privateResult['response']
                ],
                $privateResult['success']
            );
        } else {
            outputDiagnostic("Private API Endpoint Test", "No authentication token available", false);
        }
        
        // Back to diagnostic page
        echo "<p><a href='admin_diagnostic.php' class='action-button'>Back to Diagnostic</a></p>";
        break;
        
    case 'test_form':
        // Test form submission
        $formFixResults = checkFormSubmissionFix();
        
        outputDiagnostic(
            "Form Submission Fix Check",
            $formFixResults,
            $formFixResults['script_exists'] ?? false
        );
        
        // Test API endpoints for form submission
        output("<h3>Testing API Endpoints for Form Submission</h3>");
        
        // Test GET /stories
        $storiesResult = makeApiRequest('/stories');
        
        // Test PUT /stories/1
        if (isset($_GET['run_put_test']) && $_GET['run_put_test'] === 'true') {
            $updateData = [
                'title' => 'Updated Story Title ' . date('Y-m-d H:i:s'),
                'excerpt' => 'This is an updated excerpt from the admin diagnostic tool.',
                'content' => 'This is updated content for the story from the admin diagnostic tool.'
            ];
            $updatedStory = makeApiRequest('/stories/1', 'PUT', $updateData);
            
            outputDiagnostic(
                "PUT /stories/1 Test",
                [
                    'request' => $updateData,
                    'response' => $updatedStory
                ],
                $updatedStory['success']
            );
        } else {
            echo "<p><a href='admin_diagnostic.php?action=test_form&run_put_test=true' class='action-button'>Run PUT Test</a></p>";
            echo "<p>Click the button above to run the PUT test. This will update story ID 1 with new data.</p>";
        }
        
        // Back to diagnostic page
        echo "<p><a href='admin_diagnostic.php' class='action-button'>Back to Diagnostic</a></p>";
        break;
        
    case 'test_db':
        // Test database connection
        $dbConnectionResult = testDatabaseConnection($config);
        
        outputDiagnostic(
            "Database Connection Test",
            $dbConnectionResult['message'],
            $dbConnectionResult['success']
        );
        
        // Test database write operation
        $dbWriteResult = testDatabaseWrite($config);
        
        outputDiagnostic(
            "Database Write Operation Test",
            $dbWriteResult['message'],
            $dbWriteResult['success']
        );
        
        // Back to diagnostic page
        echo "<p><a href='admin_diagnostic.php' class='action-button'>Back to Diagnostic</a></p>";
        break;
        
    default:
        // Show diagnostic information
        showDiagnosticInfo();
        break;
}

// Function to show login form
function showLoginForm() {
    echo "
    <h2>Login</h2>
    <form method='post' action='admin_diagnostic.php?action=login'>
        <div style='margin-bottom: 15px;'>
            <label for='email' style='display: block; margin-bottom: 5px;'>Email:</label>
            <input type='email' id='email' name='email' required style='padding: 8px; width: 300px;'>
        </div>
        <div style='margin-bottom: 15px;'>
            <label for='password' style='display: block; margin-bottom: 5px;'>Password:</label>
            <input type='password' id='password' name='password' required style='padding: 8px; width: 300px;'>
        </div>
        <div style='margin-bottom: 15px;'>
            <label>
                <input type='checkbox' name='remember' value='1'>
                Remember me
            </label>
        </div>
        <div>
            <button type='submit' style='padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>Login</button>
            <a href='admin_diagnostic.php' style='margin-left: 10px; text-decoration: none;'>Cancel</a>
        </div>
    </form>";
}

// Function to show diagnostic information
function showDiagnosticInfo() {
    global $config, $authClassExists, $simpleAuthExists, $isWeb;
    
    // Authentication tab content
    if ($isWeb) {
        echo "<div id='auth-test' class='tab-content active'>";
    }
    
    echo "<h2>Authentication Status</h2>";
    
    // Check if user is authenticated
    $user = null;
    $authenticated = false;
    
    if ($authClassExists) {
        $user = Auth::checkAuth();
        $authenticated = $user !== false;
    } else if ($simpleAuthExists) {
        $user = SimpleAuth::check();
        $authenticated = $user !== false;
    }
    
    // Get session and cookie information
    $sessionToken = isset($_SESSION['token']) ? $_SESSION['token'] : null;
    $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
    
    // Decode tokens if available
    $sessionTokenData = $sessionToken ? decodeJwtToken($sessionToken) : null;
    $cookieTokenData = $cookieToken ? decodeJwtToken($cookieToken) : null;
    
    // Check token consistency
    $tokensConsistent = ($sessionToken && $cookieToken) ? ($sessionToken === $cookieToken) : true;
    
    // Check token expiration
    $sessionTokenExpired = false;
    $cookieTokenExpired = false;
    
    if ($sessionTokenData && isset($sessionTokenData['payload']['exp'])) {
        $sessionTokenExpired = $sessionTokenData['payload']['exp'] < time();
    }
    
    if ($cookieTokenData && isset($cookieTokenData['payload']['exp'])) {
        $cookieTokenExpired = $cookieTokenData['payload']['exp'] < time();
    }
    
    // Output authentication status
    echo "<p><strong>Authenticated:</strong> " . ($authenticated ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    if ($authenticated) {
        echo "<p><strong>User:</strong></p>";
        echo "<pre>" . print_r($user, true) . "</pre>";
    }
    
    // Output token information
    echo "<h3>Token Information</h3>";
    echo "<p><strong>Session Token:</strong> " . ($sessionToken ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Cookie Token:</strong> " . ($cookieToken ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Tokens Consistent:</strong> " . ($tokensConsistent ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    if ($sessionToken) {
        echo "<p><strong>Session Token Expired:</strong> " . ($sessionTokenExpired ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        
        if ($sessionTokenData) {
            echo "<p><strong>Session Token Data:</strong></p>";
            echo "<pre>" . print_r($sessionTokenData, true) . "</pre>";
        }
    }
    
    if ($cookieToken) {
        echo "<p><strong>Cookie Token Expired:</strong> " . ($cookieTokenExpired ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        
        if ($cookieTokenData) {
            echo "<p><strong>Cookie Token Data:</strong></p>";
            echo "<pre>" . print_r($cookieTokenData, true) . "</pre>";
        }
    }
    
    // Output authentication system information
    echo "<h3>Authentication System</h3>";
    echo "<p><strong>Auth Class:</strong> " . ($authClassExists ? "<span class='success'>Available</span>" : "<span class='error'>Not Available</span>") . "</p>";
    echo "<p><strong>SimpleAuth:</strong> " . ($simpleAuthExists ? "<span class='success'>Available</span>" : "<span class='error'>Not Available</span>") . "</p>";
    
    // Output actions
    echo "<h3>Actions</h3>";
    
    if ($authenticated) {
        echo "<a href='admin_diagnostic.php?action=logout' class='action-button danger'>Logout</a>";
    } else {
        echo "<a href='admin_diagnostic.php?action=login' class='action-button'>Login</a>";
    }
    
    if ($isWeb) {
        echo "</div>";
    }
    
    // Form submission tab content
    if ($isWeb) {
        echo "<div id='form-test' class='tab-content'>";
    }
    
    echo "<h2>Form Submission Test</h2>";
    
    // Check form submission fix
    $formFixResults = checkFormSubmissionFix();
    
    echo "<h3>Form Submission Fix Status</h3>";
    echo "<p><strong>Form Fix Script:</strong> " . ($formFixResults['script_exists'] ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Included in Footer:</strong> " . (($formFixResults['included_in_footer'] ?? false) ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    echo "<p><strong>Include Script:</strong> " . ($formFixResults['include_script_exists'] ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Auto Prepend Directive:</strong> " . (($formFixResults['auto_prepend_directive'] ?? false) ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    
    echo "<h3>Actions</h3>";
    echo "<a href='admin_diagnostic.php?action=test_form' class='action-button'>Run Form Tests</a>";
    
    if ($isWeb) {
        echo "</div>";
    }
    
    // API integration tab content
    if ($isWeb) {
        echo "<div id='api-test' class='tab-content'>";
    }
    
    echo "<h2>API Integration Test</h2>";
    
    echo "<p>This test will check the integration between the admin interface and the API.</p>";
    
    echo "<h3>Actions</h3>";
    echo "<a href='admin_diagnostic.php?action=test_api' class='action-button'>Test API Integration</a>";
    
    if ($isWeb) {
        echo "</div>";
    }
    
    // Database test tab content
    if ($isWeb) {
        echo "<div id='db-test' class='tab-content'>";
    }
    
    echo "<h2>Database Test</h2>";
    
    echo "<p>This test will check the database connection and perform a write operation test.</p>";
    
    echo "<h3>Database Configuration</h3>";
    echo "<p><strong>Host:</strong> " . $config['db']['host'] . "</p>";
    echo "<p><strong>Database:</strong> " . $config['db']['name'] . "</p>";
    echo "<p><strong>User:</strong> " . $config['db']['user'] . "</p>";
    
    echo "<h3>Actions</h3>";
    echo "<a href='admin_diagnostic.php?action=test_db' class='action-button'>Test Database</a>";
    
    if ($isWeb) {
        echo "</div>";
    }
}

// HTML footer for web mode
if ($isWeb) {
    echo "
    <script>
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
    </script>
    </div>
</body>
</html>";
}