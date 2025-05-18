<?php
/**
 * VPS Logs Checker
 * 
 * This script checks the logs on the VPS server to help diagnose issues with the scraper.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "VPS Logs Checker\n";
echo "===============\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsUser = 'root';
$vpsScraperPath = '/opt/book-scraper';

echo "VPS Server: {$vpsIp}\n";
echo "VPS User: {$vpsUser}\n";
echo "Scraper Path: {$vpsScraperPath}\n\n";

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

if (!strpos($pingOutput, 'bytes from')) {
    die("Error: VPS server is not reachable. Please check the server status.");
}

// Generate a temporary SSH key file
$keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
// Use a placeholder SSH key - you'll need to provide your own SSH key
$sshKey = <<<EOT
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAABlwAAAAdzc2gtcn
NhAAAAAwEAAQAAAYEAzBJh+fDJhiVvs8CxS1hpLMgRQwz1P7YHwvxG8hxx5PUaJLgCF/Ht
KlQBZ2/cYhJpV9K5qVRoT5CxbKKEDi0/jJzQnqDYcYWAXTF5Sqrm4Zr8N4N+AfYCghYnk3
Uj4XZSQw/jWK0NmYXeOHlM1+29TYpJ9+vUzUFTK0VVy1rXwOiGOlSLm+KI/YbKrXLVPCHT
EOT;

// Use a placeholder SSH key if you don't want to hardcode it
// You'll need to provide the actual key when running the script
if (empty($sshKey) || $sshKey === "-----BEGIN OPENSSH PRIVATE KEY-----\nEOT") {
    echo "⚠️ No SSH key provided. Please edit this script to add your SSH key.\n";
    echo "You can also run these commands manually on the VPS server:\n\n";
    echo "1. SSH into the VPS: ssh {$vpsUser}@{$vpsIp}\n";
    echo "2. Check PM2 logs: pm2 logs review-scraper --lines 100\n";
    echo "3. Check server.js configuration: cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep \"app.listen\"\n";
    echo "4. Check if the server is listening on all interfaces: netstat -tulpn | grep 3000\n";
    die("\nExiting without making changes. Please run the commands manually.\n");
}

file_put_contents($keyFile, $sshKey);
chmod($keyFile, 0600);

// Check PM2 status
echo "PM2 Status:\n";
echo "----------\n";
$pm2StatusCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pm2 status'";
$pm2Status = runCommand($pm2StatusCommand);
echo $pm2Status . "\n\n";

// Check PM2 logs
echo "PM2 Logs (last 50 lines):\n";
echo "-----------------------\n";
$pm2LogsCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pm2 logs review-scraper --lines 50'";
$pm2Logs = runCommand($pm2LogsCommand);
echo $pm2Logs . "\n\n";

// Check server.js configuration
echo "Server.js Configuration:\n";
echo "----------------------\n";
$serverJsCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep \"app.listen\"'";
$serverJs = runCommand($serverJsCommand);
echo $serverJs . "\n\n";

// Check if the server is listening on all interfaces
echo "Network Listening Check:\n";
echo "---------------------\n";
$netstatCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'netstat -tulpn | grep 3000'";
$netstat = runCommand($netstatCommand);
echo $netstat . "\n\n";

// Check the scraper endpoints
echo "Scraper Endpoints:\n";
echo "----------------\n";
$endpointsCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep -A 20 \"app.get\"'";
$endpoints = runCommand($endpointsCommand);
echo $endpoints . "\n\n";

// Check the API key configuration
echo "API Key Configuration:\n";
echo "-------------------\n";
$apiKeyCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat /opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js | grep -A 5 \"apiKey\"'";
$apiKey = runCommand($apiKeyCommand);
echo $apiKey . "\n\n";

// Check if the server is running
echo "Server Process Check:\n";
echo "------------------\n";
$processCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'ps aux | grep node | grep -v grep'";
$process = runCommand($processCommand);
echo $process . "\n\n";

// Check the server health endpoint
echo "Server Health Check:\n";
echo "-----------------\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://{$vpsIp}:3000/health");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n";
echo "Response: " . ($response ?: "No response") . "\n\n";

// Clean up the temporary key file
unlink($keyFile);

echo "Log Check Complete\n";
echo "=================\n";
echo "Next steps:\n";
echo "1. Check if the server is listening on all interfaces (should show 0.0.0.0:3000)\n";
echo "2. Check if the API key in the server matches the one in the PHP code\n";
echo "3. Check if there are any errors in the PM2 logs\n";
echo "4. Try restarting the scraper: ssh {$vpsUser}@{$vpsIp} 'pm2 restart review-scraper'\n";
