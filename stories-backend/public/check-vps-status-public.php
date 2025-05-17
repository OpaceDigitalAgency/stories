<?php
/**
 * VPS Status Checker (Public Version)
 * 
 * This script performs a comprehensive check of the VPS server status
 * and provides detailed diagnostics about connectivity issues.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "VPS Server Status Check (Public Version)\n";
echo "====================================\n\n";

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

// Manual instructions for checking server configuration
echo "Server Configuration Check:\n";
echo "------------------------\n";
echo "To check if the server is configured to listen on all interfaces, run:\n";
echo "  ssh root@{$vpsIp} 'cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep \"app.listen\"'\n";
echo "It should show: app.listen(3000, '0.0.0.0', () => ...\n";
echo "If it shows: app.listen(3000, 'localhost', () => ... then it's only listening on localhost!\n\n";

// Manual instructions for checking network listening
echo "Network Listening Check:\n";
echo "---------------------\n";
echo "To check if the server is actually listening on all interfaces, run:\n";
echo "  ssh root@{$vpsIp} 'netstat -tulpn | grep 3000'\n";
echo "It should show: tcp 0 0 0.0.0.0:3000 0.0.0.0:* LISTEN\n";
echo "If it shows: tcp 0 0 127.0.0.1:3000 0.0.0.0:* LISTEN then it's only listening on localhost!\n\n";

// Manual instructions for checking firewall
echo "Firewall Check:\n";
echo "--------------\n";
echo "To check if the server is behind a firewall, run:\n";
echo "  ssh root@{$vpsIp} 'iptables -L | grep 3000'\n";
echo "If there are no rules for port 3000, you may need to add one:\n";
echo "  ssh root@{$vpsIp} 'iptables -A INPUT -p tcp --dport 3000 -j ACCEPT'\n\n";

// Manual instructions for fixing the server configuration
echo "Manual Fix Instructions:\n";
echo "---------------------\n";
echo "If the server is only listening on localhost, you can fix it with these commands:\n";
echo "1. SSH into the VPS: ssh root@{$vpsIp}\n";
echo "2. Edit the server.js file: nano /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js\n";
echo "3. Change 'localhost' to '0.0.0.0' in the app.listen line\n";
echo "4. Save the file and exit (Ctrl+X, Y, Enter)\n";
echo "5. Restart the service: cd /opt/book-scraper && pm2 restart all\n\n";

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
    
    echo "4. Most importantly, check if the server is listening on all interfaces:\n";
    echo "   ssh root@{$vpsIp}\n";
    echo "   cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep \"app.listen\"\n";
    echo "   If it shows 'localhost', change it to '0.0.0.0' as described in the Manual Fix Instructions above.\n\n";
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

echo "VPS Status Check Complete\n";
