/**
 * Browser utility for managing Puppeteer browser instances
 * Enhanced with stealth plugins to avoid detection
 */
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const AnonymizeUAPlugin = require('puppeteer-extra-plugin-anonymize-ua');
const pageProxy = require('puppeteer-page-proxy');
const config = require('../config/default');
const logger = require('./logger');
const fs = require('fs');
const path = require('path');

// Add plugins
puppeteer.use(StealthPlugin());
puppeteer.use(AnonymizeUAPlugin());

// Ensure browser data directory exists
const userDataDir = path.resolve(config.browser.userDataDir);
if (!fs.existsSync(userDataDir)) {
  fs.mkdirSync(userDataDir, { recursive: true });
}

let browserInstance = null;

module.exports = {
  /**
   * Get a browser instance, creating one if it doesn't exist
   * @returns {Promise<Browser>} Puppeteer browser instance
   */
  getBrowser: async () => {
    if (!browserInstance) {
      logger.info('Launching new browser instance with enhanced stealth features');
      try {
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

        // Handle browser disconnection
        browserInstance.on('disconnected', () => {
          logger.warn('Browser disconnected');
          browserInstance = null;
        });

        // Create a default page and apply additional evasion techniques
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

        logger.info('Browser instance launched successfully with anti-detection measures');
      } catch (error) {
        logger.error(`Error launching browser: ${error.message}`);
        throw error;
      }
    }
    return browserInstance;
  },

  /**
   * Close the browser instance if it exists
   */
  closeBrowser: async () => {
    if (browserInstance) {
      try {
        await browserInstance.close();
        browserInstance = null;
        logger.info('Browser instance closed');
      } catch (error) {
        logger.error(`Error closing browser: ${error.message}`);
        browserInstance = null;
      }
    }
  },

  /**
   * Get a new page with optional user agent
   * @param {string} userAgent - Optional user agent to use
   * @returns {Promise<Page>} Puppeteer page
   */
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

    // Block unnecessary resources to speed up scraping
    await page.setRequestInterception(true);
    page.on('request', request => {
      if (request._interceptionHandled) return;

      const resourceType = request.resourceType();

      try {
        if (['image', 'media', 'font'].includes(resourceType)) {
          request.abort();
        } else {
          request.continue();
        }
      } catch (e) {
        console.warn(`Skipping already handled request: ${request.url()} (${resourceType})`);
      }
    });

    // Add error handling for page errors
    page.on('error', error => {
      logger.error(`Page error: ${error.message}`);
    });

    return page;
  },

  /**
   * Take a screenshot of a page for debugging
   * @param {Page} page - Puppeteer page
   * @param {string} name - Name for the screenshot file
   */
  takeScreenshot: async (page, name) => {
    const screenshotsDir = path.join(__dirname, '../logs/screenshots');
    if (!fs.existsSync(screenshotsDir)) {
      fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `${name}-${timestamp}.png`;
    const filepath = path.join(screenshotsDir, filename);

    await page.screenshot({ path: filepath, fullPage: true });
    logger.info(`Screenshot saved to ${filepath}`);

    return filepath;
  },

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
  },

  /**
   * Simulate human-like behavior on a page
   * @param {Object} page - Puppeteer page
   */
  simulateHumanBehavior: async (page) => {
    // Random scroll
    await page.evaluate(() => {
      const scrollAmount = Math.floor(Math.random() * 100) + 100;
      window.scrollBy(0, scrollAmount);
    });

    // Random delay
    const delay = Math.floor(Math.random() * 2000) + 1000;
    await page.waitForTimeout(delay);

    // Move mouse randomly
    const x = Math.floor(Math.random() * 500);
    const y = Math.floor(Math.random() * 500);
    await page.mouse.move(x, y);

    logger.debug('Simulated human-like behavior on page');
  }
};
