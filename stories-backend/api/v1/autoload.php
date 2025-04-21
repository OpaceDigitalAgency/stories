<?php
/**
 * Strict PSR-4 Autoloader
 */

class Autoloader {
    private static $baseDir;
    private static $namespace = 'StoriesAPI\\';
    private static $directoryMap = [
        'Core' => true,
        'Middleware' => true,
        'Endpoints' => true,
        'Utils' => true,
        'Config' => true
    ];

    public static function register() {
        self::$baseDir = __DIR__;
        
        spl_autoload_register([self::class, 'loadClass']);
        
        // Enforce directory structure on startup
        self::validateDirectoryStructure();
    }
    
    public static function loadClass($class) {
        // Only handle our namespace
        if (strncmp(self::$namespace, $class, strlen(self::$namespace)) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, strlen(self::$namespace));
        
        // Split into parts
        $parts = explode('\\', $relativeClass);
        
        // Validate the top-level directory
        $topDir = $parts[0];
        if (!isset(self::$directoryMap[$topDir])) {
            // Try to find a case-insensitive match
            foreach (array_keys(self::$directoryMap) as $dir) {
                if (strcasecmp($dir, $topDir) === 0) {
                    $parts[0] = $dir; // Use the correct case
                    break;
                }
            }
        }
        
        // Convert namespace to path
        $file = self::$baseDir . '/' . implode('/', $parts) . '.php';
        
        // Try both capitalized and lowercase paths
        $paths = [
            $file,
            str_replace('/' . $parts[0] . '/', '/' . strtolower($parts[0]) . '/', $file)
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                require $path;
                return;
            }
        }
        
        error_log("Class not found: $class");
        error_log("Attempted paths: " . implode(", ", $paths));
    }
    
    private static function validateDirectoryStructure() {
        foreach (self::$directoryMap as $dir => $required) {
            $path = self::$baseDir . '/' . $dir;
            $lowercasePath = self::$baseDir . '/' . strtolower($dir);
            
            // If lowercase exists but uppercase doesn't, try to fix it
            if (!is_dir($path) && is_dir($lowercasePath)) {
                try {
                    rename($lowercasePath, $path);
                    error_log("Fixed directory case: $lowercasePath → $path");
                } catch (Exception $e) {
                    error_log("Failed to fix directory case: " . $e->getMessage());
                }
            }
            
            // Now check if the directory exists with proper case
            if ($required && !is_dir($path)) {
                throw new Exception(
                    "Required directory not found: $path\n" .
                    "Please ensure all directories use proper capitalization"
                );
            }
        }
    }
}

// Register autoloader
Autoloader::register();