<?php
/**
 * Generate a JWT token for the admin user (ID = 1).
 * Usage: php generate-token.php
 */

require __DIR__ . '/api/v1/config/config.php';
require __DIR__ . '/api/v1/Core/Auth.php';

use StoriesAPI\Core\Auth;

// Initialize Auth utility
Auth::init($config['security']);

$token = Auth::generateToken(1);
if ($token) {
    echo $token;
} else {
    echo "Error: Unable to generate token\n";
}