# Amazon Scraper Implementation Plan

This document outlines specific code changes to enhance the Amazon scraper to match the effectiveness of the Goodreads scraper.

## 1. Update User Agents

**File**: `stories-backend/services/HeadlessBrowser/config/default.js`

```javascript
scraping: {
  // Other config...
  userAgents: [
    // Desktop browsers
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    // Mobile browsers
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 10; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
  ]
}
```

## 2. Enhance Browser Fingerprinting Protection

**File**: `stories-backend/services/HeadlessBrowser/utils/browser.js`

Install required packages:
```bash
npm install puppeteer-extra puppeteer-extra-plugin-stealth
```

Update browser.js:
```javascript
// Replace:
const puppeteer = require('puppeteer');

// With:
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
puppeteer.use(StealthPlugin());
```

Update the browser launch options:
```javascript
browserInstance = await puppeteer.launch({
  headless: config.browser.headless,
  userDataDir: userDataDir,
  defaultViewport: config.browser.defaultViewport,
  args: [
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
    '--disable-accelerated-2d-canvas',
    '--disable-gpu',
    '--window-size=1920x1080',
    '--disable-features=IsolateOrigins,site-per-process',
    '--disable-web-security',
    '--disable-features=site-per-process',
    '--disable-blink-features=AutomationControlled',
  ],
  ignoreHTTPSErrors: true,
});

// Add after browser launch:
const defaultPage = await browserInstance.newPage();
await defaultPage.evaluateOnNewDocument(() => {
  // Overwrite the 'navigator.webdriver' property to prevent detection
  Object.defineProperty(navigator, 'webdriver', {
    get: () => false,
  });
  
  // Overwrite the 'plugins' property to include fake plugins
  Object.defineProperty(navigator, 'plugins', {
    get: () => [1, 2, 3, 4, 5],
  });
  
  // Overwrite the 'languages' property
  Object.defineProperty(navigator, 'languages', {
    get: () => ['en-US', 'en'],
  });
});
await defaultPage.close();
```

## 3. Implement Cookie Management

**File**: `stories-backend/services/HeadlessBrowser/utils/browser.js`

Add a new function to save and load cookies:
```javascript
/**
 * Save cookies for a specific domain
 * @param {string} domain - Domain to save cookies for
 * @param {Array} cookies - Cookies to save
 */
saveCookies: async (domain, cookies) => {
  const cookiesDir = path.join(__dirname, '../data/cookies');
  if (!fs.existsSync(cookiesDir)) {
    fs.mkdirSync(cookiesDir, { recursive: true });
  }
  
  const cookieFile = path.join(cookiesDir, `${domain.replace(/\./g, '_')}.json`);
  fs.writeFileSync(cookieFile, JSON.stringify(cookies, null, 2));
  logger.info(`Saved ${cookies.length} cookies for ${domain}`);
},

/**
 * Load cookies for a specific domain
 * @param {string} domain - Domain to load cookies for
 * @returns {Array} - Cookies for the domain
 */
loadCookies: (domain) => {
  const cookiesDir = path.join(__dirname, '../data/cookies');
  const cookieFile = path.join(cookiesDir, `${domain.replace(/\./g, '_')}.json`);
  
  if (fs.existsSync(cookieFile)) {
    try {
      const cookies = JSON.parse(fs.readFileSync(cookieFile, 'utf8'));
      logger.info(`Loaded ${cookies.length} cookies for ${domain}`);
      return cookies;
    } catch (error) {
      logger.error(`Error loading cookies for ${domain}: ${error.message}`);
    }
  }
  
  return [];
}
```

## 4. Update Amazon Scraper with Enhanced Features

**File**: `stories-backend/services/HeadlessBrowser/scrapers/amazon.js`

Add randomized behavior and cookie management:
```javascript
// Add at the top of the scrapeAmazonReviews function:
const domain = config.sources.amazon.baseUrl.replace('https://', '').replace('http://', '');

// After creating the page:
// Load cookies if available
const cookies = browser.loadCookies(domain);
if (cookies.length > 0) {
  await page.setCookie(...cookies);
}

// Add random delay function
const randomDelay = async () => {
  const delay = Math.floor(Math.random() * 3000) + 1000; // 1-4 seconds
  logger.info(`Adding random delay of ${delay}ms`);
  await page.waitForTimeout(delay);
};

// Add human-like scrolling
const simulateHumanScrolling = async () => {
  await page.evaluate(() => {
    const scrollHeight = document.body.scrollHeight;
    const viewportHeight = window.innerHeight;
    const scrollSteps = Math.floor(scrollHeight / viewportHeight) + 1;
    
    return new Promise((resolve) => {
      let currentStep = 0;
      
      const scroll = () => {
        if (currentStep >= scrollSteps) {
          resolve();
          return;
        }
        
        const scrollAmount = Math.floor(Math.random() * 100) + viewportHeight * currentStep;
        window.scrollTo(0, scrollAmount);
        currentStep++;
        
        setTimeout(scroll, Math.floor(Math.random() * 500) + 500);
      };
      
      scroll();
    });
  });
};

// Add after navigating to product page:
await randomDelay();
await simulateHumanScrolling();

// Add before closing the page:
// Save cookies for future use
const currentCookies = await page.cookies();
await browser.saveCookies(domain, currentCookies);
```

