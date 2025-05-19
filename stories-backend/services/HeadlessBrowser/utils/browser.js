/**
 * Browser utility for managing Puppeteer browser instances
 */
const puppeteer = require('puppeteer');
const config = require('../config/default');
const logger = require('./logger');
const fs = require('fs');
const path = require('path');

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
      logger.info('Launching new browser instance');
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
          ],
        });

        // Handle browser disconnection
        browserInstance.on('disconnected', () => {
          logger.warn('Browser disconnected');
          browserInstance = null;
        });

        logger.info('Browser instance launched successfully');
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
  }
};
