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

  try {
    // Match review blocks - try multiple patterns to be more robust
    const reviewRegexPatterns = [
      /<div[^>]*data-hook="review"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/gi,
      /<div[^>]*data-hook="review"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/gi,
      /<div[^>]*class="[^"]*review[^"]*"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/gi
    ];

    let reviewMatches = [];

    // Try each pattern until we find reviews
    for (const pattern of reviewRegexPatterns) {
      let reviewMatch;
      const regex = new RegExp(pattern);

      while ((reviewMatch = regex.exec(html)) !== null) {
        reviewMatches.push(reviewMatch[0]);
      }

      if (reviewMatches.length > 0) {
        logger.info(`Found ${reviewMatches.length} reviews using pattern: ${pattern}`);
        break;
      }
    }

    if (reviewMatches.length === 0) {
      logger.warn('No reviews found with any regex pattern');
      return reviews;
    }

    for (const reviewBlock of reviewMatches) {
      try {
        // Extract reviewer name - try multiple patterns
        let reviewerName = 'Amazon Customer';
        const nameRegexPatterns = [
          /<span[^>]*class="a-profile-name"[^>]*>([\s\S]*?)<\/span>/i,
          /<div[^>]*class="[^"]*a-profile-content[^"]*"[^>]*>([\s\S]*?)<\/div>/i
        ];

        for (const pattern of nameRegexPatterns) {
          const nameMatch = reviewBlock.match(pattern);
          if (nameMatch && nameMatch[1].trim()) {
            reviewerName = nameMatch[1].trim();
            break;
          }
        }

        // Extract rating - try multiple patterns
        let rating = null;
        const ratingRegexPatterns = [
          /<i[^>]*class="[^"]*a-icon-star[^"]*"[^>]*><span[^>]*>([0-9.]+) out of 5 stars<\/span><\/i>/i,
          /<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([0-9.]+) out of 5 stars<\/span>/i,
          /([0-9.]+) out of 5 stars/i
        ];

        for (const pattern of ratingRegexPatterns) {
          const ratingMatch = reviewBlock.match(pattern);
          if (ratingMatch && !isNaN(parseFloat(ratingMatch[1]))) {
            rating = parseFloat(ratingMatch[1]);
            break;
          }
        }

        // Extract date
        let reviewDate = new Date().toISOString().split('T')[0]; // Default to today
        const dateRegexPatterns = [
          /<span[^>]*data-hook="review-date"[^>]*>([\s\S]*?)<\/span>/i,
          /Reviewed in .* on ([A-Za-z]+ [0-9]+, [0-9]+)/i
        ];

        for (const pattern of dateRegexPatterns) {
          const dateMatch = reviewBlock.match(pattern);
          if (dateMatch && dateMatch[1].trim()) {
            reviewDate = dateMatch[1].trim();
            break;
          }
        }

        // Extract title
        let title = '';
        const titleRegexPatterns = [
          /<a[^>]*data-hook="review-title"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/a>/i,
          /<span[^>]*data-hook="review-title"[^>]*>([\s\S]*?)<\/span>/i,
          /<a[^>]*class="[^"]*review-title[^"]*"[^>]*>([\s\S]*?)<\/a>/i
        ];

        for (const pattern of titleRegexPatterns) {
          const titleMatch = reviewBlock.match(pattern);
          if (titleMatch && titleMatch[1].trim()) {
            title = titleMatch[1].trim();
            break;
          }
        }

        // Extract review text
        let reviewText = '';
        const textRegexPatterns = [
          /<span[^>]*data-hook="review-body"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/span>/i,
          /<div[^>]*class="[^"]*review-data[^"]*"[^>]*>([\s\S]*?)<\/div>/i,
          /<span[^>]*class="[^"]*review-text[^"]*"[^>]*>([\s\S]*?)<\/span>/i
        ];

        for (const pattern of textRegexPatterns) {
          const textMatch = reviewBlock.match(pattern);
          if (textMatch && textMatch[1].trim()) {
            reviewText = textMatch[1].trim()
              .replace(/<br\s*\/?>/gi, '\n') // Replace <br> with newlines
              .replace(/<[^>]*>/g, ''); // Remove any remaining HTML tags
            break;
          }
        }

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
              review_url: `${config.sources.amazon.baseUrl}/product-reviews/${asin}`,
              affiliate_url: `${config.sources.amazon.baseUrl}/dp/${asin}?tag=${config.sources.amazon.affiliateTag}`,
            }),
          });
        }
      } catch (reviewError) {
        logger.error(`Error parsing individual review: ${reviewError.message}`);
        // Continue with next review
      }
    }
  } catch (error) {
    logger.error(`Error in parseReviewsWithRegex: ${error.message}`);
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
  try {
    // Try multiple patterns for rating
    const ratingRegexPatterns = [
      /<span[^>]*class="[^"]*a-icon-alt[^"]*"[^>]*>([0-9.]+) out of 5 stars<\/span>/i,
      /<span[^>]*>([0-9.]+) out of 5 stars<\/span>/i,
      /([0-9.]+) out of 5 stars/i,
      /<div[^>]*class="[^"]*a-row[^"]*"[^>]*>([0-9.]+) out of 5 stars<\/div>/i
    ];

    let avg = null;

    for (const pattern of ratingRegexPatterns) {
      const ratingMatch = html.match(pattern);
      if (ratingMatch && !isNaN(parseFloat(ratingMatch[1]))) {
        avg = parseFloat(ratingMatch[1]);
        logger.info(`Found aggregate rating ${avg} using pattern: ${pattern}`);
        break;
      }
    }

    if (avg === null) {
      logger.warn('No aggregate rating found with any regex pattern');
      return null;
    }

    // Try multiple patterns for number of ratings
    const countRegexPatterns = [
      /(\d+(?:,\d+)*) ratings?/i,
      /(\d+(?:,\d+)*) reviews?/i,
      /(\d+(?:,\d+)*) global ratings?/i,
      /(\d+(?:,\d+)*) global reviews?/i
    ];

    let count = 1;

    for (const pattern of countRegexPatterns) {
      const countMatch = html.match(pattern);
      if (countMatch) {
        count = parseInt(countMatch[1].replace(/,/g, ''));
        logger.info(`Found ${count} ratings using pattern: ${pattern}`);
        break;
      }
    }

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
        review_url: `${config.sources.amazon.baseUrl}/product-reviews/${asin}`,
        affiliate_url: `${config.sources.amazon.baseUrl}/dp/${asin}?tag=${config.sources.amazon.affiliateTag}`,
        is_aggregate: true,
        ratings_count: count,
      }),
    };
  } catch (error) {
    logger.error(`Error in extractAggregateRating: ${error.message}`);
    return null;
  }
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
    const productUrl = `${config.sources.amazon.baseUrl}/dp/${asin}`;
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
    const reviewsUrl = `${config.sources.amazon.baseUrl}/product-reviews/${asin}`;
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
        const nextPageUrl = `${config.sources.amazon.baseUrl}/product-reviews/${asin}?pageNumber=${pageNum}`;
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
    logger.error(`Stack trace: ${error.stack}`);

    // Take a screenshot for debugging
    try {
      if (page && page.browser) {
        await browser.takeScreenshot(page, `amazon-error-${asin}`);
      }
    } catch (screenshotError) {
      logger.error(`Error taking screenshot: ${screenshotError.message}`);
    }

    // Return any reviews we might have collected instead of throwing
    if (reviews.length > 0) {
      logger.info(`Returning ${reviews.length} reviews despite error`);
      return {
        source: 'scrape_partial',
        total: reviews.length,
        reviews: reviews.slice(0, limit)
      };
    }

    // Return empty result instead of throwing
    return {
      source: 'scrape_error',
      total: 0,
      reviews: []
    };
  } finally {
    try {
      if (page && page.browser) {
        await page.close();
      }
    } catch (closeError) {
      logger.error(`Error closing page: ${closeError.message}`);
    }
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
    const domain = new URL(config.sources.amazon.baseUrl).hostname;
    const cookies = browser.loadCookies(domain);
    if (cookies.length > 0) {
      await page.setCookie(...cookies);
    }

    // Navigate to mobile reviews page
    const mobileBaseUrl = config.sources.amazon.baseUrl.replace('www.', 'm.').replace('https://', '').replace('http://', '');
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
    logger.error(`Stack trace: ${error.stack}`);

    // Take a screenshot for debugging
    try {
      if (page && page.browser) {
        await browser.takeScreenshot(page, `amazon-mobile-error-${asin}`);
      }
    } catch (screenshotError) {
      logger.error(`Error taking screenshot: ${screenshotError.message}`);
    }

    return [];
  } finally {
    try {
      if (page && page.browser) {
        await page.close();
      }
    } catch (closeError) {
      logger.error(`Error closing page: ${closeError.message}`);
    }
  }
}

/**
 * Extract reviews from mobile page using DOM selectors
 * @param {Object} page - Puppeteer page
 * @param {string} asin - Amazon ASIN
 * @returns {Promise<Array>} - Array of reviews
 */
async function extractMobileReviews(page, asin) {
  // Get the base domain for URLs
  const domain = new URL(config.sources.amazon.baseUrl).hostname;

  return page.evaluate((asin, sourceId, domain, affiliateTag) => {
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
              review_url: `https://${domain}/product-reviews/${asin}`,
              affiliate_url: `https://${domain}/dp/${asin}?tag=${affiliateTag}`,
              source: 'mobile'
            })
          });
        }
      } catch (error) {
        console.error('Error extracting review:', error);
      }
    }

    return reviews;
  }, asin, 5, domain, config.sources.amazon.affiliateTag);
}

module.exports = {
  scrapeAmazonReviews
};
