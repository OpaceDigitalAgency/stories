<?php
/**
 * VPS Complete Fix Script
 * 
 * This script fixes both:
 * 1. The server.js file to listen on all interfaces (0.0.0.0)
 * 2. The deploy.json file to use the correct format for deploy commands
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "VPS Complete Fix Tool\n";
echo "====================\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsUser = 'root';
$vpsScraperPath = '/opt/book-scraper';
$serverJsPath = '/opt/book-scraper/stories-backend/services/HeadlessBrowser/server.js';
$deployJsonPath = '/opt/book-scraper/deploy.json';

echo "VPS Server: {$vpsIp}\n";
echo "VPS User: {$vpsUser}\n";
echo "Server.js Path: {$serverJsPath}\n";
echo "Deploy.json Path: {$deployJsonPath}\n\n";

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
    echo "2. Fix the server.js file:\n";
    echo "   nano {$serverJsPath}\n";
    echo "   Change 'localhost' to '0.0.0.0' in the app.listen line\n";
    echo "   Save the file (Ctrl+X, Y, Enter)\n\n";
    echo "3. Fix the deploy.json file:\n";
    echo "   nano {$deployJsonPath}\n";
    echo "   Change \"ondeploy\": \"command\" to \"deploy\": [\"command1\", \"command2\"]\n";
    echo "   Save the file (Ctrl+X, Y, Enter)\n\n";
    echo "4. Restart the services:\n";
    echo "   cd {$vpsScraperPath} && pm2 restart all\n";
    echo "   pkill -f gitautodeploy\n";
    echo "   python3 -m gitautodeploy --config {$deployJsonPath} --allow-root-user\n\n";
    die("Exiting without making changes. Please run the commands manually.\n");
}

file_put_contents($keyFile, $sshKey);
chmod($keyFile, 0600);

// PART 1: Fix the server.js file
echo "PART 1: Fixing server.js to listen on all interfaces\n";
echo "------------------------------------------------\n";

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

// PART 2: Fix the deploy.json file
echo "\nPART 2: Fixing deploy.json to use correct format for deploy commands\n";
echo "--------------------------------------------------------------\n";

// First, check the current deploy.json configuration
echo "Checking Current Configuration:\n";
echo "-----------------------------\n";
$checkDeployCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cat {$deployJsonPath}'";
$currentDeployConfig = runCommand($checkDeployCommand);
echo "Current deploy.json configuration:\n{$currentDeployConfig}\n\n";

// Check if the configuration uses "ondeploy" instead of "deploy"
if (strpos($currentDeployConfig, '"ondeploy"') !== false) {
    echo "⚠️ Found 'ondeploy' in deploy.json. This needs to be changed to 'deploy' with an array format.\n";
    
    // Create a backup of the original file
    $backupDeployCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cp {$deployJsonPath} {$deployJsonPath}.bak'";
    runCommand($backupDeployCommand);
    echo "Created backup at {$deployJsonPath}.bak\n";
    
    // Create a new deploy.json file with the correct format
    $newDeployJson = <<<EOT
{
  "allow-root-user": true,
  "repositories": [
    {
      "url": "https://github.com/OpaceDigitalAgency/stories.git",
      "branch": "main",
      "path": "/opt/stories",
      "deploy": [
        "cd /opt/stories/stories-backend/services/HeadlessBrowser",
        "npm install",
        "pm2 restart review-scraper"
      ]
    }
  ],
  "http-port": 8080
}
EOT;

    // Save the new deploy.json to a temporary file
    $tempDeployFile = tempnam(sys_get_temp_dir(), 'deploy_json_');
    file_put_contents($tempDeployFile, $newDeployJson);
    
    // Copy the new deploy.json to the VPS
    $scpCommand = "scp -i {$keyFile} -o StrictHostKeyChecking=no {$tempDeployFile} {$vpsUser}@{$vpsIp}:{$deployJsonPath}";
    $scpResult = runCommand($scpCommand);
    echo "SCP result: " . ($scpResult ?: "Command executed successfully") . "\n\n";
    
    // Check the updated configuration
    $updatedDeployConfig = runCommand($checkDeployCommand);
    echo "Updated deploy.json configuration:\n{$updatedDeployConfig}\n\n";
    
    if (strpos($updatedDeployConfig, '"deploy"') !== false && strpos($updatedDeployConfig, '[') !== false) {
        echo "✅ Deploy.json configuration updated successfully!\n";
    } else {
        echo "❌ Failed to update deploy.json configuration. Please check the server manually.\n";
    }
    
    // Clean up the temporary file
    unlink($tempDeployFile);
} else if (strpos($currentDeployConfig, '"deploy"') !== false && strpos($currentDeployConfig, '[') !== false) {
    echo "✅ Deploy.json is already configured correctly with 'deploy' array format.\n";
} else {
    echo "⚠️ Could not determine the format of deploy.json. Please check it manually.\n";
}

// PART 3: Restart the services
echo "\nPART 3: Restarting services\n";
echo "------------------------\n";

// Restart the scraper service
echo "Restarting Scraper Service:\n";
echo "-------------------------\n";
$restartCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'cd {$vpsScraperPath} && pm2 restart all'";
$restartOutput = runCommand($restartCommand);
echo $restartOutput . "\n";

// Restart the Git-Auto-Deploy service
echo "Restarting Git-Auto-Deploy Service:\n";
echo "-------------------------------\n";
$killCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'pkill -f gitautodeploy || true'";
$killOutput = runCommand($killCommand);
echo "Kill result: " . ($killOutput ?: "Command executed successfully") . "\n";

$startCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'nohup python3 -m gitautodeploy --config {$deployJsonPath} --allow-root-user > /opt/book-scraper/gitautodeploy.log 2>&1 &'";
$startOutput = runCommand($startCommand);
echo "Start result: " . ($startOutput ?: "Command executed successfully") . "\n\n";

// Clean up the temporary key file
unlink($keyFile);

// PART 4: Check if the services are running
echo "\nPART 4: Checking services\n";
echo "----------------------\n";

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

// Check if Git-Auto-Deploy is running
echo "Checking Git-Auto-Deploy:\n";
echo "----------------------\n";
$checkGadCommand = "ssh -i {$keyFile} -o StrictHostKeyChecking=no {$vpsUser}@{$vpsIp} 'ps aux | grep gitautodeploy | grep -v grep'";
$gadOutput = runCommand($checkGadCommand);
echo $gadOutput . "\n";

if (!empty($gadOutput)) {
    echo "✅ Git-Auto-Deploy is running!\n";
} else {
    echo "❌ Git-Auto-Deploy is not running. Please check the VPS server manually.\n";
}

echo "\nFix Process Complete\n";
echo "=================\n";
echo "Next steps:\n";
echo "1. Push a test change to GitHub to confirm auto-deployment works\n";
echo "2. Check if the scraper is returning more than 30 reviews\n";
echo "3. If there are still issues, check the logs on the VPS server\n";
