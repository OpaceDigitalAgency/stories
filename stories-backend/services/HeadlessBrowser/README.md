# Headless Browser Review Scraper

A Node.js Puppeteer-based service for scraping book reviews from Amazon and Goodreads.

## Overview

This service provides a solution for scraping book reviews from websites that implement anti-scraping measures like login walls and JavaScript-based pagination. It uses Puppeteer to automate a full browser environment, allowing it to interact with dynamic elements and handle complex page structures.

## Features

- Full browser automation with Puppeteer
- API endpoints for scraping Amazon and Goodreads reviews
- Caching system to minimize scraping frequency
- Robust error handling and logging
- Rate limiting to prevent abuse
- API key authentication for security

## Server Requirements

To use the headless browser functionality, your server needs:

1. **Node.js 18.x+**

2. **npm** for dependency management

3. **Chrome/Chromium** dependencies (installed automatically with Puppeteer)

## Installation

### 1. Install Node.js Dependencies

```bash
cd stories-backend/services/HeadlessBrowser
npm install
```

### 2. Configure the Service

Edit `config/default.js` to set your API key and other configuration options.

### 3. Start the Server

For development:
```bash
npm start
```

For production with PM2:
```bash
npm install -g pm2
pm2 start server.js --name review-scraper
pm2 save
pm2 startup
```

## Production Deployment

This service is deployed on a Hetzner VPS with automatic deployment:

- **Server**: Hetzner Cloud VPS (4GB RAM, 2 vCPU)
- **IP Address**: 37.27.31.107
- **Process Manager**: PM2 (process name: `review-scraper`)
- **Auto-Deploy**: Enabled via git-auto-deploy (listening on port 8080)
- **Last Deployment**: Updated on `date +"%Y-%m-%d %H:%M:%S"` to test webhook again

### Automatic Deployment

When changes are pushed to the main branch, the server automatically:
1. Pulls the latest changes from GitHub
2. Runs `npm install` in the HeadlessBrowser directory
3. Restarts the PM2 process

### API Endpoints

The production API is available at:
- Base URL: `http://37.27.31.107:3000`
- Health check: `http://37.27.31.107:3000/health`
- Goodreads scraper: `http://37.27.31.107:3000/scrape/goodreads`
- Amazon scraper: `http://37.27.31.107:3000/scrape/amazon`

**Note**: API key authentication is required for all endpoints except health check.

## How It Works

The headless browser service:

1. Launches Chrome in headless mode via Puppeteer
2. Navigates to Amazon and Goodreads pages with full JavaScript support
3. Handles cookies and sessions more effectively than raw HTTP requests
4. Detects and logs CAPTCHA and login redirects
5. Takes screenshots for debugging
6. Extracts review data using robust parsing logic
7. Caches results to minimize scraping frequency

## API Endpoints

### Health Check

```
GET /health
```

Returns the status of the service.

### Scrape Goodreads Reviews

```
GET /scrape/goodreads?url=<goodreads-url>&limit=<limit>
```

Parameters:
- `url`: The URL of the Goodreads book page
- `limit` (optional): Maximum number of reviews to return (default: 50)

Headers:
- `x-api-key`: Your API key

### Scrape Amazon Reviews

```
GET /scrape/amazon?asin=<asin>&limit=<limit>
```

Parameters:
- `asin`: The Amazon ASIN (product ID)
- `limit` (optional): Maximum number of reviews to return (default: 50)

Headers:
- `x-api-key`: Your API key

### Clear Cache

```
POST /cache/clear
```

Clears expired cache entries.

Headers:
- `x-api-key`: Your API key

## Debugging

Debug information is stored in:
- `logs/combined.log` - All logs
- `logs/error.log` - Error logs only
- `logs/debug/goodreads-*.html` - Saved Goodreads HTML pages
- `logs/debug/amazon-*.html` - Saved Amazon HTML pages
- `logs/screenshots/*.png` - Screenshots taken during errors

## Troubleshooting

### Common Issues

1. **CAPTCHA Detection**: If you're seeing frequent CAPTCHAs, try:
   - Reducing the scraping frequency
   - Using a VPN or proxy
   - Implementing a CAPTCHA solving service

2. **Login Walls**: If you're encountering login walls:
   - The service will still try to extract aggregate ratings
   - Consider implementing cookie persistence between sessions

3. **Performance Issues**: If the service is slow:
   - Increase the `maxConcurrent` setting in config
   - Optimize the cache TTL
   - Use a more powerful server

## Integration with PHP Backend

To integrate this service with the existing PHP backend:

1. Update the `GoodreadsReviewFetcher.php` and `AmazonReviewFetcher.php` classes to call these API endpoints
2. Set the API URL and key in your environment variables
3. Implement fallback to existing methods if the API is unavailable

Example PHP integration:

```php
// In GoodreadsReviewFetcher.php
private function fetchReviewsWithHeadlessBrowser(string $goodreadsUrl, int $limit): array {
    // Use the VPS IP address as the default if environment variable is not set
    $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
    $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'your-secret-api-key-here';

    $url = "{$apiUrl}/scrape/goodreads?url=" . urlencode($goodreadsUrl) . "&limit={$limit}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: {$apiKey}"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        $this->logToFile($this->dbgDir . '/goodreads-log.txt', "❌ Headless browser API error: HTTP {$httpCode}");
        return [];
    }

    $data = json_decode($response, true);
    return $data['reviews'] ?? [];
}

// In AmazonReviewFetcher.php
private function fetchReviewsWithHeadlessBrowser(string $asin, int $limit): array {
    // Use the VPS IP address as the default if environment variable is not set
    $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
    $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'your-secret-api-key-here';

    $url = "{$apiUrl}/scrape/amazon?asin={$asin}&limit={$limit}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: {$apiKey}"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        $this->logToFile($this->dbgDir . '/scrape-log.txt', "❌ Headless browser API error: HTTP {$httpCode}");
        return [];
    }

    $data = json_decode($response, true);
    return $data['reviews'] ?? [];
}
```
