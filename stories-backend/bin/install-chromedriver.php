<?php
/**
 * ChromeDriver Installer
 *
 * This script automatically downloads and installs the appropriate ChromeDriver
 * version for the system's Chrome/Chromium browser.
 *
 * Usage:
 * php stories-backend/bin/install-chromedriver.php
 */

// Configuration
$binDir = __DIR__;
$chromeDriverDir = $binDir;
$debugDir = __DIR__ . '/../services/HeadlessBrowser/debug';

// Create debug directory if it doesn't exist
if (!is_dir($debugDir)) {
    mkdir($debugDir, 0755, true);
}

// Log file
$logFile = $debugDir . '/chromedriver-install.log';

/**
 * Log a message to both console and log file
 *
 * @param string $message Message to log
 */
function log_message(string $message): void {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    echo $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Get Chrome/Chromium version
 *
 * @return string|null Chrome version or null if not found
 */
function get_chrome_version(): ?string {
    $os = strtolower(PHP_OS);
    $command = '';
    
    if (strpos($os, 'darwin') !== false) {
        // macOS
        $possiblePaths = [
            '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium'
        ];
        
        foreach ($possiblePaths as $path) {
            $command = "{$path} --version 2>/dev/null";
            $output = shell_exec($command);
            
            if ($output) {
                break;
            }
        }
    } elseif (strpos($os, 'win') !== false) {
        // Windows
        $command = 'reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version 2>nul';
        $output = shell_exec($command);
        
        if (!$output) {
            $command = 'reg query "HKLM\SOFTWARE\Wow6432Node\Microsoft\Windows\CurrentVersion\Uninstall\Google Chrome" /v version 2>nul';
            $output = shell_exec($command);
        }
    } else {
        // Linux
        $possibleCommands = [
            'google-chrome --version',
            'chromium --version',
            'chromium-browser --version'
        ];
        
        foreach ($possibleCommands as $cmd) {
            $output = shell_exec("{$cmd} 2>/dev/null");
            
            if ($output) {
                $command = $cmd;
                break;
            }
        }
    }
    
    if (empty($output)) {
        return null;
    }
    
    // Extract version number
    if (preg_match('/(\d+\.\d+\.\d+(\.\d+)?)/', $output, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Get ChromeDriver version for Chrome version
 *
 * @param string $chromeVersion Chrome version
 * @return string ChromeDriver version
 */
function get_chromedriver_version(string $chromeVersion): string {
    // Extract major version
    $majorVersion = explode('.', $chromeVersion)[0];
    
    // Get ChromeDriver version from Google API
    $url = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE_{$majorVersion}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $version = curl_exec($ch);
    curl_close($ch);
    
    if (!$version) {
        // Fallback to latest version
        $url = "https://chromedriver.storage.googleapis.com/LATEST_RELEASE";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $version = curl_exec($ch);
        curl_close($ch);
    }
    
    return trim($version);
}

/**
 * Download and install ChromeDriver
 *
 * @param string $version ChromeDriver version
 * @return bool True if successful
 */
function install_chromedriver(string $version): bool {
    global $chromeDriverDir;
    
    $os = strtolower(PHP_OS);
    $platform = '';
    
    if (strpos($os, 'darwin') !== false) {
        // macOS
        if (php_uname('m') === 'arm64') {
            $platform = 'mac_arm64';
        } else {
            $platform = 'mac64';
        }
    } elseif (strpos($os, 'win') !== false) {
        // Windows
        $platform = 'win32';
    } else {
        // Linux
        $platform = php_uname('m') === 'x86_64' ? 'linux64' : 'linux32';
    }
    
    $downloadUrl = "https://chromedriver.storage.googleapis.com/{$version}/chromedriver_{$platform}.zip";
    log_message("Downloading ChromeDriver from: {$downloadUrl}");
    
    // Download zip file
    $zipFile = tempnam(sys_get_temp_dir(), 'chromedriver');
    $ch = curl_init($downloadUrl);
    $fp = fopen($zipFile, 'w');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    if (!$success) {
        log_message("Failed to download ChromeDriver");
        return false;
    }
    
    // Extract zip file
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        log_message("Failed to open zip file");
        unlink($zipFile);
        return false;
    }
    
    // Create temporary directory for extraction
    $tempDir = sys_get_temp_dir() . '/chromedriver_' . uniqid();
    mkdir($tempDir);
    
    $zip->extractTo($tempDir);
    $zip->close();
    unlink($zipFile);
    
    // Move ChromeDriver to bin directory
    $chromeDriverBinary = $tempDir . '/chromedriver' . (strpos($os, 'win') !== false ? '.exe' : '');
    $targetPath = $chromeDriverDir . '/chromedriver' . (strpos($os, 'win') !== false ? '.exe' : '');
    
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
    
    if (!rename($chromeDriverBinary, $targetPath)) {
        log_message("Failed to move ChromeDriver to {$targetPath}");
        return false;
    }
    
    // Make executable
    chmod($targetPath, 0755);
    
    // Clean up
    rmdir($tempDir);
    
    log_message("ChromeDriver {$version} installed successfully at {$targetPath}");
    return true;
}

// Main execution
log_message("ChromeDriver Installer");
log_message("-------------------");

// Get Chrome version
$chromeVersion = get_chrome_version();
if (!$chromeVersion) {
    log_message("Error: Could not detect Chrome/Chromium version");
    exit(1);
}

log_message("Detected Chrome/Chromium version: {$chromeVersion}");

// Get compatible ChromeDriver version
$chromeDriverVersion = get_chromedriver_version($chromeVersion);
log_message("Compatible ChromeDriver version: {$chromeDriverVersion}");

// Install ChromeDriver
if (install_chromedriver($chromeDriverVersion)) {
    log_message("ChromeDriver installation completed successfully");
} else {
    log_message("Error: ChromeDriver installation failed");
    exit(1);
}

exit(0);
