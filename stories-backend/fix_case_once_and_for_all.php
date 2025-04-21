<?php
/**
 * Complete Case Sensitivity Fix
 * 
 * USAGE:
 * 1. Upload this file to your project root
 * 2. Run via browser or CLI: php fix_case_once_and_for_all.php
 * 
 * This script will:
 * 1. Fix all directory and file naming
 * 2. Remove duplicates and backups
 * 3. Update code references
 * 4. Install strict autoloader
 * 5. Add prevention measures
 */

class CaseSensitivityFixer {
    private $baseDir;
    private $correctCases = [
        'core' => 'Core',
        'middleware' => 'Middleware',
        'endpoints' => 'Endpoints',
        'utils' => 'Utils',
        'config' => 'Config'
    ];
    private $backupPatterns = [
        '*.bak*',    // Matches .bak with any numbers
        '*.orig',
        '*.old',
        '*.tmp',
        '*copy*',
        '*backup*'
    ];
    private $log = [];

    public function __construct() {
        $this->baseDir = dirname(__FILE__) . '/api/v1';
        if (!is_dir($this->baseDir)) {
            die("Error: Base directory not found: {$this->baseDir}\n");
        }
    }

    public function fix() {
        $this->log[] = "Starting comprehensive case sensitivity fix...";
        $this->log[] = "Base directory: {$this->baseDir}";

        // Create backup
        $backupDir = $this->createBackup();
        $this->log[] = "Created backup in: $backupDir";

        try {
            // 1. Remove duplicate directories
            $this->removeDuplicateDirectories();

            // 2. Fix directory cases
            $this->fixDirectoryCases();

            // 3. Clean up backup files
            $this->removeBackupFiles();

            // 4. Update code references
            $this->updateCodeReferences();

            // 5. Install strict autoloader
            $this->installStrictAutoloader();

            // 6. Add prevention measures
            $this->addPreventionMeasures();

            // 7. Verify fixes
            $remainingIssues = $this->verifyFixes();
            if (!empty($remainingIssues)) {
                throw new Exception("Some case sensitivity issues remain:\n" . implode("\n", $remainingIssues));
            }

            $this->log[] = "\n✅ All fixes completed and verified successfully!";
        } catch (Exception $e) {
            $this->log[] = "\n❌ Error occurred: " . $e->getMessage();
            // Restore from backup
            $this->restoreFromBackup($backupDir);
            $this->log[] = "Restored from backup due to error";
        }

        $this->outputResults();
    }

    private function verifyFixes() {
        $issues = [];
        $finder = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        // Check directory structure
        foreach ($this->correctCases as $old => $new) {
            if (is_dir($this->baseDir . '/' . $old)) {
                $issues[] = "Directory still exists with incorrect case: {$old}";
            }
            if (!is_dir($this->baseDir . '/' . $new)) {
                $issues[] = "Directory missing with correct case: {$new}";
            }
        }

        // Check file contents
        foreach ($finder as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                // Check for incorrect namespace references
                foreach ($this->correctCases as $old => $new) {
                    $oldNs = "StoriesAPI\\" . $old . "\\";
                    if (strpos($content, $oldNs) !== false) {
                        $issues[] = "Found incorrect namespace reference in {$file->getPathname()}: {$oldNs}";
                    }
                }

                // Check for incorrect path references
                if (preg_match_all('/(require|require_once|include|include_once)([^;]*?[\'"]([^\'"]*)[\'"]\s*;)/i', $content, $matches)) {
                    foreach ($matches[3] as $path) {
                        foreach ($this->correctCases as $old => $new) {
                            if (strpos($path, '/' . $old . '/') !== false) {
                                $issues[] = "Found incorrect path reference in {$file->getPathname()}: {$path}";
                            }
                        }
                    }
                }
            }
        }