## 5. Implement Mobile Site Fallback

Add a new function to the Amazon scraper:
```javascript
/**
 * Scrape reviews from Amazon mobile site
 * @param {string} asin - Amazon ASIN
 * @param {number} limit - Maximum number of reviews to return
 * @returns {Promise<Array>} - Array of reviews
 */
async function scrapeMobileAmazonReviews(asin, limit = 50) {
  logger.info(`Starting Amazon mobile scraping for ASIN: ${asin}`);
  
  const page = await browser.getNewPage();
  let reviews = [];
  
  try {
    // Set mobile user agent
    const mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
    await page.setUserAgent(mobileUserAgent);
    
    // Set mobile viewport
    await page.setViewport({
      width: 375,
      height: 812,
      isMobile: true,
      hasTouch: true
    });
    
    // Navigate to mobile reviews page
    const mobileReviewsUrl = `https://m.${config.sources.amazon.baseUrl.replace('www.', '')}/gp/aw/cr/asin/${asin}`;
    logger.info(`Navigating to mobile reviews page: ${mobileReviewsUrl}`);
    
    await page.goto(mobileReviewsUrl, { waitUntil: 'networkidle2', timeout: 30000 });
    
    // Check for login page or CAPTCHA
    if (await isLoginPage(page) || await hasCaptcha(page)) {
      logger.warn('Detected login page or CAPTCHA on mobile page');
      await browser.takeScreenshot(page, `amazon-mobile-login-captcha-${asin}`);
      return [];
    }
    
    // Extract reviews using mobile-specific selectors
    // ... (implementation details)
    
    return reviews;
  } catch (error) {
    logger.error(`Error scraping Amazon mobile reviews: ${error.message}`);
    return [];
  } finally {
    await page.close();
  }
}

// Modify the main scrapeAmazonReviews function to use mobile as fallback:
// Add at the end of the catch block:
logger.info('Attempting to scrape from mobile site as fallback');
const mobileReviews = await scrapeMobileAmazonReviews(asin, limit);
if (mobileReviews.length > 0) {
  logger.info(`Successfully scraped ${mobileReviews.length} reviews from mobile site`);
  return {
    source: 'scrape_mobile',
    total: mobileReviews.length,
    reviews: mobileReviews
  };
}
```

## 6. Update Server Configuration

**File**: `stories-backend/services/HeadlessBrowser/server.js`

Add support for the continueFromLast parameter in the Amazon endpoint:
```javascript
// Update the Amazon scraper endpoint:
app.get('/scrape/amazon', authenticateApiKey, rateLimiterMiddleware, async (req, res) => {
  try {
    // Extract parameters from query string, ensuring proper type conversion
    const asin = req.query.asin;
    const limit = parseInt(req.query.limit || '50', 10);
    const force = req.query.force === '1' || req.query.force === 'true';
    const continueFromLast = req.query.continueFromLast === '1' || req.query.continueFromLast === 'true';
    const maxPages = parseInt(req.query.maxPages || '20', 10);

    // Log all parameters for debugging
    logger.info(`Amazon scraper parameters:
      - asin: ${asin}
      - limit: ${limit}
      - force: ${force}
      - continueFromLast: ${continueFromLast}
      - maxPages: ${maxPages}
    `);

    // ... rest of the function

    const reviews = await amazon.scrapeAmazonReviews(asin, parseInt(limit), {
      continueFromLast,
      maxPages
    });

    res.status(200).json(reviews);
  } catch (error) {
    logger.error(`Error scraping Amazon reviews: ${error.message}`);
    res.status(500).json({ error: error.message });
  }
});
```

## Implementation Timeline

1. **Day 1**: Update user agents and implement browser fingerprinting protection
2. **Day 2**: Implement cookie management and add randomized behavior
3. **Day 3**: Implement mobile site fallback and update server configuration
4. **Day 4**: Testing and debugging
5. **Day 5**: Deployment and monitoring
