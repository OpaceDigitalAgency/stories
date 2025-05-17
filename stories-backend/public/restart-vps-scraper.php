<?php
/**
 * VPS Scraper Restart Script
 * 
 * This script attempts to restart the VPS scraper service via SSH.
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

echo "VPS Scraper Restart Tool\n";
echo "=======================\n\n";

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

// Try to restart the scraper service via SSH
echo "Restarting Scraper Service:\n";
echo "-------------------------\n";

// Generate a temporary SSH key file
$keyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
file_put_contents($keyFile, getenv('VPS_SSH_KEY') ?: '');
chmod($keyFile, 0600);

// Build the SSH command
$sshCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cd {$vpsScraperPath} && pm2 restart all'";

// Execute the SSH command
echo "Executing: ssh {$vpsUser}@{$vpsIp} 'cd {$vpsScraperPath} && pm2 restart all'\n";
$restartOutput = runCommand($sshCommand);
echo $restartOutput . "\n";

// Clean up the temporary key file
unlink($keyFile);

// Check PM2 status
echo "Checking PM2 Status:\n";
echo "------------------\n";
$statusCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pm2 status'";
$statusOutput = runCommand($statusCommand);
echo $statusOutput . "\n";

// Check if the restart was successful
if (strpos($restartOutput, 'Processes restarted') !== false || strpos($statusOutput, 'online') !== false) {
    echo "✅ Scraper service restarted successfully!\n";
} else {
    echo "❌ Failed to restart scraper service. Please check the VPS server manually.\n";
}

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
}

echo "\nRestart Process Complete\n";
