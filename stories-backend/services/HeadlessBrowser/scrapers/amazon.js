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
 * @returns {Promise<Object>} - Object containing reviews
 */
async function scrapeAmazonReviews(asin, limit = 50) {
  logger.info(`Starting Amazon scraping for ASIN: ${asin}`);
  
  // Check cache first
  const cachedData = await cache.get('amazon', asin);
  if (cachedData) {
    logger.info(`Using cached data for Amazon ASIN: ${asin}`);
    return {
      source: 'cache',
      ...cachedData
    };
  }
  
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

module.exports = {
  scrapeAmazonReviews
};
