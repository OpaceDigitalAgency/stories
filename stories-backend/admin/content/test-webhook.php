<?php
/**
 * Webhook Test Script
 * 
 * This script tests various methods of connecting to the Git Auto Deploy webhook
 * to diagnose why the status check is failing.
 */

// Set page title and current page
$pageTitle = 'Webhook Test';
$currentPage = 'debug';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Function to test connection using cURL
function test_curl_connection($url) {
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'error' => 'cURL is not available on this server',
            'response' => null,
            'http_code' => 0
        ];
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    
    return [
        'success' => ($response !== false),
        'error' => $error,
        'errno' => $errno,
        'response' => $response,
        'http_code' => $http_code
    ];
}

// Function to test connection using file_get_contents
function test_file_get_contents($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    return [
        'success' => ($response !== false),
        'error' => error_get_last(),
        'response' => $response
    ];
}

// Function to test connection using fsockopen
function test_socket_connection($host, $port) {
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if ($fp) {
        // Try to get HTTP response
        fwrite($fp, "GET / HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n");
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        
        return [
            'success' => true,
            'error' => null,
            'response' => $response
        ];
    }
    
    return [
        'success' => false,
        'error' => "$errstr ($errno)",
        'response' => null
    ];
}

// Get server information
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
$server_name = $_SERVER['SERVER_NAME'] ?? 'Unknown';
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// URLs to test
$urls = [
    'localhost' => 'http://localhost:8080',
    '127.0.0.1' => 'http://127.0.0.1:8080',
    '0.0.0.0' => 'http://0.0.0.0:8080',
    'server_ip' => "http://$server_ip:8080",
    'server_name' => "http://$server_name:8080",
    'direct_ip' => 'http://37.27.31.107:8080'
];

// Run the tests
$curl_results = [];
$file_results = [];
$socket_results = [];

foreach ($urls as $name => $url) {
    $curl_results[$name] = test_curl_connection($url);
    $file_results[$name] = test_file_get_contents($url);
    
    $parsed_url = parse_url($url);
    $host = $parsed_url['host'];
    $port = $parsed_url['port'] ?? 80;
    $socket_results[$name] = test_socket_connection($host, $port);
}

// Function to display results in a readable format
function display_result($result, $type) {
    $success_class = $result['success'] ? 'success' : 'danger';
    $success_icon = $result['success'] ? 'check-circle' : 'times-circle';
    
    echo "<div class='alert alert-$success_class'>";
    echo "<i class='fas fa-$success_icon'></i> ";
    echo "<strong>" . ($result['success'] ? 'Success' : 'Failed') . "</strong>";
    
    if (!$result['success'] && isset($result['error']) && $result['error']) {
        echo " - Error: ";
        if (is_array($result['error'])) {
            echo htmlspecialchars(print_r($result['error'], true));
        } else {
            echo htmlspecialchars($result['error']);
        }
    }
    
    if ($type === 'curl' && isset($result['http_code'])) {
        echo " - HTTP Code: " . $result['http_code'];
    }
    
    echo "</div>";
    
    if ($result['success'] && isset($result['response']) && $result['response']) {
        echo "<div class='card mb-3'>";
        echo "<div class='card-header'>Response</div>";
        echo "<div class='card-body'>";
        echo "<pre class='mb-0' style='max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(substr($result['response'], 0, 500)) . (strlen($result['response']) > 500 ? '...' : '');
        echo "</pre>";
        echo "</div>";
        echo "</div>";
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Git Auto Deploy Webhook Test</h5>
                    <div>
                        <a href="book-import-tool.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Import Tool
                        </a>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-success">
                            <i class="fas fa-sync"></i> Run Tests Again
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <h3>Server Information</h3>
                    <table class="table table-bordered">
                        <tr>
                            <th>Server IP (SERVER_ADDR)</th>
                            <td><?php echo $server_ip; ?></td>
                        </tr>
                        <tr>
                            <th>Server Name (SERVER_NAME)</th>
                            <td><?php echo $server_name; ?></td>
                        </tr>
                        <tr>
                            <th>Remote Address (REMOTE_ADDR)</th>
                            <td><?php echo $remote_addr; ?></td>
                        </tr>
                        <tr>
                            <th>PHP Version</th>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <th>cURL Available</th>
                            <td><?php echo function_exists('curl_init') ? 'Yes' : 'No'; ?></td>
                        </tr>
                    </table>
                    
                    <h3 class="mt-4">Connection Tests</h3>
                    
                    <?php foreach ($urls as $name => $url): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($name); ?> - <?php echo htmlspecialchars($url); ?></h5>
                            </div>
                            <div class="card-body">
                                <h6>cURL Test</h6>
                                <?php display_result($curl_results[$name], 'curl'); ?>
                                
                                <h6>file_get_contents Test</h6>
                                <?php display_result($file_results[$name], 'file'); ?>
                                
                                <h6>Socket Test</h6>
                                <?php display_result($socket_results[$name], 'socket'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
