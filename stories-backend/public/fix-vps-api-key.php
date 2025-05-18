<?php
/**
 * Fix VPS API Key
 * 
 * This script updates the API key in the VPS server to match the one in the PHP code.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "Fix VPS API Key\n";
echo "==============\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsUser = 'root';
$vpsScraperPath = '/opt/book-scraper/stories-backend/services/HeadlessBrowser';

// API key to set
$apiKey = isset($_GET['key']) ? $_GET['key'] : 'your-api-key-here';

echo "VPS Server: {$vpsIp}\n";
echo "VPS User: {$vpsUser}\n";
echo "Scraper Path: {$vpsScraperPath}\n";
echo "API Key: {$apiKey}\n\n";

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
    echo "2. Edit the server.js file: nano {$vpsScraperPath}/server.js\n";
    echo "3. Find the apiKey variable and update it to: {$apiKey}\n";
    echo "4. Save the file and restart the scraper: pm2 restart review-scraper\n";
    die("\nExiting without making changes. Please run the commands manually.\n");
}

file_put_contents($keyFile, $sshKey);
chmod($keyFile, 0600);

// Check current API key
echo "Current API Key:\n";
echo "--------------\n";
$currentApiKeyCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat {$vpsScraperPath}/server.js | grep \"apiKey\"'";
$currentApiKey = runCommand($currentApiKeyCommand);
echo $currentApiKey . "\n\n";

// Update API key
echo "Updating API Key:\n";
echo "---------------\n";
$updateApiKeyCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} \"sed -i 's/const apiKey = \\\"[^\\\"]*\\\"/const apiKey = \\\"{$apiKey}\\\"/g' {$vpsScraperPath}/server.js\"";
$updateApiKeyOutput = runCommand($updateApiKeyCommand);
echo "API key updated.\n\n";

// Verify API key update
echo "Verifying API Key Update:\n";
echo "----------------------\n";
$verifyApiKeyCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat {$vpsScraperPath}/server.js | grep \"apiKey\"'";
$verifyApiKey = runCommand($verifyApiKeyCommand);
echo $verifyApiKey . "\n\n";

// Restart the scraper
echo "Restarting Scraper:\n";
echo "-----------------\n";
$restartCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pm2 restart review-scraper'";
$restartOutput = runCommand($restartCommand);
echo $restartOutput . "\n\n";

// Check PM2 status
echo "PM2 Status:\n";
echo "----------\n";
$pm2StatusCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pm2 status'";
$pm2Status = runCommand($pm2StatusCommand);
echo $pm2Status . "\n\n";

// Clean up the temporary key file
unlink($keyFile);

echo "API Key Update Complete\n";
echo "=====================\n";
echo "Next steps:\n";
echo "1. Update the API key in your PHP code to match: {$apiKey}\n";
echo "2. Test the scraper with the new API key: https://api.storiesfromtheweb.org/test-direct-vps-connection.php?isbn=9780007416851&limit=50\n";
echo "3. If it still doesn't work, check the logs: https://api.storiesfromtheweb.org/check-vps-logs.php\n";
