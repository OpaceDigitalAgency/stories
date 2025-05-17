# VPS-Based Review Scraper Implementation Plan

This document provides a comprehensive guide for implementing a VPS-based solution for scraping book reviews from Amazon and Goodreads, addressing the challenges we've faced with existing approaches.

## Background

Our previous attempts to scrape reviews have encountered significant challenges:

1. **Amazon's Anti-Scraping Measures**:
   - Forced login walls
   - CAPTCHA challenges
   - IP blocking
   - Changing HTML structure

2. **Goodreads Pagination Issues**:
   - JavaScript-based "Show more reviews" buttons
   - Limited initial review load (10-30 reviews)
   - Dynamic React-based interface

3. **Netlify Function Limitations**:
   - 250MB size limit (problematic for Puppeteer)
   - Execution time constraints
   - Limited ability to maintain sessions

## VPS Solution Overview

A dedicated VPS running Puppeteer/Playwright offers the most effective solution by providing:

- Full browser automation capabilities
- Persistent sessions between requests
- No serverless function limitations
- Cost-effectiveness compared to commercial scraping services
- Complete control over the environment

## Implementation Steps

### Step 1: VPS Setup

#### Provider Selection

**Recommended: Hetzner Cloud**
- Best balance of performance and cost
- Locations in Europe (Germany, Finland) and US
- Excellent network performance
- Starting at €4.15/month (~$4.50) for 2GB RAM, 1 vCPU, 20GB SSD

**Alternative: DigitalOcean**
- Good performance, slightly more expensive
- More global locations if needed
- Starting at $5/month for 1GB RAM, 1 vCPU, 25GB SSD

#### Server Specifications
- **Recommended**: 4GB RAM, 2 vCPU, 40GB SSD (~$10/month on Hetzner)
- **OS**: Ubuntu 22.04 LTS

#### Initial Server Setup

```bash
# Update system
apt update && apt upgrade -y

# Install essential packages
apt install -y git curl wget unzip build-essential nodejs npm

# Install Node.js 18.x (LTS)
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install PM2 for process management
npm install -g pm2

# Install Chrome dependencies
apt install -y ca-certificates fonts-liberation libappindicator3-1 libasound2 libatk-bridge2.0-0 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release xdg-utils
```

### Step 2: Project Setup

```bash
# Create directory for scraper
mkdir -p /opt/book-scraper
cd /opt/book-scraper

# Initialize project
npm init -y

# Install dependencies
npm install puppeteer express dotenv winston sqlite3 axios cors helmet rate-limiter-flexible

# Create project structure
mkdir -p scrapers utils config logs data
```

### Step 3: Core Implementation

#### Project Structure

```
/opt/book-scraper/
├── config/
│   ├── default.js
│   └── production.js
├── data/
│   └── cache.db
├── logs/
│   ├── access.log
│   └── error.log
├── scrapers/
│   ├── amazon.js
│   └── goodreads.js
├── utils/
│   ├── browser.js
│   ├── cache.js
│   ├── logger.js
│   └── helpers.js
├── server.js
├── package.json
└── README.md
```

#### Configuration (config/default.js)

```javascript
module.exports = {
  server: {
    port: 3000,
    host: 'localhost',
    apiKey: 'your-secret-api-key-here'
  },
  browser: {
    headless: true,
    userDataDir: './data/browser-data',
    defaultViewport: { width: 1920, height: 1080 }
  },
  cache: {
    enabled: true,
    ttl: 7 * 24 * 60 * 60 * 1000, // 7 days in milliseconds
    dbPath: './data/cache.db'
  },
  scraping: {
    maxConcurrent: 2,
    retries: 3,
    timeout: 60000,
    userAgents: [
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
    ]
  },
  sources: {
    goodreads: {
      baseUrl: 'https://www.goodreads.com',
      maxReviews: 100,
      maxPages: 10
    },
    amazon: {
      baseUrl: 'https://www.amazon.co.uk',
      maxReviews: 100,
      maxPages: 10,
      affiliateTag: 'storiesfro0f0-20'
    }
  }
};
```

#### Browser Utility (utils/browser.js)

```javascript
const puppeteer = require('puppeteer');
const config = require('../config/default');
const logger = require('./logger');

let browserInstance = null;

module.exports = {
  getBrowser: async () => {
    if (!browserInstance) {
      logger.info('Launching new browser instance');
      browserInstance = await puppeteer.launch({
        headless: config.browser.headless,
        userDataDir: config.browser.userDataDir,
        defaultViewport: config.browser.defaultViewport,
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--disable-accelerated-2d-canvas',
          '--disable-gpu',
          '--window-size=1920x1080',
        ],
      });
    }
    return browserInstance;
  },
  
  closeBrowser: async () => {
    if (browserInstance) {
      await browserInstance.close();
      browserInstance = null;
      logger.info('Browser instance closed');
    }
  },
  
  getNewPage: async (userAgent = null) => {
    const browser = await module.exports.getBrowser();
    const page = await browser.newPage();
    
    // Set a realistic user agent if provided
    if (userAgent) {
      await page.setUserAgent(userAgent);
    } else {
      const randomUserAgent = config.scraping.userAgents[
        Math.floor(Math.random() * config.scraping.userAgents.length)
      ];
      await page.setUserAgent(randomUserAgent);
    }
    
    // Set viewport
    await page.setViewport(config.browser.defaultViewport);
    
    return page;
  }
};
```

### Step 4: API Server Implementation

The complete implementation details for the server, scrapers, and utilities will be provided in the next sections.

## Integration with PHP Backend

Once the VPS scraper is operational, update the PHP backend to use it:

1. Modify `AmazonReviewFetcher.php` and `GoodreadsReviewFetcher.php` to call the VPS API
2. Implement fallback to existing methods if the VPS API is unavailable
3. Add caching to reduce API calls

## Monitoring and Maintenance

- Set up PM2 for process monitoring and auto-restart
- Implement daily log rotation
- Create a health check endpoint
- Set up automated backups of the cache database
- Implement error alerting via email or Slack

## Security Considerations

- Use API key authentication for all endpoints
- Implement rate limiting to prevent abuse
- Keep the server behind a firewall with only necessary ports open
- Regularly update dependencies for security patches
- Use HTTPS for all API communication
