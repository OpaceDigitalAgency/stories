/**
 * Amazon review scraper
 */
const browser = require('../utils/browser');
const logger = require('../utils/logger');
const cache = require('../utils/cache');
const config = require('../config/default');
const path = require('path');
const fs = require('fs');

// Ensure debug directory exists
const debugDir = path.join(__dirname, '../logs/debug');
if (!fs.existsSync(debugDir)) {
  fs.mkdirSync(debugDir, { recursive: true });
}

/**
 * Parse reviews from HTML using regex
 * @param {string} html - HTML content
 * @param {string} asin - Amazon ASIN
 * @returns {Array} - Array of review objects
 */
function parseReviewsWithRegex(html, asin) {
  const reviews = [];

  // Match review blocks
  const reviewRegex = /<div[^>]*data-hook="review"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/gi;
  let reviewMatch;

  while ((reviewMatch = reviewRegex.exec(html)) !== null) {
    const reviewBlock = reviewMatch[0];

    // Extract reviewer name
    const nameRegex = /<span[^>]*class="a-profile-name"[^>]*>([\s\S]*?)<\/span>/i;
    const nameMatch = reviewBlock.match(nameRegex);
    const reviewerName = nameMatch ? nameMatch[1].trim() : 'Amazon Customer';

    // Extract rating
    const ratingRegex = /<i[^>]*class="[^"]*a-icon-star[^"]*"[^>]*><span[^>]*>([0-9.]+) out of 5 stars<\/span><\/i>/i;
    const ratingMatch = reviewBlock.match(ratingRegex);
    const rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;

    // Extract date
    const dateRegex = /<span[^>]*data-hook="review-date"[^>]*>([\s\S]*?)<\/span>/i;
    const dateMatch = reviewBlock.match(dateRegex);
    const reviewDate = dateMatch ? dateMatch[1].trim() : '';

    // Extract title
    const titleRegex = /<a[^>]*data-hook="review-title"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/a>/i;
    const titleMatch = reviewBlock.match(titleRegex);
    const title = titleMatch ? titleMatch[1].trim() : '';

    // Extract review text
    const textRegex = /<span[^>]*data-hook="review-body"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/span>/i;
    const textMatch = reviewBlock.match(textRegex);
    const reviewText = textMatch ? textMatch[1].trim() : '';

    // Only add reviews with valid ratings
    if (rating !== null) {
      reviews.push({
        source_id: 5, // Amazon source ID
        reviewer_name: reviewerName,
        review_date: reviewDate,
        original_rating: `${rating}/5`,
        rating_value: rating,
        rating_scale: 5,
        rating_normalised: rating / 5, // Normalize to 0-1 scale
        review_text: reviewText,
        metadata: JSON.stringify({
          asin: asin,
          review_title: title,
          review_url: `https://www.amazon.co.uk/product-reviews/${asin}`,
          affiliate_url: `https://www.amazon.co.uk/dp/${asin}?tag=${config.sources.amazon.affiliateTag}`,
        }),
      });
    }
  }

  return reviews;
}

/**
 * Extract aggregate rating from HTML
 * @param {string} html - HTML content
 * @param {string} asin - Amazon ASIN
 * @returns {Object|null} - Aggregate rating object or null if not found
 */
function extractAggregateRating(html, asin) {
  const ratingRegex = /<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([0-9.]+) out of 5 stars<\/span>/i;
  const ratingMatch = html.match(ratingRegex);

  if (!ratingMatch) return null;

  const avg = parseFloat(ratingMatch[1]);

  // Extract number of ratings
  const countRegex = /(\d+(?:,\d+)*) ratings?/i;
  const countMatch = html.match(countRegex);
  const count = countMatch ? parseInt(countMatch[1].replace(/,/g, '')) : 1;

  return {
    source_id: 5, // Amazon source ID
    reviewer_name: 'Amazon Aggregate',
    review_date: new Date().toISOString().split('T')[0],
    original_rating: `${avg}/5`,
    rating_value: avg,
    rating_scale: 5,
    rating_normalised: avg / 5,
    review_text: `Average rating ${avg}/5 based on ${count} ratings on Amazon.`,
    metadata: JSON.stringify({
      asin: asin,
      review_url: `https://www.amazon.co.uk/product-reviews/${asin}`,
      affiliate_url: `https://www.amazon.co.uk/dp/${asin}?tag=${config.sources.amazon.affiliateTag}`,
      is_aggregate: true,
      ratings_count: count,
    }),
  };
}

