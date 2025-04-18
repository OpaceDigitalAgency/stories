<?php
/**
 * Class Loader
 * 
 * Handles class loading with case insensitivity and robust path resolution.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Core;

class ClassLoader {
    private static $instance = null;
    private $baseDir;
    private $namespacePrefix;
    private $loadedPaths = [];
    
    /**
     * Constructor
     * 
     * @param string $baseDir Base directory for class files
     * @param string $namespacePrefix Namespace prefix for the project
     */
    private function __construct($baseDir, $namespacePrefix) {
        $this->baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $this->namespacePrefix = $namespacePrefix;
    }
    
    /**
     * Get singleton instance
     * 
     * @param string $baseDir Base directory for class files
     * @param string $namespacePrefix Namespace prefix for the project
     * @return ClassLoader
     */
    public static function getInstance($baseDir = null, $namespacePrefix = 'StoriesAPI\\') {
        if (self::$instance === null) {
            self::$instance = new self($baseDir, $namespacePrefix);
        }
        return self::$instance;
    }
    
    /**
     * Register the class loader
     */
    public function register() {
        spl_autoload_register([$this, 'loadClass']);
    }
    
    /**
     * Load a class
     * 
     * @param string $class Full class name
     * @return bool Whether the class was loaded
     */
    public function loadClass($class) {
        // Check if class uses our namespace
        if (strncmp($this->namespacePrefix, $class, strlen($this->namespacePrefix)) !== 0) {
            return false;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, strlen($this->namespacePrefix));
        
        // Convert namespace separators to directory separators
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        
        // Try to load the class
        return $this->loadFile($this->baseDir, $relativePath);
    }
    
    /**
     * Load a file with case-insensitive path resolution
     * 
     * @param string $basePath Base path
     * @param string $relativePath Relative path
     * @return bool Whether the file was loaded
     */
    private function loadFile($basePath, $relativePath) {
        // First try the exact path
        $file = $basePath . $relativePath;
        if (file_exists($file)) {
            require_once $file;
            $this->loadedPaths[$relativePath] = $file;
            return true;
        }
        
        // Split the path into parts
        $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
        $currentPath = $basePath;
        
        // Traverse the path parts
        foreach ($parts as $i => $part) {
            $found = false;
            
            // If this is the last part (the file)
            if ($i === count($parts) - 1) {
                if (isset($this->loadedPaths[$relativePath])) {
                    require_once $this->loadedPaths[$relativePath];
                    return true;
                }
                
                // Look for the file case-insensitively
                $files = scandir($currentPath);
                foreach ($files as $file) {
                    if (strtolower($file) === strtolower($part)) {
                        $fullPath = $currentPath . DIRECTORY_SEPARATOR . $file;
                        require_once $fullPath;
                        $this->loadedPaths[$relativePath] = $fullPath;
                        return true;
                    }
                }
            } else {
                // Look for the directory case-insensitively
                $dirs = scandir($currentPath);
                foreach ($dirs as $dir) {
                    if (is_dir($currentPath . DIRECTORY_SEPARATOR . $dir) && 
                        strtolower($dir) === strtolower($part)) {
                        $currentPath .= DIRECTORY_SEPARATOR . $dir;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    error_log("Directory not found: {$part} in {$currentPath}");
                    return false;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get the real path for a class file
     * 
     * @param string $class Class name
     * @return string|false The real path or false if not found
     */
    public function getClassPath($class) {
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, 
            substr($class, strlen($this->namespacePrefix))) . '.php';
        return isset($this->loadedPaths[$relativePath]) 
            ? $this->loadedPaths[$relativePath] 
            : false;
    }
}