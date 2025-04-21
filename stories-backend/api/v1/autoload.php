<?php
/**
 * PSR-4 Autoloader with Case-Sensitivity Handling
 */

spl_autoload_register(function ($class) {
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // Project namespace prefix
    $prefix = 'StoriesAPI\\';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Convert namespace separators to directory separators
    $file_path = str_replace('\\', '/', $relative_class);

    // Try different case variations for the path components
    $path_parts = explode('/', $file_path);
    $current_path = $base_dir;
    $final_path = '';

    foreach ($path_parts as $part) {
        // If this is the last part (the class file)
        if ($part === end($path_parts)) {
            $file = $part . '.php';
            // Try exact case first
            if (file_exists($current_path . $file)) {
                $final_path = $current_path . $file;
                break;
            }
            // Try case-insensitive search
            $files = scandir($current_path);
            foreach ($files as $f) {
                if (strcasecmp($f, $file) === 0) {
                    $final_path = $current_path . $f;
                    break 2;
                }
            }
        } else {
            // For directories, try exact case first
            if (is_dir($current_path . $part)) {
                $current_path .= $part . '/';
                continue;
            }
            // Try case-insensitive search
            $dirs = scandir($current_path);
            foreach ($dirs as $d) {
                if (strcasecmp($d, $part) === 0 && is_dir($current_path . $d)) {
                    $current_path .= $d . '/';
                    continue 2;
                }
            }
        }
    }

    error_log("Autoloader looking for: " . $class);
    error_log("Final path: " . $final_path);

    // If we found the file, require it
    if ($final_path && file_exists($final_path)) {
        require $final_path;
        error_log("Autoloader loaded: " . $final_path);
        return;
    }

    error_log("Autoloader failed to find class: " . $class);
    error_log("Tried path: " . $file_path);
});