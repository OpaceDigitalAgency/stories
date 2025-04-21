<?php
/**
 * Strict PSR-4 Autoloader with Case Sensitivity Enforcement
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
            throw new \Exception(
                "Invalid namespace structure. Top-level directory must be one of: " .
                implode(', ', array_keys(self::$directoryMap))
            );
        }
        
        // Convert namespace to path
        $file = self::$baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Strict case-sensitive check
        if (file_exists($file)) {
            // Verify exact case match
            $realPath = realpath($file);
            $expectedPath = realpath(self::$baseDir) . '/' . str_replace('\\', '/', $relativeClass) . '.php';
            
            if ($realPath === $expectedPath) {
                require $file;
                return;
            }
        }
        
        // If we get here, either the file doesn't exist or there's a case mismatch
        throw new \Exception(
            "Class not found or case mismatch: $class\n" .
            "Expected path: $file\n" .
            "Make sure both the file path and namespace use proper capitalization:\n" .
            "- Directory: {$parts[0]} (must be capitalized)\n" .
            "- Namespace: StoriesAPI\\{$parts[0]}"
        );
    }
    
    private static function validateDirectoryStructure() {
        foreach (self::$directoryMap as $dir => $required) {
            $path = self::$baseDir . '/' . $dir;
            $lowercasePath = self::$baseDir . '/' . strtolower($dir);
            
            // Check for lowercase directory
            if (is_dir($lowercasePath) && $lowercasePath !== $path) {
                throw new \Exception(
                    "Invalid directory structure: Found lowercase directory '$lowercasePath'\n" .
                    "Directory must be capitalized: '$path'"
                );
            }
            
            // Check for missing required directory
            if ($required && !is_dir($path)) {
                throw new \Exception(
                    "Missing required directory: '$path'\n" .
                    "Please create this directory with proper capitalization"
                );
            }
        }
    }
    
    public static function validateFile($file) {
        // Get relative path from base directory
        $relativePath = str_replace(self::$baseDir . '/', '', $file);
        $parts = explode('/', $relativePath);
        
        // Check top-level directory
        if (isset($parts[0])) {
            $topDir = $parts[0];
            $expectedDir = null;
            
            // Find case-sensitive match
            foreach (array_keys(self::$directoryMap) as $dir) {
                if (strcasecmp($topDir, $dir) === 0) {
                    $expectedDir = $dir;
                    break;
                }
            }
            
            if ($expectedDir && $topDir !== $expectedDir) {
                throw new \Exception(
                    "Invalid directory case in file: $file\n" .
                    "Found: $topDir\n" .
                    "Expected: $expectedDir"
                );
            }
        }
        
        // Check namespace declaration
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($file);
            if (preg_match('/namespace\s+StoriesAPI\\\\([^;]+);/', $content, $matches)) {
                $namespacePart = $matches[1];
                $parts = explode('\\', $namespacePart);
                
                if (isset($parts[0])) {
                    $topNamespace = $parts[0];
                    if (!isset(self::$directoryMap[$topNamespace])) {
                        throw new \Exception(
                            "Invalid namespace in file: $file\n" .
                            "Found: StoriesAPI\\$topNamespace\n" .
                            "Must be one of: " . implode(', ', array_keys(self::$directoryMap))
                        );
                    }
                }
            }
        }
    }
}

// Register autoloader
Autoloader::register();