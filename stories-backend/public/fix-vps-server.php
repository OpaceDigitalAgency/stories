<?php
/**
 * VPS Server Fix Script
 * 
 * This script attempts to fix the VPS server configuration to listen on all interfaces.
 * It requires SSH access to the VPS server.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Check for admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Authentication required. Please log in to the admin panel first.");
}

echo "VPS Server Fix Tool\n";
echo "==================\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsUser = 'root';
$vpsScraperPath = '/opt/book-scraper';
$serverJsPath = '/opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js';

echo "VPS Server: {$vpsIp}\n";
echo "VPS User: {$vpsUser}\n";
echo "Server.js Path: {$serverJsPath}\n\n";

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
file_put_contents($keyFile, getenv('VPS_SSH_KEY') ?: '');
chmod($keyFile, 0600);

// First, check the current server.js configuration
echo "Checking Current Configuration:\n";
echo "-----------------------------\n";
$checkCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat {$serverJsPath} | grep \"app.listen\"'";
$currentConfig = runCommand($checkCommand);
echo "Current configuration: {$currentConfig}\n\n";

// Check if the configuration already uses 0.0.0.0
if (strpos($currentConfig, '0.0.0.0') !== false) {
    echo "✅ Server is already configured to listen on all interfaces (0.0.0.0).\n";
} else {
    // Update the server.js file to listen on all interfaces
    echo "Updating Server Configuration:\n";
    echo "---------------------------\n";
    
    // Create a backup of the original file
    $backupCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cp {$serverJsPath} {$serverJsPath}.bak'";
    runCommand($backupCommand);
    echo "Created backup at {$serverJsPath}.bak\n";
    
    // Update the file using sed
    $sedCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'sed -i \"s/app.listen(3000, \'localhost\'/app.listen(3000, \'0.0.0.0\'/g\" {$serverJsPath}'";
    $updateResult = runCommand($sedCommand);
    echo "Update result: " . ($updateResult ?: "Command executed successfully") . "\n\n";
    
    // Check the updated configuration
    $updatedConfig = runCommand($checkCommand);
    echo "Updated configuration: {$updatedConfig}\n\n";
    
    if (strpos($updatedConfig, '0.0.0.0') !== false) {
        echo "✅ Server configuration updated successfully!\n";
    } else {
        echo "❌ Failed to update server configuration. Please check the server manually.\n";
    }
}

// Restart the scraper service
echo "Restarting Scraper Service:\n";
echo "-------------------------\n";
$restartCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cd {$vpsScraperPath} && pm2 restart all'";
$restartOutput = runCommand($restartCommand);
echo $restartOutput . "\n";

// Clean up the temporary key file
unlink($keyFile);

// Check if the API is now reachable
echo "Checking API Health:\n";
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

if ($httpCode === 200) {
    echo "✅ API is now reachable and healthy!\n";
} else {
    echo "❌ API is still not reachable. Please check the VPS server manually.\n";
    
    // Check if there might be a firewall issue
    echo "\nChecking for Firewall Issues:\n";
    echo "-------------------------\n";
    $firewallCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'iptables -L | grep 3000'";
    $firewallOutput = runCommand($firewallCommand);
    
    if (empty($firewallOutput)) {
        echo "No specific firewall rules found for port 3000.\n";
        echo "You may need to add a firewall rule to allow incoming connections on port 3000:\n";
        echo "ssh {$vpsUser}@{$vpsIp} 'iptables -A INPUT -p tcp --dport 3000 -j ACCEPT'\n";
    } else {
        echo "Firewall rules for port 3000:\n{$firewallOutput}\n";
    }
}

echo "\nFix Process Complete\n";
