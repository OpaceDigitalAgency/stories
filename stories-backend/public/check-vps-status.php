<?php
/**
 * VPS Status Checker
 *
 * This script performs a comprehensive check of the VPS server status
 * and provides detailed diagnostics about connectivity issues.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "VPS Server Status Check\n";
echo "======================\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsPort = 3000;
$vpsUrl = "http://{$vpsIp}:{$vpsPort}";

echo "VPS Server: {$vpsIp}\n";
echo "VPS Port: {$vpsPort}\n";
echo "VPS URL: {$vpsUrl}\n\n";

// Function to run a command and return the output
function runCommand($command) {
    $output = shell_exec($command . " 2>&1");
    return $output;
}

// Check if the server is pingable
echo "Ping Test:\n";
echo "---------\n";
$pingOutput = runCommand("ping -c 3 -W 2 {$vpsIp}");
echo $pingOutput . "\n";

// Check if the port is open
echo "Port Check:\n";
echo "-----------\n";
$portCheckOutput = runCommand("nc -zv -w 5 {$vpsIp} {$vpsPort} 2>&1");
echo $portCheckOutput . "\n";

// Try to connect to the health endpoint
echo "HTTP Health Check:\n";
echo "----------------\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$vpsUrl}/health");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorMsg = curl_error($ch);
$errorNum = curl_errno($ch);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n";
if ($errorNum) {
    echo "CURL Error ({$errorNum}): {$errorMsg}\n";
}
echo "Response: " . ($response ?: "No response") . "\n\n";

echo "CURL Verbose Log:\n";
echo "----------------\n";
echo $verboseLog . "\n";

// Check PM2 status on the VPS (if SSH access is available)
echo "PM2 Status Check:\n";
echo "----------------\n";
echo "Note: This requires SSH access to the VPS server.\n";
echo "To check PM2 status, run the following command on the VPS:\n";
echo "  ssh root@{$vpsIp} 'pm2 status'\n\n";

// Check server.js configuration
echo "Server Configuration Check:\n";
echo "------------------------\n";
echo "To check if the server is configured to listen on all interfaces, run:\n";
echo "  ssh root@{$vpsIp} 'cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep \"app.listen\"'\n";
echo "It should show: app.listen(3000, '0.0.0.0', () => ...\n";
echo "If it shows: app.listen(3000, 'localhost', () => ... then it's only listening on localhost!\n\n";

// Check if the server is actually listening on all interfaces
echo "Network Listening Check:\n";
echo "---------------------\n";
echo "To check if the server is actually listening on all interfaces, run:\n";
echo "  ssh root@{$vpsIp} 'netstat -tulpn | grep 3000'\n";
echo "It should show: tcp 0 0 0.0.0.0:3000 0.0.0.0:* LISTEN\n";
echo "If it shows: tcp 0 0 127.0.0.1:3000 0.0.0.0:* LISTEN then it's only listening on localhost!\n\n";

// Check if the server is behind a firewall
echo "Firewall Check:\n";
echo "--------------\n";
echo "To check if the server is behind a firewall, try accessing it from different networks.\n";
echo "If it's accessible from some networks but not others, it might be behind a firewall.\n\n";

// Check if the API key is correct
echo "API Key Check:\n";
echo "-------------\n";
echo "The current API key being used is: 'your-secret-api-key-here'\n";
echo "Make sure this matches the API key configured on the VPS server.\n\n";

// Provide recommendations
echo "Recommendations:\n";
echo "---------------\n";
if ($httpCode === 0) {
    echo "1. The VPS server is not reachable. Check if:\n";
    echo "   - The server is running\n";
    echo "   - The port is open and not blocked by a firewall\n";
    echo "   - The server's IP address is correct\n";
    echo "   - The server's network allows incoming connections\n\n";

    echo "2. Try restarting the scraper service on the VPS:\n";
    echo "   ssh root@{$vpsIp}\n";
    echo "   cd /opt/book-scraper\n";
    echo "   pm2 restart all\n\n";

    echo "3. Check the logs on the VPS:\n";
    echo "   ssh root@{$vpsIp}\n";
    echo "   cd /opt/book-scraper\n";
    echo "   pm2 logs\n\n";
} else if ($httpCode >= 400) {
    echo "1. The VPS server is reachable but returned an error. Check if:\n";
    echo "   - The API key is correct\n";
    echo "   - The scraper service is running properly\n";
    echo "   - The server logs show any errors\n\n";
} else {
    echo "1. The VPS server is reachable and responding correctly. Check if:\n";
    echo "   - The API key is correct\n";
    echo "   - The scraper endpoints are configured correctly\n";
    echo "   - The server logs show any errors when scraping\n\n";
}

echo "4. If all else fails, consider redeploying the scraper service:\n";
echo "   ssh root@{$vpsIp}\n";
echo "   cd /opt/book-scraper\n";
echo "   git pull\n";
echo "   npm install\n";
echo "   pm2 restart all\n\n";

echo "VPS Status Check Complete\n";
