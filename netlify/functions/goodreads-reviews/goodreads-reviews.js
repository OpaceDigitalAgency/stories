const chromium = require('@sparticuz/chromium');
const puppeteer = require('puppeteer-core');

// Main handler function
exports.handler = async (event, context) => {
  // Only allow POST requests
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      body: JSON.stringify({ error: 'Method Not Allowed' }),
    };
  }

  let browser = null;

  try {
    // Parse request body
    const body = JSON.parse(event.body);
    const { goodreadsUrl, limit = 50, maxPages = 10 } = body;

    if (!goodreadsUrl) {
      return {
        statusCode: 400,
        body: JSON.stringify({ error: 'Goodreads URL is required' }),
      };
    }

    console.log(`Starting Goodreads scraping for URL: ${goodreadsUrl}`);
    console.log(`Requested limit: ${limit}, Max pages: ${maxPages}`);

    // Launch browser with more robust error handling
    try {
      console.log("Launching browser with Chromium");
      browser = await puppeteer.launch({
        args: chromium.args,
        defaultViewport: chromium.defaultViewport,
        executablePath: await chromium.executablePath(),
        headless: true,
        ignoreHTTPSErrors: true,
      });
      console.log("Browser launched successfully");
    } catch (error) {
      console.log("Error launching browser:", error.message);
      console.log("Attempting fallback launch method");

      // Fallback to a more basic configuration
      browser = await puppeteer.launch({
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--disable-accelerated-2d-canvas',
          '--no-first-run',
          '--no-zygote',
          '--single-process',
          '--disable-gpu'
        ],
        headless: true,
        ignoreHTTPSErrors: true,
      });
      console.log("Browser launched with fallback method");
    }

    // Create a new page
    const page = await browser.newPage();

    // Set a realistic user agent
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    // Navigate to the Goodreads reviews page
    console.log(`Navigating to: ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 10000 });

    // Extract the book title for reference
    let bookTitle = await page.evaluate(() => {
      const titleElement = document.querySelector('h1.Text__title1');
      return titleElement ? titleElement.innerText.trim() : 'Unknown Book';
    });

    console.log(`Book title: ${bookTitle}`);

    // Extract aggregate rating
    const aggregateRating = await extractAggregateRating(page);

    // Initialize reviews array
    let reviews = [];

    // Extract initial reviews
    console.log('Extracting initial reviews...');
    let initialReviews = await extractReviews(page);
    reviews = [...reviews, ...initialReviews];
    console.log(`Found ${initialReviews.length} reviews on first page`);

    // Click "Show more reviews" button and extract more reviews
    let currentPage = 1;
    let hasMoreReviews = true;

    // Try to find pagination links first
    const hasPagination = await page.evaluate(() => {
      return document.querySelector('a.next_page') !== null ||
             document.querySelector('a[href*="page="]') !== null;
    });

    console.log(`Pagination links found: ${hasPagination}`);

    // If we have pagination links, use them for better reliability
    if (hasPagination) {
      while (reviews.length < limit && currentPage < maxPages && hasMoreReviews) {
        try {
          // Look for pagination links
          const nextPageUrl = await page.evaluate(() => {
            const nextPageLink = document.querySelector('a.next_page') ||
                                document.querySelector('a[href*="page="]');
            return nextPageLink ? nextPageLink.href : null;
          });

          if (!nextPageUrl) {
            console.log('No more pagination links found');
            hasMoreReviews = false;
            break;
          }

          // Navigate to the next page
          currentPage++;
          console.log(`Navigating to page ${currentPage}: ${nextPageUrl}`);
          await page.goto(nextPageUrl, { waitUntil: 'networkidle2', timeout: 10000 });

          // Extract reviews from the new page
          console.log(`Extracting reviews from page ${currentPage}...`);
          const newReviews = await extractReviews(page);

          // Check if we got new reviews
          if (newReviews.length === 0) {
            console.log('No new reviews found, stopping pagination');
            hasMoreReviews = false;
            break;
          }

          // Add new reviews to the collection
          reviews = [...reviews, ...newReviews];
          console.log(`Total reviews collected: ${reviews.length}`);

          // Add a small delay between pages to avoid rate limiting
          await page.waitForTimeout(1500);
        } catch (err) {
          console.log(`Error navigating to next page: ${err.message}`);
          hasMoreReviews = false;
          break;
        }
      }
    } else {
      // Fall back to clicking "Show more reviews" button
      while (reviews.length < limit && currentPage < maxPages && hasMoreReviews) {
        try {
          // Look for "Show more reviews" button
          const showMoreButton = await page.evaluate(() => {
            // Try different selectors for the "Show more" button
            const selectors = [
              'button[data-testid="loadMore"]',
              'button.Button--secondary:not([disabled])',
              'button:contains("Show more reviews")',
              'a:contains("Show more reviews")'
            ];

            for (const selector of selectors) {
              const button = document.querySelector(selector);
              if (button && button.offsetParent !== null) {
                return true;
              }
            }
            return false;
          });

          if (!showMoreButton) {
            console.log('No more "Show more reviews" button found');
            hasMoreReviews = false;
            break;
          }

          // Click the button
          console.log('Clicking "Show more reviews" button...');
          await page.evaluate(() => {
            const selectors = [
              'button[data-testid="loadMore"]',
              'button.Button--secondary:not([disabled])',
              'button:contains("Show more reviews")',
              'a:contains("Show more reviews")'
            ];

            for (const selector of selectors) {
              const button = document.querySelector(selector);
              if (button && button.offsetParent !== null) {
                button.click();
                return;
              }
            }
          });

          // Wait for new reviews to load
          await page.waitForTimeout(3000);

          // Extract reviews from the updated page
          currentPage++;
          console.log(`Extracting reviews from page ${currentPage}...`);
          const newReviews = await extractReviews(page);

          // Check if we got new reviews
          if (newReviews.length === 0) {
            console.log('No new reviews found, stopping pagination');
            hasMoreReviews = false;
            break;
          }

          // Add new reviews to the collection
          reviews = [...reviews, ...newReviews];
          console.log(`Total reviews collected: ${reviews.length}`);

          // Add a small delay between pages to avoid rate limiting
          await page.waitForTimeout(1500);
        } catch (err) {
          console.log(`Error loading more reviews: ${err.message}`);
          hasMoreReviews = false;
          break;
        }
      }
    }

    // If we have an aggregate rating, add it to the beginning of the reviews array
    if (aggregateRating) {
      reviews.unshift(aggregateRating);
    }

    // Return reviews (limited to requested amount)
    return {
      statusCode: 200,
      body: JSON.stringify({
        reviews: reviews.slice(0, limit),
        total: reviews.length,
        book_title: bookTitle
      }),
    };
  } catch (err) {
    console.log(`Error: ${err.message}`);
    return {
      statusCode: 500,
      body: JSON.stringify({ error: err.message }),
    };
  } finally {
    if (browser !== null) {
      await browser.close();
    }
  }
};

// Function to extract reviews from the current page
async function extractReviews(page) {
  return await page.evaluate(() => {
    const reviews = [];

    // Try multiple selectors for review containers
    const reviewSelectors = [
      'article.ReviewCard',
      'div.review[id^="review_"]',
      'div[class*="review"][id^="review_"]',
      'article[class*="review"][id^="review_"]',
      'div.ReviewsList__item'
    ];

    let reviewElements = [];

    // Try each selector
    for (const selector of reviewSelectors) {
      const elements = document.querySelectorAll(selector);
      if (elements.length > 0) {
        reviewElements = [...elements];
        break;
      }
    }

    // Process each review element
    reviewElements.forEach(reviewElement => {
      try {
        // Extract reviewer name
        let reviewerName = 'Goodreads User';

        // Try different selectors for reviewer name
        const nameSelectors = [
          'a[data-testid="user-profile-link"]',
          'a.user',
          'a[class*="reviewer"]',
          'span[class*="reviewer"]',
          'div.ReviewCard__profile a'
        ];

        for (const selector of nameSelectors) {
          const nameElement = reviewElement.querySelector(selector);
          if (nameElement && nameElement.textContent.trim()) {
            reviewerName = nameElement.textContent.trim();
            break;
          }
        }

        // If still generic, generate a unique name
        if (reviewerName === 'Goodreads User') {
          const uniqueId = Math.random().toString(36).substring(2, 10);
          reviewerName = `Goodreads User #${uniqueId}`;
        }

        // Extract rating
        let rating = 0;

        // Try different selectors for rating
        const ratingSelectors = [
          'span[aria-label*="Rating"]',
          'span[data-testid="rating-stars"]',
          'span.static-stars[title]',
          'span[data-rating]',
          'span[class*="p10"]',
          'span[class*="p8"]',
          'span[class*="p6"]',
          'span[class*="p4"]',
          'span[class*="p2"]'
        ];

        for (const selector of ratingSelectors) {
          const ratingElement = reviewElement.querySelector(selector);
          if (ratingElement) {
            if (ratingElement.getAttribute('aria-label') && ratingElement.getAttribute('aria-label').includes('Rating')) {
              const match = ratingElement.getAttribute('aria-label').match(/Rating ([0-9.]+) out of 5/);
              if (match) {
                rating = parseFloat(match[1]);
                break;
              }
            } else if (ratingElement.getAttribute('data-rating')) {
              rating = parseInt(ratingElement.getAttribute('data-rating'));
              break;
            } else if (ratingElement.getAttribute('title') && ratingElement.getAttribute('title').includes('stars')) {
              const match = ratingElement.getAttribute('title').match(/(\d+)/);
              if (match) {
                rating = parseInt(match[1]);
                break;
              }
            } else if (ratingElement.classList.contains('p10')) {
              rating = 5;
              break;
            } else if (ratingElement.classList.contains('p8')) {
              rating = 4;
              break;
            } else if (ratingElement.classList.contains('p6')) {
              rating = 3;
              break;
            } else if (ratingElement.classList.contains('p4')) {
              rating = 2;
              break;
            } else if (ratingElement.classList.contains('p2')) {
              rating = 1;
              break;
            }
          }
        }

        // Extract review text
        let reviewText = '';

        // Try different selectors for review text
        const textSelectors = [
          'div.TruncatedContent__text--large',
          'div[data-testid="contentContainer"]',
          'span.Formatted',
          'div.Formatted',
          'div[data-testid="reviewText"]',
          'div.reviewText span',
          'div[class*="reviewText"]',
          'div[class*="reviewContent"]'
        ];

        for (const selector of textSelectors) {
          const textElement = reviewElement.querySelector(selector);
          if (textElement && textElement.textContent.trim()) {
            reviewText = textElement.textContent.trim();
            break;
          }
        }

        // Extract review date
        let reviewDate = null;

        // Try different selectors for review date
        const dateSelectors = [
          'div[data-testid="reviewDate"]',
          'a.reviewDate',
          'time[datetime]',
          'span[class*="reviewDate"]'
        ];

        for (const selector of dateSelectors) {
          const dateElement = reviewElement.querySelector(selector);
          if (dateElement) {
            if (dateElement.getAttribute('datetime')) {
              reviewDate = dateElement.getAttribute('datetime').substring(0, 10);
              break;
            } else if (dateElement.textContent.trim()) {
              // Simple date parsing - in a real implementation, you'd want more robust parsing
              reviewDate = dateElement.textContent.trim();
              break;
            }
          }
        }

        // Use current date if no date found
        if (!reviewDate) {
          const now = new Date();
          reviewDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        }

        // Skip reviews without text or rating
        if (!reviewText || rating === 0) {
          return;
        }

        // Get review ID
        let reviewId = '';
        if (reviewElement.id && reviewElement.id.startsWith('review_')) {
          reviewId = reviewElement.id.replace('review_', '');
        } else {
          reviewId = `modern_${Math.random().toString(36).substring(2, 15)}`;
        }

        // Add review to collection
        reviews.push({
          source_id: 1, // Goodreads source ID
          reviewer_name: reviewerName,
          reviewer_age: null,
          review_date: reviewDate,
          original_rating: `${rating}/5`,
          rating_value: rating,
          rating_scale: 5,
          rating_normalised: rating / 5,
          review_text: reviewText,
          metadata: JSON.stringify({
            review_id: reviewId,
            is_synthetic: false
          })
        });
      } catch (err) {
        console.log(`Error extracting review: ${err.message}`);
      }
    });

    return reviews;
  });
}

