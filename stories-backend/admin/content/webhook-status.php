<?php
/**
 * Webhook Status Check
 * 
 * This script directly checks the status of the Git Auto Deploy webhook
 * and returns a JSON response.
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Function to check webhook status using multiple methods
function check_webhook_status() {
    $results = [];
    $webhook_running = false;
    
    // URLs to check
    $urls = [
        'localhost' => 'http://localhost:8080',
        '127.0.0.1' => 'http://127.0.0.1:8080',
        'server_ip' => isset($_SERVER['SERVER_ADDR']) ? "http://{$_SERVER['SERVER_ADDR']}:8080" : null,
        'direct_ip' => 'http://37.27.31.107:8080'
    ];
    
    // Remove null entries
    $urls = array_filter($urls);
    
    // Check using cURL
    if (function_exists('curl_init')) {
        foreach ($urls as $name => $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            
            $results['curl'][$name] = [
                'success' => ($response !== false || ($http_code > 0 && $http_code < 500)),
                'error' => $error,
                'errno' => $errno,
                'http_code' => $http_code
            ];
            
            if ($results['curl'][$name]['success']) {
                $webhook_running = true;
            }
        }
    }
    
    // Check using file_get_contents
    foreach ($urls as $name => $url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 2,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        $results['file_get_contents'][$name] = [
            'success' => ($response !== false),
            'error' => error_get_last()
        ];
        
        if ($results['file_get_contents'][$name]['success']) {
            $webhook_running = true;
        }
    }
    
    // Check using fsockopen
    foreach ($urls as $name => $url) {
        $parsed_url = parse_url($url);
        $host = $parsed_url['host'];
        $port = $parsed_url['port'] ?? 80;
        
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 2);
        
        $results['fsockopen'][$name] = [
            'success' => ($fp !== false),
            'error' => $errstr,
            'errno' => $errno
        ];
        
        if ($fp) {
            fclose($fp);
            $webhook_running = true;
        }
    }
    
    // Check using socket_create (lower level)
    if (function_exists('socket_create')) {
        foreach ($urls as $name => $url) {
            $parsed_url = parse_url($url);
            $host = $parsed_url['host'];
            $port = $parsed_url['port'] ?? 80;
            
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $result = @socket_connect($socket, $host, $port);
            
            $results['socket'][$name] = [
                'success' => ($result !== false),
                'error' => socket_strerror(socket_last_error($socket))
            ];
            
            if ($socket) {
                socket_close($socket);
            }
            
            if ($results['socket'][$name]['success']) {
                $webhook_running = true;
            }
        }
    }
    
    // Get the last auto-pull timestamp
    $last_pull = 'Unknown';
    $logFile = '/var/log/git-auto-deploy.log';
    if (file_exists($logFile)) {
        $lastLine = exec("tail -n 1 " . escapeshellarg($logFile));
        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lastLine, $matches)) {
            $last_pull = $matches[1];
        }
    } else {
        // Fallback: check the git directory for last commit time
        $gitDir = dirname(dirname(dirname(__DIR__))) . '/.git';
        if (is_dir($gitDir)) {
            $lastCommitTime = exec("git --git-dir=" . escapeshellarg($gitDir) . " log -1 --format=%cd --date=format:'%Y-%m-%d %H:%M:%S'");
            if ($lastCommitTime) {
                $last_pull = $lastCommitTime;
            }
        }
    }
    
    // Check if the webhook process is running
    $process_running = false;
    $process_info = [];
    
    // Try to find the process using ps
    exec("ps aux | grep 'git-auto-deploy' | grep -v grep", $output, $return_var);
    if (!empty($output)) {
        $process_running = true;
        $process_info = $output;
    }
    
    // Return the final result
    return [
        'webhook_running' => $webhook_running,
        'process_running' => $process_running,
        'process_info' => $process_info,
        'last_pull' => $last_pull,
        'server_info' => [
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'php_version' => phpversion(),
            'curl_available' => function_exists('curl_init'),
            'socket_available' => function_exists('socket_create')
        ],
        'test_results' => $results
    ];
}

// Run the check and output the result
echo json_encode(check_webhook_status(), JSON_PRETTY_PRINT);
