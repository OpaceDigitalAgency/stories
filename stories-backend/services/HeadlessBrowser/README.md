# Headless Browser Service for Amazon Reviews

This service provides a headless browser implementation to improve Amazon review scraping by bypassing anti-bot measures. It uses PHP WebDriver to control Chrome/Chromium in headless mode.

## Server Requirements

To use the headless browser functionality, your server needs:

1. **PHP 7.4+** with the following extensions:
   - curl
   - json
   - zip
   - dom

2. **Composer** for dependency management

3. **Chrome/Chromium** browser installed on the server

4. **ChromeDriver** matching your Chrome version

## Installation

### 1. Install PHP Dependencies

```bash
cd stories-backend
composer require php-webdriver/webdriver
composer require symfony/process
composer require symfony/dom-crawler
composer require symfony/css-selector
```

### 2. Install Chrome/Chromium (if not already installed)

#### Ubuntu/Debian:
```bash
sudo apt update
sudo apt install chromium-browser
```

#### CentOS/RHEL:
```bash
sudo yum install chromium
```

### 3. Install ChromeDriver

The ChromeDriver version must match your Chrome/Chromium version. You can use the provided installer script:

```bash
php stories-backend/bin/install-chromedriver.php
```

Or install manually:

1. Check your Chrome version: `chromium-browser --version` or `google-chrome --version`
2. Download the matching ChromeDriver from https://chromedriver.chromium.org/downloads
3. Extract and place in `stories-backend/bin/`
4. Make executable: `chmod +x stories-backend/bin/chromedriver`

## How It Works

The headless browser service:

1. Launches Chrome in headless mode via ChromeDriver
2. Navigates to Amazon review pages with full JavaScript support
3. Handles cookies and sessions more effectively than raw HTTP requests
4. Detects and logs CAPTCHA and login redirects
5. Takes screenshots for debugging
6. Extracts review data using the existing parsing logic

## Fallback Mechanism

If the headless browser approach fails for any reason (missing dependencies, CAPTCHA detection, etc.), the system will automatically fall back to the original HTTP client method. This ensures that the review fetching process continues to work even if the headless browser is not available.

## Debugging

Debug information is stored in:
- `services/HeadlessBrowser/debug/browser-log.txt` - Browser operation logs
- `services/ReviewFetcher/debug/headless-log.txt` - Amazon fetcher logs
- `services/ReviewFetcher/debug/amazon-*.html` - Saved HTML pages
- `services/HeadlessBrowser/debug/*.png` - Screenshots

## Troubleshooting

### ChromeDriver Not Found

If ChromeDriver is not found, you'll see an error in the logs. Make sure:
1. ChromeDriver is installed in `stories-backend/bin/`
2. It's executable (`chmod +x`)
3. It matches your Chrome/Chromium version

### CAPTCHA/Login Detection

If you see "CAPTCHA detected" or "Redirected to login page" in the logs:
1. Check the screenshots in the debug directory
2. Amazon may be blocking your server's IP
3. Consider using a proxy service

### PHP WebDriver Not Installed

If you see "PHP WebDriver not installed" in the logs:
1. Run `composer require php-webdriver/webdriver`
2. Check that the class can be loaded

## Maintenance

The headless browser approach may need periodic updates as Amazon changes their site structure or enhances their anti-bot measures. Monitor the logs and update the implementation as needed.

## Disabling Headless Browser

If you need to disable the headless browser approach and use only the original HTTP client method:

1. Edit `stories-backend/services/ReviewFetcher/ReviewFetcherFactory.php`
2. Replace the Amazon case with:
   ```php
   case 'amazon':
       $fetcher = new AmazonReviewFetcher($this->db, $sourceId);
       break;
   ```
