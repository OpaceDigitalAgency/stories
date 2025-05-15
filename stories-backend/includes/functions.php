<?php
// Common functions used across the application

/**
 * Create a URL-friendly slug from a string
 * 
 * @param string $string The string to convert to a slug
 * @return string The slug
 */
function createSlug($string) {
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($string)));
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    // Replace multiple hyphens with a single hyphen
    $slug = preg_replace('/-+/', '-', $slug);
    return $slug;
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date string
 * 
 * @param string $date The date string to format
 * @param string $format The format to use (default: 'Y-m-d')
 * @return string The formatted date
 */
function formatDate($date, $format = 'Y-m-d') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Generate a random string
 * 
 * @param int $length The length of the string to generate
 * @return string The random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if the user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if a user has admin privileges
 * 
 * @return bool True if the user has admin privileges, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display a flash message
 * 
 * @param string $message The message to display
 * @param string $type The type of message (success, error, warning, info)
 */
function flashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear the flash message
 * 
 * @return array|null The flash message or null if there is none
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>