/**
 * Check if the page is a login page
 * @param {Page} page - Puppeteer page
 * @returns {Promise<boolean>} - Whether the page is a login page
 */
async function isLoginPage(page) {
  return page.evaluate(() => {
    return document.body.innerHTML.includes('Sign-In') ||
           document.body.innerHTML.includes('sign-in') ||
           document.body.innerHTML.includes('Sign in') ||
           document.body.innerHTML.includes('Amazon password') ||
           document.body.innerHTML.includes('ap_password') ||
           document.body.innerHTML.includes('ap_email');
  });
}

/**
 * Check if the page has a CAPTCHA
 * @param {Page} page - Puppeteer page
 * @returns {Promise<boolean>} - Whether the page has a CAPTCHA
 */
async function hasCaptcha(page) {
  return page.evaluate(() => {
    return document.body.innerHTML.includes('captcha') ||
           document.body.innerHTML.includes('robot check') ||
           document.body.innerHTML.includes('verify you\'re not a robot') ||
           document.body.innerHTML.includes('security challenge');
  });
}

/**
 * Scrape reviews from Amazon
 * @param {string} asin - Amazon ASIN
 * @param {number} limit - Maximum number of reviews to return
 * @param {Object} options - Additional options
 * @param {boolean} options.continueFromLast - Whether to continue from the last scrape
 * @param {number} options.maxPages - Maximum number of pages to scrape
 * @returns {Promise<Object>} - Object containing reviews
 */