        return $issues;
    }

    private function createBackup() {
        $backupDir = dirname($this->baseDir) . '/api_backup_' . date('Y-m-d_H-i-s');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $this->recursiveCopy($this->baseDir, $backupDir);
        return $backupDir;
    }

    private function restoreFromBackup($backupDir) {
        if (is_dir($backupDir)) {
            $this->recursiveDelete($this->baseDir);
            $this->recursiveCopy($backupDir, $this->baseDir);
        }
    }

    private function removeDuplicateDirectories() {
        foreach ($this->correctCases as $old => $new) {
            $wrongCase = $this->baseDir . '/' . $old;
            $rightCase = $this->baseDir . '/' . $new;
            
            if (is_dir($wrongCase) && is_dir($rightCase) && strtolower($wrongCase) === strtolower($rightCase)) {
                $this->log[] = "Found duplicate directories: $wrongCase and $rightCase";
                
                // Move files from wrong case to right case
                $this->mergeDirs($wrongCase, $rightCase);
                $this->recursiveDelete($wrongCase);
                $this->log[] = "Merged and removed duplicate directory: $wrongCase";
            }
        }
    }

    private function fixDirectoryCases() {
        foreach ($this->correctCases as $old => $new) {
            $oldPath = $this->baseDir . '/' . $old;
            $newPath = $this->baseDir . '/' . $new;
            
            if (is_dir($oldPath) && strtolower($old) === strtolower($new) && $old !== $new) {
                rename($oldPath, $newPath);
                $this->log[] = "Renamed directory: $old → $new";
            }
        }
    }

    private function removeBackupFiles() {
        $finder = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($finder as $file) {
            if ($file->isFile()) {
                $filename = $file->getFilename();
                foreach ($this->backupPatterns as $pattern) {
                    if (fnmatch($pattern, $filename)) {
                        unlink($file->getPathname());
                        $this->log[] = "Removed backup file: " . $file->getPathname();
                        break;
                    }
                }
            }
        }
    }

    private function updateCodeReferences() {
        $finder = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($finder as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $modified = false;
                
                // Update namespace references
                foreach ($this->correctCases as $old => $new) {
                    // Fix namespace references (e.g., StoriesAPI\core\)
                    $oldNs = "StoriesAPI\\" . $old . "\\";
                    $newNs = "StoriesAPI\\" . $new . "\\";
                    if (strpos($content, $oldNs) !== false) {
                        $content = str_replace($oldNs, $newNs, $content);
                        $modified = true;
                    }

                    // Fix file path references in require/include statements
                    $patterns = [
                        // require/include statements
                        "/(require|require_once|include|include_once)\s*['\"].*?\/$old\/.*?['\"];/i",
                        // __DIR__ references
                        "/__DIR__\s*\.\s*['\"].*?\/$old\/.*?['\"]/i"
                    ];

                    foreach ($patterns as $pattern) {
                        $content = preg_replace_callback(
                            $pattern,
                            function($matches) use ($old, $new) {
                                return str_replace("/$old/", "/$new/", $matches[0]);
                            },
                            $content,
                            -1,
                            $count
                        );
                        if ($count > 0) {
                            $modified = true;
                        }
                    }

                    // Fix other file path references
                    $pathPatterns = [
                        // Absolute paths
                        ["'" . DIRECTORY_SEPARATOR . $old . DIRECTORY_SEPARATOR, "'" . DIRECTORY_SEPARATOR . $new . DIRECTORY_SEPARATOR],
                        ['"' . DIRECTORY_SEPARATOR . $old . DIRECTORY_SEPARATOR, '"' . DIRECTORY_SEPARATOR . $new . DIRECTORY_SEPARATOR],
                        // Relative paths
                        ["'" . $old . "/", "'" . $new . "/"],
                        ['"' . $old . "/", '"' . $new . "/"],
                        // Mixed paths
                        ["'" . $old . "\\", "'" . $new . "\\"],
                        ['"' . $old . "\\", '"' . $new . "\\"]
                    ];

                    foreach ($pathPatterns as [$oldPath, $newPath]) {
                        if (strpos($content, $oldPath) !== false) {
                            $content = str_replace($oldPath, $newPath, $content);
                            $modified = true;
                        }
                    }
                }
                
                if ($modified) {
                    file_put_contents($file->getPathname(), $content);
                    $this->log[] = "Updated references in: " . $file->getPathname();
                }
            }
        }
    }

    private function installStrictAutoloader() {
        $autoloaderContent = <<<'PHP'
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
    
    throw new Exception(
        "Class not found or case mismatch: $class\n" .
        "Expected path: $file"
    );
});
PHP;

        file_put_contents($this->baseDir . '/autoload.php', $autoloaderContent);
        $this->log[] = "Installed strict PSR-4 autoloader";
    }

    private function addPreventionMeasures() {
        // Add .gitignore rules
        $gitignoreContent = <<<'GITIGNORE'
# Prevent backup files
*.bak*
*.orig
*.old
*.tmp
*copy*
*backup*
GITIGNORE;

        file_put_contents($this->baseDir . '/.gitignore', $gitignoreContent, FILE_APPEND);
        $this->log[] = "Added backup file patterns to .gitignore";

        // Add .htaccess to prevent direct PHP access
        $htaccessContent = <<<'HTACCESS'
# Prevent direct access to PHP files
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow only specific files
<Files "index.php">
    Order Allow,Deny
    Allow from all
</Files>

<Files "api-status.php">
    Order Allow,Deny
    Allow from all
</Files>
HTACCESS;

        foreach ($this->correctCases as $dir) {
            file_put_contents($this->baseDir . '/' . $dir . '/.htaccess', $htaccessContent);
            $this->log[] = "Created .htaccess in $dir directory";
        }
    }

    private function recursiveCopy($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        while (($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function recursiveDelete($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveDelete($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    private function mergeDirs($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        while (($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->mergeDirs($src . '/' . $file, $dst . '/' . $file);
                } else {
                    if (!file_exists($dst . '/' . $file)) {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
        }
        closedir($dir);
    }

    private function outputResults() {
        if (php_sapi_name() === 'cli') {
            foreach ($this->log as $line) {
                echo $line . "\n";
            }
        } else {
            echo "<pre>";
            foreach ($this->log as $line) {
                echo htmlspecialchars($line) . "\n";
            }
            echo "</pre>";
        }
    }
}

// Run the fixer
$fixer = new CaseSensitivityFixer();
$fixer->fix();