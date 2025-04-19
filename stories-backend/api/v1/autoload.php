<?php
/**
 * Simple PSR-4 Autoloader
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

    // Replace namespace separators with directory separators
    // and append .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    error_log("Autoloader looking for: " . $file);

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        error_log("Autoloader loaded: " . $file);
    } else {
        error_log("Autoloader failed to find: " . $file);
    }
});