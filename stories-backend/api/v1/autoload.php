<?php
/**
 * Strict PSR-4 Autoloader
 * No case-insensitive fallback allowed
 */

spl_autoload_register(function ($class) {
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/';

    // Project namespace prefix
    $prefix = 'StoriesAPI\\';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Convert namespace to path
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Strict case-sensitive check
    if (file_exists($file)) {
        // Verify exact case match
        $realPath = realpath($file);
        $expectedPath = realpath($base_dir) . '/' . str_replace('\\', '/', $relative_class) . '.php';
        
        if ($realPath === $expectedPath) {
            require $file;
            return;
        }
    }
    
    error_log("Class not found or case mismatch: $class\nExpected path: $file");
    throw new \Exception(
        "Class not found or case mismatch: $class\n" .
        "Expected path: $file"
    );
});