// Function to extract aggregate rating
async function extractAggregateRating(page) {
  try {
    return await page.evaluate(() => {
      let rating = 0;
      let ratingCount = 0;
      let reviewCount = 0;

      // Try modern layout
      const ratingElement = document.querySelector('div.RatingStatistics__rating');
      if (ratingElement && ratingElement.textContent) {
        rating = parseFloat(ratingElement.textContent.trim());

        // Try to get ratings count
        const ratingsCountElement = document.querySelector('span[data-testid="ratingsCount"]');
        if (ratingsCountElement) {
          const text = ratingsCountElement.textContent.trim();
          ratingCount = parseInt(text.replace(/[^0-9]/g, ''));
        }

        // Try to get reviews count
        const reviewsCountElement = document.querySelector('span[data-testid="reviewsCount"]');
        if (reviewsCountElement) {
          const text = reviewsCountElement.textContent.trim();
          reviewCount = parseInt(text.replace(/[^0-9]/g, ''));
        }
      } else {
        // Try classic layout
        const ratingValueElement = document.querySelector('span[itemprop="ratingValue"]');
        if (ratingValueElement) {
          rating = parseFloat(ratingValueElement.textContent.trim());

          // Try to get ratings count
          const ratingCountElement = document.querySelector('meta[itemprop="ratingCount"]');
          if (ratingCountElement) {
            ratingCount = parseInt(ratingCountElement.getAttribute('content'));
          }

          // Try to get reviews count
          const reviewCountElement = document.querySelector('meta[itemprop="reviewCount"]');
          if (reviewCountElement) {
            reviewCount = parseInt(reviewCountElement.getAttribute('content'));
          }
        }
      }

      if (rating > 0) {
        return {
          source_id: 1, // Goodreads source ID
          reviewer_name: "Goodreads Aggregate",
          reviewer_age: null,
          review_date: new Date().toISOString().substring(0, 10),
          original_rating: `${rating}/5`,
          rating_value: rating,
          rating_scale: 5,
          rating_normalised: rating / 5,
          review_text: `This book has an average rating of ${rating}/5 based on ${ratingCount} ratings and ${reviewCount} reviews on Goodreads.`,
          metadata: JSON.stringify({
            is_synthetic: false,
            is_aggregate: true,
            ratings_count: ratingCount,
            reviews_count: reviewCount
          })
        };
      }

      return null;
    });
  } catch (err) {
    console.log(`Error extracting aggregate rating: ${err.message}`);
    return null;
  }
}