async function scrapeAmazonReviews(asin, limit = 50, options = {}) {
  logger.info(`Starting Amazon scraping for ASIN: ${asin}`);

  // Extract options
  const continueFromLast = options.continueFromLast || false;
  const maxPages = options.maxPages || config.sources.amazon.maxPages;

  logger.info(`Options: continueFromLast=${continueFromLast}, maxPages=${maxPages}`);

  // Check cache first (unless continueFromLast is true)
  if (!continueFromLast) {
    const cachedData = await cache.get('amazon', asin);
    if (cachedData) {
      logger.info(`Using cached data for Amazon ASIN: ${asin}`);
      return {
        source: 'cache',
        ...cachedData
      };
    }
  }

  // Try mobile site first as it's more reliable
  const mobileReviews = await scrapeMobileAmazonReviews(asin, limit, options);
  if (mobileReviews && mobileReviews.length > 0) {
    logger.info(`Successfully scraped ${mobileReviews.length} reviews from mobile site`);

    // Cache the results
    const dataToCache = {
      total: mobileReviews.length,
      reviews: mobileReviews.slice(0, limit)
    };

    await cache.set('amazon', asin, dataToCache);

    return {
      source: 'scrape_mobile',
      total: mobileReviews.length,
      reviews: mobileReviews.slice(0, limit)
    };
  }

  // If mobile site fails, fall back to desktop site
  logger.info(`Mobile site scraping failed or returned no reviews, falling back to desktop site`);

  const page = await browser.getNewPage();
  let reviews = [];

  try {
    // First try the product page to get aggregate rating
    const productUrl = `https://${config.sources.amazon.baseUrl}/dp/${asin}`;
    logger.info(`Navigating to product page: ${productUrl}`);

    await page.goto(productUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Check for login page or CAPTCHA
    if (await isLoginPage(page)) {
      logger.warn('Detected login page on product page');
      await browser.takeScreenshot(page, `amazon-login-${asin}`);

      // Save the page HTML for debugging
      const html = await page.content();
      fs.writeFileSync(path.join(debugDir, `amazon-${asin}-login-page.html`), html);
    }

    if (await hasCaptcha(page)) {
      logger.warn('Detected CAPTCHA on product page');
      await browser.takeScreenshot(page, `amazon-captcha-${asin}`);

      // Save the page HTML for debugging
      const html = await page.content();
      fs.writeFileSync(path.join(debugDir, `amazon-${asin}-captcha-page.html`), html);
    }

    // Get the product page content
    const productContent = await page.content();

    // Save the product page HTML for debugging
    fs.writeFileSync(path.join(debugDir, `amazon-${asin}-product-page.html`), productContent);

    // Extract aggregate rating
    const aggregate = extractAggregateRating(productContent, asin);
    if (aggregate) {
      reviews.push(aggregate);
      logger.info(`Found aggregate rating: ${aggregate.rating_value}/5`);
    }

    // Navigate to reviews page
    const reviewsUrl = `https://${config.sources.amazon.baseUrl}/product-reviews/${asin}`;
    logger.info(`Navigating to reviews page: ${reviewsUrl}`);

    await page.goto(reviewsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Check for login page or CAPTCHA
    if (await isLoginPage(page)) {
      logger.warn('Detected login page on reviews page');
      await browser.takeScreenshot(page, `amazon-login-reviews-${asin}`);

      // Save the page HTML for debugging
      const html = await page.content();
      fs.writeFileSync(path.join(debugDir, `amazon-${asin}-login-reviews-page.html`), html);

      // If we have the aggregate rating, return that
      if (reviews.length > 0) {
        logger.info('Returning aggregate rating only due to login page');

        // Cache the results
        const dataToCache = {
          total: reviews.length,
          reviews: reviews
        };

        await cache.set('amazon', asin, dataToCache);

        return {
          source: 'scrape',
          total: reviews.length,
          reviews: reviews
        };
      }

      throw new Error('Login page detected and no aggregate rating found');
    }

    if (await hasCaptcha(page)) {
      logger.warn('Detected CAPTCHA on reviews page');
      await browser.takeScreenshot(page, `amazon-captcha-reviews-${asin}`);

      // Save the page HTML for debugging
      const html = await page.content();
      fs.writeFileSync(path.join(debugDir, `amazon-${asin}-captcha-reviews-page.html`), html);

      // If we have the aggregate rating, return that
      if (reviews.length > 0) {
        logger.info('Returning aggregate rating only due to CAPTCHA');

        // Cache the results
        const dataToCache = {
          total: reviews.length,
          reviews: reviews
        };

        await cache.set('amazon', asin, dataToCache);

        return {
          source: 'scrape',
          total: reviews.length,
          reviews: reviews
        };
      }

      throw new Error('CAPTCHA detected and no aggregate rating found');
    }

    // Get the reviews page content
    const reviewsContent = await page.content();

    // Save the reviews page HTML for debugging
    fs.writeFileSync(path.join(debugDir, `amazon-${asin}-reviews-page.html`), reviewsContent);

    // Parse reviews from the first page
    const firstPageReviews = parseReviewsWithRegex(reviewsContent, asin);
    reviews = [...reviews, ...firstPageReviews];

    logger.info(`Found ${firstPageReviews.length} reviews on first page`);

    // If we need more reviews and have less than the limit, try more pages
    let pageNum = 2;
    while (reviews.length < limit + 1 && pageNum <= config.sources.amazon.maxPages) {
      try {
        const nextPageUrl = `https://${config.sources.amazon.baseUrl}/product-reviews/${asin}?pageNumber=${pageNum}`;
        logger.info(`Navigating to page ${pageNum}: ${nextPageUrl}`);

        await page.goto(nextPageUrl, { waitUntil: 'networkidle2', timeout: 30000 });

        // Check for login page or CAPTCHA
        if (await isLoginPage(page) || await hasCaptcha(page)) {
          logger.warn(`Detected login page or CAPTCHA on page ${pageNum}`);
          await browser.takeScreenshot(page, `amazon-login-captcha-page${pageNum}-${asin}`);
          break;
        }

        const pageContent = await page.content();

        // Save the page HTML for debugging
        fs.writeFileSync(path.join(debugDir, `amazon-${asin}-reviews-page${pageNum}.html`), pageContent);

        const pageReviews = parseReviewsWithRegex(pageContent, asin);

        if (pageReviews.length === 0) {
          logger.info(`No more reviews found on page ${pageNum}`);
          break;
        }

        logger.info(`Found ${pageReviews.length} reviews on page ${pageNum}`);
        reviews = [...reviews, ...pageReviews];
        pageNum++;

        // Add a small delay between pages
        await page.waitForTimeout(2000);
      } catch (err) {
        logger.error(`Error fetching page ${pageNum}: ${err.message}`);
        break;
      }
    }

    // Limit the number of reviews to the requested limit
    const limitedReviews = reviews.slice(0, limit);

    // Cache the results
    const dataToCache = {
      total: reviews.length,
      reviews: limitedReviews
    };

    await cache.set('amazon', asin, dataToCache);

    return {
      source: 'scrape',
      total: reviews.length,
      reviews: limitedReviews
    };
  } catch (error) {
    logger.error(`Error scraping Amazon reviews: ${error.message}`);

    // Take a screenshot for debugging
    try {
      await browser.takeScreenshot(page, `amazon-error-${asin}`);
    } catch (screenshotError) {
      logger.error(`Error taking screenshot: ${screenshotError.message}`);
    }

    throw error;
  } finally {
    await page.close();
  }
}

/**
 * Scrape reviews from Amazon mobile site
 * @param {string} asin - Amazon ASIN
 * @param {number} limit - Maximum number of reviews to return
 * @param {Object} options - Additional options
 * @returns {Promise<Array>} - Array of reviews
 */
async function scrapeMobileAmazonReviews(asin, limit = 50, options = {}) {
  logger.info(`Starting Amazon mobile scraping for ASIN: ${asin}`);

  // Extract options
  const maxPages = options.maxPages || config.sources.amazon.maxPages;

  const page = await browser.getNewPage();
  let reviews = [];

  try {
    // Set mobile user agent and viewport
    const mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';
    await page.setUserAgent(mobileUserAgent);

    await page.setViewport({
      width: 375,
      height: 812,
      isMobile: true,
      hasTouch: true
    });

    // Load cookies if available
    const domain = config.sources.amazon.baseUrl.replace('https://', '').replace('http://', '');
    const cookies = browser.loadCookies(domain);
    if (cookies.length > 0) {
      await page.setCookie(...cookies);
    }

    // Navigate to mobile reviews page
    const mobileBaseUrl = config.sources.amazon.baseUrl.replace('www.', 'm.');
    const mobileReviewsUrl = `https://${mobileBaseUrl}/gp/aw/cr/asin/${asin}`;
    logger.info(`Navigating to mobile reviews page: ${mobileReviewsUrl}`);

    await page.goto(mobileReviewsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Simulate human behavior
    await browser.simulateHumanBehavior(page);

    // Check for login page or CAPTCHA
    if (await isLoginPage(page) || await hasCaptcha(page)) {
      logger.warn('Detected login page or CAPTCHA on mobile page');
      await browser.takeScreenshot(page, `amazon-mobile-login-captcha-${asin}`);

      // Save the page HTML for debugging
      const html = await page.content();
      fs.writeFileSync(path.join(debugDir, `amazon-${asin}-mobile-login-captcha.html`), html);

      // Try the product page to at least get aggregate rating
      const mobileProductUrl = `https://${mobileBaseUrl}/dp/${asin}`;
      logger.info(`Navigating to mobile product page: ${mobileProductUrl}`);

      await page.goto(mobileProductUrl, { waitUntil: 'networkidle2', timeout: 30000 });

      // Simulate human behavior
      await browser.simulateHumanBehavior(page);

      const productContent = await page.content();

      // Extract aggregate rating
      const aggregate = extractAggregateRating(productContent, asin);
      if (aggregate) {
        reviews.push(aggregate);
        logger.info(`Found aggregate rating from mobile: ${aggregate.rating_value}/5`);
      }

      // Save cookies for future use
      const currentCookies = await page.cookies();
      await browser.saveCookies(domain, currentCookies);

      return reviews;
    }

    // Get the reviews page content
    const reviewsContent = await page.content();

    // Save the reviews page HTML for debugging
    fs.writeFileSync(path.join(debugDir, `amazon-${asin}-mobile-reviews.html`), reviewsContent);

    // Extract reviews using mobile-specific selectors
    const mobileReviews = await extractMobileReviews(page, asin);
    reviews = [...reviews, ...mobileReviews];

    logger.info(`Found ${mobileReviews.length} reviews on first mobile page`);

    // If we need more reviews and have less than the limit, try more pages
    let pageNum = 2;
    while (reviews.length < limit && pageNum <= maxPages) {
      try {
        // Look for "Next page" link
        const hasNextPage = await page.evaluate(() => {
          const nextLinks = Array.from(document.querySelectorAll('a')).filter(a =>
            a.textContent.includes('Next page') ||
            a.textContent.includes('Next') ||
            a.href.includes('pageNumber=')
          );
          return nextLinks.length > 0;
        });

        if (!hasNextPage) {
          logger.info('No next page link found on mobile site');
          break;
        }

        // Click the next page link
        await page.evaluate(() => {
          const nextLinks = Array.from(document.querySelectorAll('a')).filter(a =>
            a.textContent.includes('Next page') ||
            a.textContent.includes('Next') ||
            a.href.includes('pageNumber=')
          );
          if (nextLinks.length > 0) nextLinks[0].click();
        });

        // Wait for navigation
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 });

        // Simulate human behavior
        await browser.simulateHumanBehavior(page);

        // Check for login page or CAPTCHA
        if (await isLoginPage(page) || await hasCaptcha(page)) {
          logger.warn(`Detected login page or CAPTCHA on mobile page ${pageNum}`);
          await browser.takeScreenshot(page, `amazon-mobile-login-captcha-page${pageNum}-${asin}`);
          break;
        }

        const pageContent = await page.content();

        // Save the page HTML for debugging
        fs.writeFileSync(path.join(debugDir, `amazon-${asin}-mobile-reviews-page${pageNum}.html`), pageContent);

        // Extract reviews
        const pageReviews = await extractMobileReviews(page, asin);

        if (pageReviews.length === 0) {
          logger.info(`No more reviews found on mobile page ${pageNum}`);
          break;
        }

        logger.info(`Found ${pageReviews.length} reviews on mobile page ${pageNum}`);
        reviews = [...reviews, ...pageReviews];
        pageNum++;

        // Add a small delay between pages
        await page.waitForTimeout(Math.floor(Math.random() * 2000) + 1000);
      } catch (err) {
        logger.error(`Error fetching mobile page ${pageNum}: ${err.message}`);
        break;
      }
    }

    // Save cookies for future use
    const currentCookies = await page.cookies();
    await browser.saveCookies(domain, currentCookies);

    return reviews;
  } catch (error) {
    logger.error(`Error scraping Amazon mobile reviews: ${error.message}`);

    // Take a screenshot for debugging
    try {
      await browser.takeScreenshot(page, `amazon-mobile-error-${asin}`);
    } catch (screenshotError) {
      logger.error(`Error taking screenshot: ${screenshotError.message}`);
    }

    return [];
  } finally {
    await page.close();
  }
}

/**
 * Extract reviews from mobile page using DOM selectors
 * @param {Object} page - Puppeteer page
 * @param {string} asin - Amazon ASIN
 * @returns {Promise<Array>} - Array of reviews
 */
async function extractMobileReviews(page, asin) {
  return page.evaluate((asin, sourceId) => {
    const reviews = [];

    // Find review containers
    const reviewContainers = document.querySelectorAll('.review');

    for (const container of reviewContainers) {
      try {
        // Extract reviewer name
        const nameElement = container.querySelector('.a-profile-name');
        const reviewerName = nameElement ? nameElement.textContent.trim() : 'Amazon Customer';

        // Extract rating
        const ratingElement = container.querySelector('.a-icon-star');
        let rating = 0;
        if (ratingElement) {
          const ratingText = ratingElement.textContent.trim();
          const ratingMatch = ratingText.match(/([0-9.]+)/);
          rating = ratingMatch ? parseFloat(ratingMatch[1]) : 0;
        }

        // Extract date
        const dateElement = container.querySelector('[data-hook="review-date"]');
        const reviewDate = dateElement ? dateElement.textContent.trim() : new Date().toISOString().split('T')[0];

        // Extract title
        const titleElement = container.querySelector('[data-hook="review-title"]');
        const title = titleElement ? titleElement.textContent.trim() : '';

        // Extract text
        const textElement = container.querySelector('.review-text');
        const reviewText = textElement ? textElement.textContent.trim() : '';

        // Only add reviews with valid ratings and text
        if (rating > 0 && reviewText) {
          reviews.push({
            source_id: sourceId,
            reviewer_name: reviewerName,
            review_date: reviewDate,
            original_rating: `${rating}/5`,
            rating_value: rating,
            rating_scale: 5,
            rating_normalised: rating / 5,
            review_text: reviewText,
            metadata: JSON.stringify({
              asin: asin,
              review_title: title,
              review_url: `https://www.amazon.com/product-reviews/${asin}`,
              affiliate_url: `https://www.amazon.com/dp/${asin}`,
              source: 'mobile'
            })
          });
        }
      } catch (error) {
        console.error('Error extracting review:', error);
      }
    }

    return reviews;
  }, asin, this.sourceId || 5);
}

module.exports = {
  scrapeAmazonReviews
};
