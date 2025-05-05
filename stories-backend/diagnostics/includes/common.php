<?php
/**
 * Common Functions for Diagnostic Tools
 * 
 * This file contains common functions used by all diagnostic tools.
 */

/**
 * Get the base URL for the application
 * 
 * @return string The base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    
    // Remove 'diagnostics/xxx' from path
    $path = preg_replace('/\/diagnostics\/.*$/', '', $path);
    
    return "$protocol://$host$path";
}

/**
 * Get the base API URL
 * 
 * @return string The base API URL
 */
function getBaseApiUrl() {
    return getBaseUrl() . '/api';
}

/**
 * Format file size in human-readable format
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Check if a directory is writable and create it if it doesn't exist
 * 
 * @param string $dir Directory path
 * @return array Result of the check
 */
function checkDirectory($dir) {
    $result = [
        'path' => $dir,
        'exists' => false,
        'writable' => false,
        'created' => false,
        'error' => null
    ];
    
    // Check if directory exists
    if (file_exists($dir)) {
        $result['exists'] = true;
        $result['writable'] = is_writable($dir);
    } else {
        // Try to create directory
        try {
            if (mkdir($dir, 0755, true)) {
                $result['exists'] = true;
                $result['created'] = true;
                $result['writable'] = is_writable($dir);
            } else {
                $result['error'] = 'Failed to create directory';
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
    }
    
    return $result;
}

/**
 * Check if a PHP extension is loaded
 * 
 * @param string $extension Extension name
 * @return bool True if loaded, false otherwise
 */
function checkExtension($extension) {
    return extension_loaded($extension);
}

/**
 * Test database connection
 * 
 * @param array $config Database configuration
 * @return array Result of the test
 */
function testDatabaseConnection($config) {
    $result = [
        'success' => false,
        'error' => null,
        'tables' => []
    ];
    
    try {
        // Connect to database
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $db = new PDO($dsn, $config['user'], $config['password'], $options);
        $result['success'] = true;
        
        // Get tables
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $result['tables'] = $tables;
    } catch (PDOException $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Output diagnostic information in a standardized format
 * 
 * @param string $title Title of the diagnostic
 * @param mixed $data Data to display
 * @param bool $success Whether the diagnostic was successful
 */
function outputDiagnostic($title, $data, $success = true) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header " . ($success ? 'bg-success text-white' : 'bg-danger text-white') . "'>";
    echo "<h3 class='m-0'>" . htmlspecialchars($title) . "</h3>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    if (is_array($data) || is_object($data)) {
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    } else {
        echo "<p>" . htmlspecialchars($data) . "</p>";
    }
    
    echo "</div>";
    echo "</div>";
}
