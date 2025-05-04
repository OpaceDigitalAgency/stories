<?php
/**
 * Test script for the subscriber API
 * This script tests the save-subscriber.php endpoint directly
 */

// Set headers for JSON output
header('Content-Type: application/json');

// Test data
$testData = [
    'email' => 'test_' . time() . '@example.com',
    'feature' => 'premium stories',
    'name' => 'Test User',
    'message' => 'This is a test submission from test-subscriber-api.php'
];

// Log the test
echo json_encode([
    'status' => 'Testing API',
    'endpoint' => 'save-subscriber.php',
    'data' => $testData
]) . "\n\n";

// Create cURL request
$ch = curl_init('https://api.storiesfromtheweb.org/save-subscriber.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Origin: https://api.storiesfromtheweb.org'
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Output results
echo json_encode([
    'http_code' => $httpCode,
    'curl_error' => $error,
    'response' => json_decode($response, true)
]);
