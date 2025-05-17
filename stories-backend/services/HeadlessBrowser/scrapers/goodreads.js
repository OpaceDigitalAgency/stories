/**
 * Goodreads review scraper
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
 * Extract book ID from Goodreads URL
 * @param {string} url - Goodreads URL
 * @returns {string|null} - Book ID or null if not found
 */
function extractBookIdFromUrl(url) {
  // Try to extract book ID from URL
  const patterns = [
    /goodreads\.com\/book\/show\/(\d+)/,
    /goodreads\.com\/book\/show\/([\w.-]+)/
  ];
  
  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match && match[1]) {
      return match[1];
    }
  }
  
  return null;
}

/**
 * Scrape reviews from a Goodreads book page
 * @param {string} goodreadsUrl - The URL of the Goodreads book page
 * @param {number} limit - Maximum number of reviews to return
 * @returns {Promise<Object>} - Object containing book title and reviews
 */
async function scrapeGoodreadsReviews(goodreadsUrl, limit = 50) {
  logger.info(`Starting Goodreads scraping for URL: ${goodreadsUrl}`);
  
  // Extract book ID for caching
  const bookId = extractBookIdFromUrl(goodreadsUrl);
  if (!bookId) {
    logger.warn(`Could not extract book ID from URL: ${goodreadsUrl}`);
  } else {
    // Check cache first
    const cachedData = await cache.get('goodreads', bookId);
    if (cachedData) {
      logger.info(`Using cached data for Goodreads book ID: ${bookId}`);
      return {
        source: 'cache',
        ...cachedData
      };
    }
  }
  
  const page = await browser.getNewPage();
  let reviews = [];
  let bookTitle = 'Unknown Book';
  
  try {
    // Navigate to the reviews page
    logger.info(`Navigating to: ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 30000 });
    
    // Save the page HTML for debugging
    const html = await page.content();
    fs.writeFileSync(path.join(debugDir, `goodreads-${bookId || 'unknown'}-page.html`), html);
    
    // Extract book title
    bookTitle = await page.evaluate(() => {
      const titleElement = document.querySelector('h1.BookPageTitleSection__title');
      return titleElement ? titleElement.textContent.trim() : 'Unknown Book';
    });
    
    logger.info(`Book title: ${bookTitle}`);
    
    // Extract aggregate rating
    const aggregateRating = await page.evaluate(() => {
      const ratingElement = document.querySelector('div.RatingStatistics__rating');
      const countElement = document.querySelector('div.RatingStatistics__meta span');
      
      const rating = ratingElement ? parseFloat(ratingElement.textContent.trim()) : null;
      const count = countElement ? parseInt(countElement.textContent.trim().replace(/[^0-9]/g, '')) : 0;
      
      return { rating, count };
    });
    
    if (aggregateRating.rating) {
      reviews.push({
        source_id: 4, // Goodreads source ID
        reviewer_name: 'Goodreads Aggregate',
        rating: aggregateRating.rating,
        rating_normalised: aggregateRating.rating / 5,
        review_text: `This book has an average rating of ${aggregateRating.rating}/5 based on ${aggregateRating.count} ratings on Goodreads.`,
        review_date: new Date().toISOString().split('T')[0],
        metadata: JSON.stringify({
          book_id: bookId,
          is_aggregate: true,
          ratings_count: aggregateRating.count
        })
      });
      
      logger.info(`Aggregate rating: ${aggregateRating.rating}/5 from ${aggregateRating.count} ratings`);
    }
    
    // Navigate to reviews page if not already there
    if (!goodreadsUrl.includes('/reviews')) {
      const reviewsUrl = `${goodreadsUrl.replace(/\?.*$/, '').replace(/\/$/, '')}/reviews`;
      logger.info(`Navigating to reviews page: ${reviewsUrl}`);
      await page.goto(reviewsUrl, { waitUntil: 'networkidle2', timeout: 30000 });
      
      // Save the reviews page HTML for debugging
      const reviewsHtml = await page.content();
      fs.writeFileSync(path.join(debugDir, `goodreads-${bookId || 'unknown'}-reviews-page.html`), reviewsHtml);
    }
    
    // Function to extract reviews from current page
    const extractReviewsFromPage = async () => {
      return page.evaluate(() => {
        const reviewElements = document.querySelectorAll('div.ReviewsList__item');
        const pageReviews = [];
        
        reviewElements.forEach(reviewElement => {
          // Extract reviewer name
          const nameElement = reviewElement.querySelector('.ReviewerProfile__name');
          const reviewerName = nameElement ? nameElement.textContent.trim() : 'Goodreads User';
          
          // Extract rating
          const ratingElement = reviewElement.querySelector('.RatingStars');
          let ratingValue = null;
          
          if (ratingElement) {
            const ariaLabel = ratingElement.getAttribute('aria-label');
            if (ariaLabel) {
              const match = ariaLabel.match(/(\d+)/);
              if (match) {
                ratingValue = parseInt(match[1]);
              }
            }
          }
          
          // Extract review text
          const textElement = reviewElement.querySelector('.ReviewText__content');
          const reviewText = textElement ? textElement.textContent.trim() : '';
          
          // Extract date
          const dateElement = reviewElement.querySelector('.ReviewCard__date');
          const reviewDate = dateElement ? 
            dateElement.textContent.trim().replace(/^reviewed\s+/i, '') : 
            new Date().toISOString().split('T')[0];
          
          if (ratingValue && reviewText) {
            pageReviews.push({
              reviewer_name: reviewerName,
              rating: ratingValue,
              rating_normalised: ratingValue / 5,
              review_text: reviewText,
              review_date: reviewDate
            });
          }
        });
        
        return pageReviews;
      });
    };
    
    // Extract reviews from first page
    let pageReviews = await extractReviewsFromPage();
    reviews = [...reviews, ...pageReviews.map(review => ({
      source_id: 4, // Goodreads source ID
      ...review,
      metadata: JSON.stringify({
        book_id: bookId
      })
    }))];
    
    logger.info(`Extracted ${pageReviews.length} reviews from first page`);
    
    // Click "More reviews" button and extract more reviews until we reach the limit
    let pageNum = 2;
    while (reviews.length < limit + 1 && pageNum <= config.sources.goodreads.maxPages) {
      logger.info(`Attempting to load page ${pageNum} by clicking "More reviews" button`);
      
      // Check if "More reviews" button exists
      const hasMoreButton = await page.evaluate(() => {
        const button = document.querySelector('button.Button--secondary');
        return button && (button.textContent.includes('More reviews') || button.textContent.includes('Show more reviews'));
      });
      
      if (!hasMoreButton) {
        logger.info('No "More reviews" button found, stopping pagination');
        break;
      }
      
      // Click the "More reviews" button
      await page.evaluate(() => {
        const button = document.querySelector('button.Button--secondary');
        if (button) button.click();
      });
      
      // Wait for new reviews to load
      await page.waitForTimeout(3000);
      
      // Extract reviews from the new page
      pageReviews = await extractReviewsFromPage();
      if (pageReviews.length === 0) {
        logger.info(`No reviews found on page ${pageNum}, stopping pagination`);
        break;
      }
      
      reviews = [...reviews, ...pageReviews.map(review => ({
        source_id: 4, // Goodreads source ID
        ...review,
        metadata: JSON.stringify({
          book_id: bookId
        })
      }))];
      
      logger.info(`Extracted ${pageReviews.length} reviews from page ${pageNum}`);
      pageNum++;
      
      // Add a delay between pages to avoid rate limiting
      await page.waitForTimeout(2000);
    }
    
    // Limit the number of reviews to the requested limit
    const limitedReviews = reviews.slice(0, limit);
    
    // Cache the results
    if (bookId) {
      const dataToCache = {
        book_title: bookTitle,
        total: reviews.length,
        reviews: limitedReviews
      };
      
      await cache.set('goodreads', bookId, dataToCache);
    }
    
    return {
      source: 'scrape',
      book_title: bookTitle,
      total: reviews.length,
      reviews: limitedReviews
    };
  } catch (error) {
    logger.error(`Error scraping Goodreads reviews: ${error.message}`);
    
    // Take a screenshot for debugging
    try {
      await browser.takeScreenshot(page, `goodreads-error-${bookId || 'unknown'}`);
    } catch (screenshotError) {
      logger.error(`Error taking screenshot: ${screenshotError.message}`);
    }
    
    throw error;
  } finally {
    await page.close();
  }
}

module.exports = {
  scrapeGoodreadsReviews
};
