<?php
/**
 * Configuration Reader
 * 
 * This file reads the shared configuration file and makes it available to PHP.
 */

// Define the path to the configuration file
$configPath = dirname(dirname(dirname(__FILE__))) . '/config/site.json';

// Initialize the configuration array
$siteConfig = [];

// Read and parse the configuration file
if (file_exists($configPath)) {
    $configJson = file_get_contents($configPath);
    $siteConfig = json_decode($configJson, true);
    
    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Error parsing site configuration: ' . json_last_error_msg());
    }
} else {
    error_log('Site configuration file not found at: ' . $configPath);
}

/**
 * Get a configuration value
 * 
 * @param string $key The configuration key (dot notation supported)
 * @param mixed $default The default value to return if the key is not found
 * @return mixed The configuration value
 */
function get_config($key, $default = null) {
    global $siteConfig;
    
    // Split the key into parts
    $parts = explode('.', $key);
    
    // Start with the full config array
    $value = $siteConfig;
    
    // Traverse the config array
    foreach ($parts as $part) {
        if (!isset($value[$part])) {
            return $default;
        }
        $value = $value[$part];
    }
    
    return $value;
}
