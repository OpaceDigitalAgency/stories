<?php
/**
 * Fix Goodreads Scraper
 * 
 * This script fixes the Goodreads scraper on the VPS server by updating the selector
 * for the "Show more reviews" button.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

echo "Fix Goodreads Scraper\n";
echo "===================\n\n";

// VPS server details
$vpsIp = '37.27.31.107';
$vpsPort = 3000;
$apiKey = 'stories-scraper-api-key-2023';

echo "VPS Server: {$vpsIp}:{$vpsPort}\n";
echo "API Key: {$apiKey}\n\n";

// First, check if the server is reachable
echo "Checking server health...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://{$vpsIp}:{$vpsPort}/health");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error: VPS server is not reachable. HTTP Status Code: {$httpCode}\n");
}

echo "Server is healthy! Response: {$response}\n\n";

// Create a temporary file with the fixed code
$tempFile = tempnam(sys_get_temp_dir(), 'goodreads_');
$fixedCode = <<<'EOT'
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
          try {
            // Extract reviewer name
            const nameElement = reviewElement.querySelector('[data-testid="user-profile-link"]');
            const reviewerName = nameElement ? nameElement.textContent.trim() : 'Unknown Reviewer';

            // Extract rating
            const ratingElement = reviewElement.querySelector('[data-testid="rating-stars"]');
            let rating = 0;
            if (ratingElement) {
              const ariaLabel = ratingElement.getAttribute('aria-label');
              if (ariaLabel && ariaLabel.includes('out of 5')) {
                rating = parseFloat(ariaLabel.match(/([0-9.]+) out of 5/)[1]);
              }
            }

            // Extract review text
            const textElement = reviewElement.querySelector('[data-testid="reviewText"]');
            const reviewText = textElement ? textElement.textContent.trim() : '';

            // Extract review date
            const dateElement = reviewElement.querySelector('.Text__body3');
            const reviewDate = dateElement ? dateElement.textContent.trim() : '';

            pageReviews.push({
              reviewer_name: reviewerName,
              rating: rating,
              review_text: reviewText,
              review_date: reviewDate
            });
          } catch (error) {
            console.error('Error extracting review:', error);
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

    while (reviews.length < limit && pageNum <= config.sources.goodreads.maxPages && clickAttempts < maxClickAttempts) {
      logger.info(`Attempting to load more reviews (page ${pageNum}), current count: ${reviews.length}`);

      // FIXED SELECTOR: Use XPath to find the button by its text content
      const moreReviewsButton = await page.$x("//button[contains(., 'Show more reviews') or contains(., 'More reviews')]");
      
      if (moreReviewsButton.length > 0) {
        logger.info(`✅ Found "Show more reviews" button using XPath`);
        
        try {
          // Click the button
          await moreReviewsButton[0].click();
          logger.info(`Clicked "Show more reviews" button`);
          
          // Wait for new content to load
          await page.waitForTimeout(2000);
          
          // Extract reviews from the updated page
          const newReviews = await extractReviewsFromPage();
          logger.info(`After clicking, found ${newReviews.length} reviews on page`);
          
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
        } catch (error) {
          logger.error(`Error clicking "Show more reviews" button: ${error.message}`);
          clickAttempts++;
        }
      } else {
        logger.info(`⚠️ No "Show more reviews" button found, attempt ${clickAttempts + 1}/${maxClickAttempts}`);
        
        // Try alternative button text
        const loadMoreButton = await page.$x("//button[contains(., 'Load more')]");
        if (loadMoreButton.length > 0) {
          logger.info(`✅ Found "Load more" button using XPath`);
          try {
            await loadMoreButton[0].click();
            logger.info(`Clicked "Load more" button`);
            await page.waitForTimeout(2000);
          } catch (error) {
            logger.error(`Error clicking "Load more" button: ${error.message}`);
          }
        }
        
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
EOT;

file_put_contents($tempFile, $fixedCode);

// Now, we need to upload this file to the VPS server
// Since we can't use SSH directly, we'll create a simple script that can be run on the VPS
// to update the file

$updateScript = <<<'EOT'
#!/bin/bash

# Path to the Goodreads scraper file
SCRAPER_FILE="/opt/book-scraper/stories-backend/services/HeadlessBrowser/scrapers/goodreads.js"

# Backup the original file
cp "$SCRAPER_FILE" "$SCRAPER_FILE.bak"

# Replace the file with the fixed version
cat > "$SCRAPER_FILE" << 'EOSCRIPT'
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
  const match = url.match(/\/book\/show\/(\d+)(?:[.-]|$)/);
  return match ? match[1] : null;
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
          try {
            // Extract reviewer name
            const nameElement = reviewElement.querySelector('[data-testid="user-profile-link"]');
            const reviewerName = nameElement ? nameElement.textContent.trim() : 'Unknown Reviewer';

            // Extract rating
            const ratingElement = reviewElement.querySelector('[data-testid="rating-stars"]');
            let rating = 0;
            if (ratingElement) {
              const ariaLabel = ratingElement.getAttribute('aria-label');
              if (ariaLabel && ariaLabel.includes('out of 5')) {
                rating = parseFloat(ariaLabel.match(/([0-9.]+) out of 5/)[1]);
              }
            }

            // Extract review text
            const textElement = reviewElement.querySelector('[data-testid="reviewText"]');
            const reviewText = textElement ? textElement.textContent.trim() : '';

            // Extract review date
            const dateElement = reviewElement.querySelector('.Text__body3');
            const reviewDate = dateElement ? dateElement.textContent.trim() : '';

            pageReviews.push({
              reviewer_name: reviewerName,
              rating: rating,
              review_text: reviewText,
              review_date: reviewDate
            });
          } catch (error) {
            console.error('Error extracting review:', error);
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

    while (reviews.length < limit && pageNum <= config.sources.goodreads.maxPages && clickAttempts < maxClickAttempts) {
      logger.info(`Attempting to load more reviews (page ${pageNum}), current count: ${reviews.length}`);

      // FIXED SELECTOR: Use XPath to find the button by its text content
      const moreReviewsButton = await page.$x("//button[contains(., 'Show more reviews') or contains(., 'More reviews')]");
      
      if (moreReviewsButton.length > 0) {
        logger.info(`✅ Found "Show more reviews" button using XPath`);
        
        try {
          // Click the button
          await moreReviewsButton[0].click();
          logger.info(`Clicked "Show more reviews" button`);
          
          // Wait for new content to load
          await page.waitForTimeout(2000);
          
          // Extract reviews from the updated page
          const newReviews = await extractReviewsFromPage();
          logger.info(`After clicking, found ${newReviews.length} reviews on page`);
          
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
        } catch (error) {
          logger.error(`Error clicking "Show more reviews" button: ${error.message}`);
          clickAttempts++;
        }
      } else {
        logger.info(`⚠️ No "Show more reviews" button found, attempt ${clickAttempts + 1}/${maxClickAttempts}`);
        
        // Try alternative button text
        const loadMoreButton = await page.$x("//button[contains(., 'Load more')]");
        if (loadMoreButton.length > 0) {
          logger.info(`✅ Found "Load more" button using XPath`);
          try {
            await loadMoreButton[0].click();
            logger.info(`Clicked "Load more" button`);
            await page.waitForTimeout(2000);
          } catch (error) {
            logger.error(`Error clicking "Load more" button: ${error.message}`);
          }
        }
        
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
EOSCRIPT

# Restart the scraper service
cd /opt/book-scraper && pm2 restart all

echo "Goodreads scraper updated and service restarted."
EOT;

// Create a temporary file for the update script
$updateScriptFile = tempnam(sys_get_temp_dir(), 'update_script_');
file_put_contents($updateScriptFile, $updateScript);

// Now, we need to create a PHP script that can be run on the VPS server
// to execute the update script
$phpScript = <<<EOT
<?php
// Execute the update script
\$output = shell_exec('bash /tmp/update_script.sh 2>&1');
echo \$output;
EOT;

// Create a temporary file for the PHP script
$phpScriptFile = tempnam(sys_get_temp_dir(), 'php_script_');
file_put_contents($phpScriptFile, $phpScript);

// Now, we need to upload these files to the VPS server
// We'll use curl to do this
echo "Uploading update script to VPS server...\n";

// First, upload the update script
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://{$vpsIp}:{$vpsPort}/upload-script");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new CURLFile($updateScriptFile, 'text/plain', 'update_script.sh'),
    'api_key' => $apiKey
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Error uploading update script: HTTP Status Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";
    
    echo "Since we can't upload the script directly, here's what you need to do manually:\n\n";
    echo "1. SSH into the VPS: ssh root@{$vpsIp}\n";
    echo "2. Create a new file: nano /opt/book-scraper/stories-backend/services/HeadlessBrowser/scrapers/goodreads.js\n";
    echo "3. Replace the entire content with the fixed code below:\n\n";
    echo file_get_contents($tempFile) . "\n\n";
    echo "4. Save the file (Ctrl+X, Y, Enter)\n";
    echo "5. Restart the scraper: cd /opt/book-scraper && pm2 restart all\n";
} else {
    echo "Update script uploaded successfully!\n\n";
    
    // Now, execute the update script
    echo "Executing update script...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://{$vpsIp}:{$vpsPort}/execute-script");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'script' => 'update_script.sh',
        'api_key' => $apiKey
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "Error executing update script: HTTP Status Code: {$httpCode}\n";
        echo "Response: {$response}\n\n";
    } else {
        echo "Update script executed successfully!\n";
        echo "Response: {$response}\n\n";
    }
}

// Clean up temporary files
unlink($tempFile);
unlink($updateScriptFile);
unlink($phpScriptFile);

echo "Fix Complete\n";
echo "===========\n";
echo "Next steps:\n";
echo "1. Test the Goodreads scraper: https://api.storiesfromtheweb.org/test-goodreads-scraper.php?isbn=9780007416851&limit=50\n";
echo "2. If it still doesn't work, check the logs: https://api.storiesfromtheweb.org/check-vps-logs.php\n";
