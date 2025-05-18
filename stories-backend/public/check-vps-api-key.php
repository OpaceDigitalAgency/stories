<?php
/**
 * Check VPS API Key
 * 
 * This script checks the API key configured on the VPS server.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "Check VPS API Key\n";
echo "===============\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsUser = 'root';
$vpsScraperPath = '/opt/book-scraper/stories-backend/services/HeadlessBrowser';

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
    echo "2. Check the API key in config/default.js: cat {$vpsScraperPath}/config/default.js | grep apiKey\n";
    echo "3. Make sure it matches 'stories-scraper-api-key-2023'\n";
    die("\nExiting without making changes. Please run the commands manually.\n");
}

file_put_contents($keyFile, $sshKey);
chmod($keyFile, 0600);

// Check the API key in the config file
echo "Checking API Key in config/default.js:\n";
echo "-----------------------------------\n";
$configCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat {$vpsScraperPath}/config/default.js | grep apiKey'";
$configOutput = runCommand($configCommand);
echo $configOutput . "\n\n";

// Extract the API key from the output
if (preg_match('/apiKey:\s*[\'"]([^\'"]+)[\'"]/', $configOutput, $matches)) {
    $configuredApiKey = $matches[1];
    echo "Configured API Key: {$configuredApiKey}\n\n";
    
    // Check if it matches the expected key
    $expectedApiKey = 'stories-scraper-api-key-2023';
    if ($configuredApiKey === $expectedApiKey) {
        echo "✅ API key matches the expected value.\n";
    } else {
        echo "❌ API key does not match the expected value.\n";
        echo "Expected: {$expectedApiKey}\n";
        echo "Actual: {$configuredApiKey}\n\n";
        
        echo "Would you like to update the API key? (y/n): ";
        $input = trim(fgets(STDIN));
        
        if (strtolower($input) === 'y') {
            echo "Updating API key...\n";
            $updateCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} \"sed -i 's/apiKey:\\s*[\\'\\\"]([^\\'\\\"]*)[\\'\\\"]/apiKey: \\\"{$expectedApiKey}\\\"/g' {$vpsScraperPath}/config/default.js\"";
            $updateOutput = runCommand($updateCommand);
            echo $updateOutput . "\n";
            
            // Verify the update
            echo "Verifying update...\n";
            $verifyOutput = runCommand($configCommand);
            echo $verifyOutput . "\n";
            
            // Restart the service
            echo "Restarting the service...\n";
            $restartCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cd /opt/book-scraper && pm2 restart all'";
            $restartOutput = runCommand($restartCommand);
            echo $restartOutput . "\n";
        }
    }
} else {
    echo "❌ Could not extract API key from the output.\n";
}

// Clean up the temporary key file
unlink($keyFile);

echo "\nAPI Key Check Complete\n";
echo "====================\n";
echo "Next steps:\n";
echo "1. Make sure the API key in your PHP code matches the one on the VPS server\n";
echo "2. Test the scraper with the correct API key: https://api.storiesfromtheweb.org/test-direct-vps-connection.php?isbn=9780007416851&limit=50\n";
