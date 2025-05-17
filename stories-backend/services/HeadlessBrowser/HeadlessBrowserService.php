<?php
/**
 * Headless Browser Service
 *
 * Provides a service for interacting with web pages using a headless browser.
 * This service uses PHP WebDriver to control Chrome/Chromium in headless mode.
 */

namespace Services\HeadlessBrowser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\Exception\WebDriverException;
use Symfony\Component\Process\Process;

class HeadlessBrowserService
{
    /** @var string Path to Chrome/Chromium executable */
    protected string $chromeBinary;

    /** @var string Chrome driver server URL */
    protected string $serverUrl = 'http://localhost:9515';

    /** @var RemoteWebDriver|null WebDriver instance */
    protected ?RemoteWebDriver $driver = null;

    /** @var Process|null ChromeDriver process */
    protected ?Process $chromeDriverProcess = null;

    /** @var string Path to debug folder */
    protected string $debugDir;

    /** @var array User agents to rotate through */
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
    ];

    /**
     * Constructor
     *
     * @param string|null $chromeBinary Path to Chrome/Chromium executable
     * @param string|null $debugDir Path to debug directory
     */
    public function __construct(?string $chromeBinary = null, ?string $debugDir = null)
    {
        // Check if required classes exist
        if (!class_exists('Facebook\\WebDriver\\Chrome\\ChromeOptions')) {
            throw new \RuntimeException(
                "PHP WebDriver not installed. Please run 'composer require php-webdriver/webdriver'."
            );
        }

        // Find Chrome binary if not provided
        $this->chromeBinary = $chromeBinary ?? $this->findChromeBinary();

        // Set debug directory
        $this->debugDir = $debugDir ?? __DIR__ . '/debug';
        if (!is_dir($this->debugDir)) {
            mkdir($this->debugDir, 0755, true);
        }

        // Log initialization
        $this->logToFile('browser-log.txt', "🔧 Initializing HeadlessBrowserService");
        $this->logToFile('browser-log.txt', "🔧 Chrome binary: {$this->chromeBinary}");
    }

    /**
     * Destructor - ensure browser is closed
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Initialize the browser
     *
     * @param array $options Additional browser options
     * @return bool True if initialization was successful
     */
    public function initialize(array $options = []): bool
    {
        try {
            // Start ChromeDriver if not already running
            $this->startChromeDriver();

            // Set up Chrome options
            $chromeOptions = new ChromeOptions();

            // Default options
            $defaultOptions = [
                '--headless',
                '--disable-gpu',
                '--window-size=1920,1080',
                '--disable-dev-shm-usage',
                '--no-sandbox',
                '--disable-web-security',
                '--disable-features=IsolateOrigins,site-per-process',
                '--disable-blink-features=AutomationControlled', // Hide automation
                '--disable-infobars',
                '--disable-notifications',
                '--disable-extensions',
                '--disable-translate',
                '--disable-sync',
                '--disable-default-apps',
                '--disable-background-networking',
                '--disable-background-timer-throttling',
                '--disable-client-side-phishing-detection',
                '--disable-hang-monitor',
                '--disable-prompt-on-repost',
                '--disable-breakpad',
                '--disable-domain-reliability',
                '--disable-component-update',
                '--metrics-recording-only',
                '--mute-audio',
                '--no-first-run',
                '--no-default-browser-check',
                '--no-pings',
                '--password-store=basic',
                '--use-mock-keychain',
                '--lang=en-US,en;q=0.9',
                '--user-agent=' . $this->getRandomUserAgent()
            ];

            // Merge with custom options
            $browserOptions = array_merge($defaultOptions, $options['browser_options'] ?? []);
            $chromeOptions->addArguments($browserOptions);

            // Add experimental options to avoid detection
            $chromeOptions->setExperimentalOption('excludeSwitches', ['enable-automation']);
            $chromeOptions->setExperimentalOption('useAutomationExtension', false);

            // Set preferences to avoid detection
            $prefs = [
                'profile.default_content_setting_values.notifications' => 2,
                'credentials_enable_service' => false,
                'profile.password_manager_enabled' => false,
                'webrtc.ip_handling_policy' => 'disable_non_proxied_udp',
                'webrtc.multiple_routes_enabled' => false,
                'webrtc.nonproxied_udp_enabled' => false
            ];
            $chromeOptions->setExperimentalOption('prefs', $prefs);

            // Set up capabilities
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

            // Create WebDriver instance
            $this->driver = RemoteWebDriver::create(
                $this->serverUrl,
                $capabilities,
                60000,  // Connection timeout in milliseconds
                60000   // Request timeout in milliseconds
            );

            // Set window size
            $this->driver->manage()->window()->setSize(
                new WebDriverDimension(1920, 1080)
            );

            $this->logToFile('browser-log.txt', "✅ Browser initialized successfully");
            return true;
        } catch (\Exception $e) {
            $this->logToFile('browser-log.txt', "❌ Browser initialization failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Navigate to a URL
     *
     * @param string $url The URL to navigate to
     * @param int $timeout Timeout in seconds
     * @return bool True if navigation was successful
     */
    public function navigateTo(string $url, int $timeout = 30): bool
    {
        if (!$this->driver) {
            if (!$this->initialize()) {
                return false;
            }
        }

        try {
            $this->logToFile('browser-log.txt', "🌐 Navigating to: {$url}");
            $this->driver->get($url);

            // Wait for page to load
            $this->driver->wait($timeout, 500)->until(
                WebDriverExpectedCondition::jsReturnsValue('return document.readyState === "complete";')
            );

            // Take screenshot for debugging
            $this->takeScreenshot("navigation_" . md5($url) . ".png");

            // Save page source
            $this->savePageSource("page_" . md5($url) . ".html");

            return true;
        } catch (\Exception $e) {
            $this->logToFile('browser-log.txt', "❌ Navigation failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get the page source
     *
     * @return string The page source HTML
     */
    public function getPageSource(): string
    {
        if (!$this->driver) {
            return '';
        }

        try {
            return $this->driver->getPageSource();
        } catch (\Exception $e) {
            $this->logToFile('browser-log.txt', "❌ Failed to get page source: {$e->getMessage()}");
            return '';
        }
    }

    /**
     * Check if the current page contains a CAPTCHA
     *
     * @return bool True if CAPTCHA is detected
     */
    public function hasCaptcha(): bool
    {
        if (!$this->driver) {
            return false;
        }

        $pageSource = $this->driver->getPageSource();

        // Common CAPTCHA indicators
        $captchaPatterns = [
            'captcha',
            'robot check',
            'human verification',
            'security challenge',
            'verify you are human',
            'bot check',
            'prove you\'re not a robot',
            'type the characters',
            'enter the characters',
            'solve this puzzle'
        ];

        foreach ($captchaPatterns as $pattern) {
            if (stripos($pageSource, $pattern) !== false) {
                $this->logToFile('browser-log.txt', "⚠️ CAPTCHA detected: {$pattern}");
                return true;
            }
        }

        return false;
    }

    /**
     * Check if redirected to a login page
     *
     * @return bool True if login page is detected
     */
    public function isLoginPage(): bool
    {
        if (!$this->driver) {
            return false;
        }

        $pageSource = $this->driver->getPageSource();
        $currentUrl = $this->driver->getCurrentURL();

        // Login page indicators
        $loginPatterns = [
            'sign in',
            'sign-in',
            'login',
            'log in',
            'password',
            'email address',
            'username',
            'authentication'
        ];

        // Check URL for login indicators
        if (stripos($currentUrl, 'signin') !== false ||
            stripos($currentUrl, 'login') !== false ||
            stripos($currentUrl, 'auth') !== false) {
            $this->logToFile('browser-log.txt', "⚠️ Login page detected in URL: {$currentUrl}");
            return true;
        }

        // Check content for login indicators
        foreach ($loginPatterns as $pattern) {
            if (stripos($pageSource, $pattern) !== false) {
                // Additional check to avoid false positives
                try {
                    $elements = $this->driver->findElements(WebDriverBy::xpath(
                        "//input[@type='password'] | //form[contains(@action, 'signin') or contains(@action, 'login')]"
                    ));

                    if (count($elements) > 0) {
                        $this->logToFile('browser-log.txt', "⚠️ Login page detected: {$pattern}");
                        return true;
                    }
                } catch (\Exception $e) {
                    // Ignore exceptions during detection
                }
            }
        }

        return false;
    }

    /**
     * Close the browser and clean up resources
     */
    public function close(): void
    {
        if ($this->driver) {
            try {
                $this->driver->quit();
            } catch (\Exception $e) {
                // Ignore exceptions during cleanup
            }
            $this->driver = null;
        }

        if ($this->chromeDriverProcess) {
            $this->chromeDriverProcess->stop();
            $this->chromeDriverProcess = null;
        }

        $this->logToFile('browser-log.txt', "🔧 Browser closed");
    }

    /**
     * Take a screenshot
     *
     * @param string $filename Filename for the screenshot
     * @return bool True if successful
     */
    public function takeScreenshot(string $filename): bool
    {
        if (!$this->driver) {
            return false;
        }

        try {
            $screenshotPath = "{$this->debugDir}/{$filename}";
            $this->driver->takeScreenshot($screenshotPath);
            $this->logToFile('browser-log.txt', "📸 Screenshot saved: {$filename}");
            return true;
        } catch (\Exception $e) {
            $this->logToFile('browser-log.txt', "❌ Screenshot failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Save the current page source to a file
     *
     * @param string $filename Filename for the page source
     * @return bool True if successful
     */
    public function savePageSource(string $filename): bool
    {
        if (!$this->driver) {
            return false;
        }

        try {
            $sourcePath = "{$this->debugDir}/{$filename}";
            file_put_contents($sourcePath, $this->driver->getPageSource());
            $this->logToFile('browser-log.txt', "💾 Page source saved: {$filename}");
            return true;
        } catch (\Exception $e) {
            $this->logToFile('browser-log.txt', "❌ Saving page source failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Find Chrome binary path
     *
     * @return string Path to Chrome binary
     */
    protected function findChromeBinary(): string
    {
        // Common Chrome/Chromium paths
        $possiblePaths = [
            // macOS
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            // Linux
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            // Windows
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Default to 'chrome' and hope it's in the PATH
        return 'chrome';
    }

    /**
     * Start ChromeDriver process
     */
    protected function startChromeDriver(): void
    {
        // Check if ChromeDriver is already running
        try {
            $ch = curl_init($this->serverUrl . '/status');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->logToFile('browser-log.txt', "✅ ChromeDriver is already running");
                return;
            }
        } catch (\Exception $e) {
            // ChromeDriver is not running, continue to start it
        }

        // Start ChromeDriver
        $chromeDriverBinary = $this->findChromeDriverBinary();

        if (!$chromeDriverBinary) {
            throw new \RuntimeException("ChromeDriver binary not found");
        }

        $this->logToFile('browser-log.txt', "🔧 Starting ChromeDriver: {$chromeDriverBinary}");

        $this->chromeDriverProcess = new Process([$chromeDriverBinary]);
        $this->chromeDriverProcess->start();

        // Wait for ChromeDriver to start
        $startTime = time();
        while (time() - $startTime < 10) {
            try {
                $ch = curl_init($this->serverUrl . '/status');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $this->logToFile('browser-log.txt', "✅ ChromeDriver started successfully");
                    return;
                }
            } catch (\Exception $e) {
                // Ignore and retry
            }

            sleep(1);
        }

        throw new \RuntimeException("Failed to start ChromeDriver");
    }

    /**
     * Find ChromeDriver binary path
     *
     * @return string|null Path to ChromeDriver binary or null if not found
     */
    protected function findChromeDriverBinary(): ?string
    {
        // Common ChromeDriver paths
        $possiblePaths = [
            // Local project
            __DIR__ . '/../../bin/chromedriver',
            // macOS
            '/usr/local/bin/chromedriver',
            // Linux
            '/usr/bin/chromedriver',
            // Windows
            'C:\\chromedriver.exe',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Check if chromedriver is in PATH
        $process = new Process(['which', 'chromedriver']);
        $process->run();
        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return null;
    }

    /**
     * Get a random user agent
     *
     * @return string Random user agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Log message to file
     *
     * @param string $filename Log filename
     * @param string $message Message to log
     */
    protected function logToFile(string $filename, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logPath = "{$this->debugDir}/{$filename}";
        file_put_contents(
            $logPath,
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND
        );
    }
}
