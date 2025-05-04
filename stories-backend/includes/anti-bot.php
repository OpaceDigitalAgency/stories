<?php
/**
 * Anti-Bot Protection Functions
 * 
 * This file contains functions to help protect forms from bot submissions
 * without requiring CAPTCHA.
 */

/**
 * Check if a request is likely from a bot
 * 
 * @param array $data The form data
 * @return bool True if the request is likely from a bot, false otherwise
 */
function isLikelyBot($data = []) {
    // Check for common bot signatures
    
    // 1. Check user agent
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $suspiciousUserAgents = [
        'bot', 'crawl', 'spider', 'curl', 'wget', 'python', 'http', 'java', 'perl',
        'headless', 'phantom', 'selenium', 'chrome-lighthouse'
    ];
    
    foreach ($suspiciousUserAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            error_log("Suspicious user agent detected: $userAgent");
            return true;
        }
    }
    
    // 2. Check if request has no user agent or referer
    if (empty($userAgent) || empty($_SERVER['HTTP_REFERER'])) {
        error_log("Missing user agent or referer");
        return true;
    }
    
    // 3. Check for abnormally fast form submission
    if (isset($_SESSION['form_start_time'])) {
        $timeTaken = time() - $_SESSION['form_start_time'];
        if ($timeTaken < 2) { // Less than 2 seconds to fill out the form
            error_log("Form submitted too quickly: $timeTaken seconds");
            return true;
        }
    }
    
    // 4. Check for hidden honeypot field
    if (!empty($data['website']) || !empty($data['url']) || !empty($data['honey'])) {
        error_log("Honeypot field filled: Bot detected");
        return true;
    }
    
    // 5. Check for missing or invalid token
    if (empty($data['token']) || !validateToken($data['token'])) {
        error_log("Missing or invalid token");
        return true;
    }
    
    // 6. Check for too many submissions from the same IP
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($ipAddress)) {
        $submissionCount = getSubmissionCountFromIP($ipAddress);
        if ($submissionCount > 5) { // More than 5 submissions in the last hour
            error_log("Too many submissions from IP: $ipAddress");
            return true;
        }
    }
    
    return false;
}

/**
 * Generate a form token
 * 
 * @return string The generated token
 */
function generateToken() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['form_token'] = $token;
    $_SESSION['form_start_time'] = time();
    
    return $token;
}

/**
 * Validate a form token
 * 
 * @param string $token The token to validate
 * @return bool True if the token is valid, false otherwise
 */
function validateToken($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (empty($_SESSION['form_token'])) {
        return false;
    }
    
    $valid = hash_equals($_SESSION['form_token'], $token);
    
    // Clear the token after validation to prevent reuse
    unset($_SESSION['form_token']);
    
    return $valid;
}

/**
 * Get the number of submissions from an IP address in the last hour
 * 
 * @param string $ipAddress The IP address to check
 * @return int The number of submissions
 */
function getSubmissionCountFromIP($ipAddress) {
    // Create a file-based tracking system
    $logFile = sys_get_temp_dir() . '/form_submissions.log';
    
    // Clean up old entries first
    cleanupSubmissionLog($logFile);
    
    // Count recent submissions from this IP
    $count = 0;
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($ip, $timestamp) = explode('|', $line);
            if ($ip === $ipAddress && $timestamp > time() - 3600) {
                $count++;
            }
        }
    }
    
    // Log this submission
    file_put_contents($logFile, "$ipAddress|" . time() . "\n", FILE_APPEND);
    
    return $count;
}

/**
 * Clean up old entries from the submission log
 * 
 * @param string $logFile The path to the log file
 */
function cleanupSubmissionLog($logFile) {
    if (!file_exists($logFile)) {
        return;
    }
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    
    foreach ($lines as $line) {
        list($ip, $timestamp) = explode('|', $line);
        if ($timestamp > time() - 3600) { // Keep entries from the last hour
            $newLines[] = $line;
        }
    }
    
    file_put_contents($logFile, implode("\n", $newLines) . "\n");
}

/**
 * Generate honeypot field HTML
 * 
 * @return string The HTML for the honeypot field
 */
function generateHoneypotField() {
    return '<input type="text" name="website" style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;" tabindex="-1" autocomplete="off">';
}

/**
 * Generate token field HTML
 * 
 * @return string The HTML for the token field
 */
function generateTokenField() {
    $token = generateToken();
    return '<input type="hidden" name="token" value="' . $token . '">';
}
