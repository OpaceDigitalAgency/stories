<?php
/**
 * Run API Tests
 * 
 * This script starts a local PHP server and runs the API endpoint tests.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting local PHP server for testing...\n";

// Start PHP built-in server in the background
$serverPort = 8000;
$serverHost = "localhost:$serverPort";
$serverRoot = __DIR__;
$serverCommand = "php -S $serverHost -t $serverRoot > /dev/null 2>&1 & echo $!";
$pid = exec($serverCommand);

echo "PHP server started with PID: $pid\n";
echo "Server running at http://$serverHost\n";

// Give the server a moment to start
sleep(2);

echo "\n=== Running Directory Items and AI Tools Endpoint Tests ===\n";
$testOutput = shell_exec("php " . __DIR__ . "/tests/DirectoryAndAiToolsEndpointTest.php");
echo $testOutput;

echo "\n=== Tests Completed ===\n";

// Kill the PHP server
echo "Stopping PHP server (PID: $pid)...\n";
exec("kill $pid");
echo "Server stopped.\n";

echo "\nTest run completed.\n";