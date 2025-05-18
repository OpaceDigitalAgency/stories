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
 * @param {string} url - Goodreads book URL
 * @returns {string|null} - Book ID or null if not found
 */
function extractBookIdFromUrl(url) {
  // Match patterns like /book/show/12345.Book_Title or /book/show/12345-Book-Title
  // or /book/isbn/9781234567890

  // First, try to extract from ISBN URL
  const isbnMatch = url.match(/\/book\/isbn\/(\d+)/);
  if (isbnMatch && isbnMatch[1]) {
    logger.info(`Extracted ISBN ${isbnMatch[1]} from URL`);
    return isbnMatch[1];
  }

  // Next, try to extract numeric ID from show URL
  const numericIdMatch = url.match(/\/book\/show\/(\d+)(?:[.-]|$)/);
  if (numericIdMatch && numericIdMatch[1]) {
    logger.info(`Extracted numeric ID ${numericIdMatch[1]} from URL`);
    return numericIdMatch[1];
  }

  // Finally, try to extract alphanumeric ID from show URL
  const alphaNumIdMatch = url.match(/\/book\/show\/([\w.-]+)/);
  if (alphaNumIdMatch && alphaNumIdMatch[1]) {
    // If the ID contains a period or hyphen, extract just the first part
    const cleanId = alphaNumIdMatch[1].split(/[.-]/)[0];
    logger.info(`Extracted alphanumeric ID ${cleanId} from URL`);
    return cleanId;
  }

  logger.warn(`Could not extract book ID from URL: ${url}`);
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
    let clickAttempts = 0;
    const maxClickAttempts = 20; // Try up to 20 times to get more reviews

    logger.info(`📊 Starting enhanced review extraction - target: ${limit} reviews`);

    while (reviews.length < limit + 1 && pageNum <= config.sources.goodreads.maxPages && clickAttempts < maxClickAttempts) {
      logger.info(`📑 Attempting to load more reviews (attempt ${clickAttempts + 1}/${maxClickAttempts})`);

      // Scroll to bottom to ensure the button is visible
      await page.evaluate(() => {
        window.scrollTo(0, document.body.scrollHeight);
      });

      await page.waitForTimeout(1000);

      // Check for different button selectors using CSS selectors
      const cssButtonSelectors = [
        'button.Button--secondary',
        '.gr_more_reviews_button',
        'button.Button--secondary[data-testid="loadMore"]'
      ];

      // Also try XPath selectors for text-based matching (more reliable than CSS :contains)
      const xpathButtonSelectors = [
        "//button[contains(., 'Show more reviews')]",
        "//button[contains(., 'More reviews')]",
        "//button[contains(., 'Load more')]"
      ];

      let buttonFound = false;

      // First try CSS selectors
      for (const selector of cssButtonSelectors) {
        try {
          const hasButton = await page.evaluate((sel) => {
            const button = document.querySelector(sel);
            if (!button) return false;

            // Check if button is visible
            const rect = button.getBoundingClientRect();
            const isVisible = rect.top >= 0 && rect.left >= 0 &&
                             rect.bottom <= window.innerHeight && rect.right <= window.innerWidth;

            if (isVisible) {
              // Check button text
              const text = button.textContent.toLowerCase();
              return text.includes('more reviews') || text.includes('show more') || text.includes('load more');
            }
            return false;
          }, selector);

          if (hasButton) {
            logger.info(`✅ Found "More reviews" button using CSS selector: ${selector}`);

            // Click the button
            await page.evaluate((sel) => {
              const button = document.querySelector(sel);
              if (button) button.click();
            }, selector);

            buttonFound = true;
            break;
          }
        } catch (err) {
          logger.error(`Error with CSS selector ${selector}: ${err.message}`);
        }
      }

      // If CSS selectors didn't work, try XPath selectors
      if (!buttonFound) {
        for (const xpathSelector of xpathButtonSelectors) {
          try {
            // Find elements using XPath
            const buttons = await page.$x(xpathSelector);

            if (buttons.length > 0) {
              logger.info(`✅ Found "More reviews" button using XPath selector: ${xpathSelector}`);

              // Click the first matching button
              await buttons[0].click();

              buttonFound = true;
              break;
            }
          } catch (err) {
            logger.error(`Error with XPath selector ${xpathSelector}: ${err.message}`);
          }
        }
      }

      if (!buttonFound) {
        // Try a more generic XPath approach as a last resort
        try {
          // Look for any button that contains text about reviews or loading more
          const genericButtons = await page.$x("//button[contains(., 'review') or contains(., 'Review') or contains(., 'more') or contains(., 'More') or contains(., 'load') or contains(., 'Load')]");

          if (genericButtons.length > 0) {
            logger.info(`✅ Found a potential "More reviews" button using generic XPath (${genericButtons.length} matches)`);

            // Click the first button that might be relevant
            await genericButtons[0].click();
            logger.info(`Clicked generic button with text: ${await page.evaluate(el => el.textContent, genericButtons[0])}`);

            buttonFound = true;
          } else {
            logger.info(`⚠️ No buttons found with generic XPath selectors`);

            // Take a screenshot to see what's on the page
            await browser.takeScreenshot(page, `goodreads-no-buttons-${bookId}-attempt-${clickAttempts}`);

            // Log the HTML for debugging
            const currentHtml = await page.content();
            fs.writeFileSync(path.join(debugDir, `goodreads-no-buttons-${bookId}-attempt-${clickAttempts}.html`), currentHtml);
          }
        } catch (err) {
          logger.error(`Error with generic XPath approach: ${err.message}`);
        }
      }

      // Wait for new reviews to load
      await page.waitForTimeout(3000);

      // Take a screenshot for debugging if needed
      if (clickAttempts % 5 === 0) {
        await browser.takeScreenshot(page, `goodreads-pagination-${bookId}-attempt-${clickAttempts}`);
      }

      // Extract reviews from the updated page
      const newReviews = await extractReviewsFromPage();

      // Check if we got new reviews
      if (newReviews.length > pageReviews.length) {
        logger.info(`✅ Found ${newReviews.length - pageReviews.length} new reviews`);

        // Update pageReviews with the new reviews
        pageReviews = newReviews;

        // Add the new reviews to our collection
        reviews = [...reviews, ...pageReviews.map(review => ({
          source_id: 4, // Goodreads source ID
          ...review,
          metadata: JSON.stringify({
            book_id: bookId
          })
        }))];

        logger.info(`📊 Total reviews collected: ${reviews.length}`);
        pageNum++;

        // Reset click attempts on success
        clickAttempts = 0;
      } else {
        logger.info(`⚠️ No new reviews found after clicking, attempt ${clickAttempts + 1}/${maxClickAttempts}`);
        clickAttempts++;
      }

      // Add a delay between attempts
      await page.waitForTimeout(2000);
    }

    logger.info(`🏁 Finished review extraction. Total reviews: ${reviews.length}`);

    // If we didn't get enough reviews, log the reason
    if (reviews.length < limit) {
      if (clickAttempts >= maxClickAttempts) {
        logger.info(`⚠️ Stopped after ${maxClickAttempts} click attempts`);
      } else if (pageNum > config.sources.goodreads.maxPages) {
        logger.info(`⚠️ Reached maximum page limit: ${config.sources.goodreads.maxPages}`);
      } else {
        logger.info(`⚠️ No more reviews available`);
      }
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
