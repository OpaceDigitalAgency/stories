<?php
/**
 * Admin API Diagnostic Script
 * 
 * Tests CRUD operations and logs detailed request/response data.
 * This script is protected and only accessible to logged-in admin users.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/stories/api.storiesfromtheweb.org/logs/api-error.log');

// Start output buffering
ob_start();

// Required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/ApiClient.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Auth and check authentication
Auth::init($config['security']);
$user = Auth::checkAuth();

if (!$user) {
    header('Location: ' . ADMIN_URL . '/login.php');
    exit('Authentication required');
}

// Ensure we have a valid token
if (empty($_SESSION['token'])) {
    // Generate new token if needed
    $token = Auth::refreshToken($user, true);
    if (!$token) {
        error_log("Failed to refresh token for user {$user['id']}");
        exit('Failed to refresh authentication token');
    }
} else {
    $token = $_SESSION['token'];
}

// Create log function
function logTest($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    echo "<pre style='margin: 10px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd;'>";
    echo "<strong>[$timestamp] $message</strong>\n";
    if ($data !== null) {
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    echo "</pre>";
}

// Initialize API client with the validated token
error_log("Using token: " . ($token ? "Present" : "Missing"));
$apiClient = new ApiClient(API_URL, $token);

// Log token details for debugging
error_log("Token details:");
error_log("- Session token: " . (!empty($_SESSION['token']) ? "Present" : "Missing"));
error_log("- Cookie token: " . (!empty($_COOKIE['auth_token']) ? "Present" : "Missing"));
error_log("- API Client token: " . ($token ? "Present" : "Missing"));

// Test endpoint (using tags as it's a simple resource)
$testEndpoint = 'tags';

// Start tests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin API Diagnostic Test</title>
    <link href="<?php echo ADMIN_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .test-section { margin-bottom: 30px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin API Diagnostic Test</h1>
        <div class="test-section">
            <h2>1. Authentication Status</h2>
            <?php
            // Get token status after potential cookie restore
            $token = $_SESSION['token'] ?? $_COOKIE['auth_token'] ?? null;
            logTest("Checking authentication", [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'token_exists' => !empty($token)
            ]);
            ?>
        </div>

        <div class="test-section">
            <h2>2. GET Request Test</h2>
            <?php
            $getResponse = $apiClient->get($testEndpoint);
            logTest("GET /$testEndpoint Response", $getResponse);
            // Debug output: HTTP status code and raw response body
            echo "<pre style='margin: 10px; padding: 10px; background: #eef; border: 1px solid #99f;'>";
            echo "<strong>GET /$testEndpoint Debug Info</strong>\n";
            echo "HTTP Status Code: " . htmlspecialchars($apiClient->getLastStatusCode()) . "\n";
            echo "Raw Response Body:\n" . htmlspecialchars($apiClient->getLastRawResponse());
            echo "</pre>";
            ?>
        </div>

        <div class="test-section">
            <h2>3. POST Request Test</h2>
            <?php
            $testData = [
                'data' => [
                    'attributes' => [
                        'name' => 'Test Tag ' . time(),
                        'slug' => 'test-tag-' . time()
                    ]
                ]
            ];
            $postResponse = $apiClient->post($testEndpoint, $testData);
            logTest("POST /$testEndpoint Request Data", $testData);
            logTest("POST /$testEndpoint Response", $postResponse);
            // Debug output: HTTP status code and raw response body
            echo "<pre style='margin: 10px; padding: 10px; background: #eef; border: 1px solid #99f;'>";
            echo "<strong>POST /$testEndpoint Debug Info</strong>\n";
            echo "HTTP Status Code: " . htmlspecialchars($apiClient->getLastStatusCode()) . "\n";
            echo "Raw Response Body:\n" . htmlspecialchars($apiClient->getLastRawResponse());
            echo "</pre>";

            // Store created ID for update and delete tests
            $createdId = $postResponse['data']['id'] ?? null;
            ?>
        </div>

        <?php if ($createdId): ?>
            <div class="test-section">
                <h2>4. PUT Request Test</h2>
                <?php
                $updateData = [
                    'data' => [
                        'attributes' => [
                            'name' => 'Updated Test Tag ' . time(),
                            'slug' => 'updated-test-tag-' . time()
                        ]
                    ]
                ];
                $putResponse = $apiClient->put("$testEndpoint/$createdId", $updateData);
                logTest("PUT /$testEndpoint/$createdId Request Data", $updateData);
                logTest("PUT /$testEndpoint/$createdId Response", $putResponse);
                ?>
            </div>

            <div class="test-section">
                <h2>5. DELETE Request Test</h2>
                <?php
                $deleteResponse = $apiClient->delete("$testEndpoint/$createdId");
                logTest("DELETE /$testEndpoint/$createdId Response", $deleteResponse);
                ?>
            </div>
        <?php endif; ?>

        <div class="test-section">
            <h2>Summary</h2>
            <?php
            $summary = [
                'authentication' => !empty($user),
                'get_request' => !empty($getResponse),
                'post_request' => !empty($postResponse),
                'put_request' => !empty($putResponse),
                'delete_request' => !empty($deleteResponse)
            ];

            foreach ($summary as $test => $result) {
                echo "<div class='" . ($result ? 'success' : 'error') . "'>";
                echo ucwords(str_replace('_', ' ', $test)) . ": " . ($result ? 'Success' : 'Failed');
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <script src="<?php echo ADMIN_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>