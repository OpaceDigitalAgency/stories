/**
 * Goodreads scraper main module
 */
const browser = require('../../utils/browser');
const logger = require('../../utils/logger');
const { makeGraphQLRequest } = require('./graphql');
const { checkCache, saveToCache } = require('./cache');

/**
 * Extract work ID from HTML content
 * @param {string} html - HTML content
 * @returns {string|null} Work ID
 */
function extractWorkId(html) {
  const workIdMatch = html.match(/kca:\/\/work\/amzn1\.gr\.work\.v1\.[a-zA-Z0-9]+/);
  if (workIdMatch) {
    const workId = workIdMatch[0].replace('kca://', '');
    logger.info(`Extracted work ID: ${workId}`);
    return workId;
  }
  logger.warn('Could not extract work ID from HTML');
  return null;
}

/**
 * Extract book ID from Goodreads URL
 * @param {string} url - Goodreads book URL
 * @returns {string|null} Book ID
 */
function extractBookId(url) {
  // Try ISBN URL format
  const isbnMatch = url.match(/\/book\/isbn\/(\d+)/);
  if (isbnMatch?.[1]) {
    logger.info(`Extracted ISBN ${isbnMatch[1]} from URL`);
    return isbnMatch[1];
  }

  // Try show URL format
  const showMatch = url.match(/\/book\/show\/(\d+)(?:[.-]|$)/);
  if (showMatch?.[1]) {
    logger.info(`Extracted book ID ${showMatch[1]} from URL`);
    return showMatch[1];
  }

  logger.warn(`Could not extract book ID from URL: ${url}`);
  return null;
}

/**
 * Scrape reviews and metadata from Goodreads
 * @param {string} goodreadsUrl - URL of the book page
 * @param {number} limit - Maximum number of reviews to fetch
 * @param {Object} options - Scraping options
 */
async function scrapeGoodreadsReviews(goodreadsUrl, limit = 50, options = {}) {
  // Fix force parameter handling - accept boolean true, string 'true', or '1'
  // Convert the force parameter to a boolean to ensure consistent handling
  const force = options.force === true || options.force === 'true' || options.force === '1' || options.force === 1;
  const envForce = process.env.VPS_BYPASS_CACHE === 'true' || process.env.FORCE_FRESH_DATA === 'true';
  const forceFinal = force || envForce;
  const maxPages = options.maxPages ?? 100;
  const continueFromLast = options.continueFromLast ?? false;

  // Log force parameter sources for debugging
  logger.info(`Force refresh sources:
    - Passed from options: ${force} (raw value: ${JSON.stringify(options.force)})
    - Environment variables: ${envForce}
    - Final force value: ${forceFinal}
  `);

  // Initialize steps array for detailed logging
  const steps = [];
  const addStep = (name, status, message, details = {}) => {
    steps.push({
      name,
      status,
      message,
      fetch_url: details.url,
      response: details.response_length,
      details
    });
    logger.info(`[${name}] ${status}: ${message}`);
  };

  addStep('scrape_start', 'info', 'Starting Goodreads scrape', {
    url: goodreadsUrl,
    limit,
    maxPages,
    continueFromLast,
    force: forceFinal
  });

  // Extract book ID for caching
  const bookId = extractBookId(goodreadsUrl);
  if (!bookId) {
    throw new Error(`Invalid Goodreads URL: ${goodreadsUrl}`);
  }

  // Check cache with detailed logging
  logger.info(`Checking cache for book ${bookId}`);
  logger.info(`Force refresh: ${forceFinal}`);
  const cachedData = await checkCache(bookId, { force: forceFinal, continueFromLast, limit });

  // Handle cache result
  if (cachedData && !forceFinal) {
    logger.info(`Using cached data for book ${bookId}`);

    if (continueFromLast) {
      logger.info(`Using cached data for continuation`);
      return { ...cachedData, source: 'cache' };
    }

    const hasEnoughReviews = cachedData.reviews?.length >= limit;
    if (hasEnoughReviews) {
      logger.info(`Cached reviews sufficient: ${cachedData.reviews.length} >= ${limit}`);
      return { ...cachedData, source: 'cache' };
    }

    logger.info(`Cached reviews insufficient: ${cachedData.reviews?.length} < ${limit}`);
  } else if (cachedData && forceFinal) {
    logger.info(`Force refresh requested - skipping cache`);
  } else {
    logger.info(`No cached data found for book ${bookId} - proceeding`);
  }

  // Initialize browser and navigate to page
  const page = await browser.getNewPage();
  try {
    logger.info(`Navigating to ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Take screenshot for debugging
    await browser.takeScreenshot(page, `goodreads-initial-${bookId}`);

    // Extract metadata from HTML with detailed logging
    logger.info('Extracting metadata from page HTML...');
    // Inline metadata extraction logic with selector fallbacks
    const metadata = await page.evaluate(() => {
      function extractWithFallbacks(selectorObj) {
        const { primary, fallbacks } = selectorObj;
        let element = document.querySelector(primary);
        if (!element && fallbacks) {
          for (const fallback of fallbacks) {
            element = document.querySelector(fallback);
            if (element) break;
          }
        }
        return element ? element.textContent.trim() : null;
      }

      function cleanText(text) {
        if (!text) return '';
        return text.trim().replace(/\s+/g, ' ').replace(/[\u200B-\u200D\uFEFF]/g, '');
      }

      const SELECTORS = {
        title: {
          primary: '[data-testid="bookTitle"]',
          fallbacks: ['h1.BookPageTitleSection__title', '.BookPageTitleSection h1']
        },
        author: {
          primary: '[data-testid="authorLink"]',
          fallbacks: ['.BookPageTitleSection__authorLink', '.AuthorLink__name', 'span.ContributorLink__name']
        },
        rating: {
          primary: '[data-testid="averageRating"]',
          fallbacks: ['.RatingStatistics__rating', '.RatingStars__average', '.BookRatingStars [aria-label*="rating"]']
        },
        description: {
          primary: '[data-testid="description"] span',
          fallbacks: ['.DetailsLayoutRightParagraph__widthConstrained']
        },
        genres: {
          primary: '[data-testid="genresList"] a',
          fallbacks: ['.BookPageMetadataSection__genreShelf', '.bookPageGenreLink', 'span.BookPageMetadataSection__genreButton']
        },
        isbn: {
          primary: '.FeatureItem:contains("ISBN") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("ISBN") + span.BookDetails__value']
        },
        isbn13: {
          primary: '.FeatureItem:contains("ISBN13") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("ISBN13") + span.BookDetails__value']
        },
        publisher: {
          primary: '.FeatureItem:contains("Publisher") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("Publisher") + span.BookDetails__value']
        },
        language: {
          primary: '.FeatureItem:contains("Language") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("Language") + span.BookDetails__value']
        },
        format: {
          primary: '.FeatureItem:contains("Format") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("Format") + span.BookDetails__value']
        },
        pages: {
          primary: '.FeatureItem:contains("Pages") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("Pages") + span.BookDetails__value', '.BookDetails__list span.BookDetails__label:contains("Length") + span.BookDetails__value']
        },
        publication_date: {
          primary: '.FeatureItem:contains("Published") .FeatureItem__value',
          fallbacks: ['.BookDetails__list span.BookDetails__label:contains("Published") + span.BookDetails__value']
        },
        series: {
          primary: '.BookPageTitleSection__series a',
          fallbacks: ['.SeriesLink']
        },
        awards: {
          primary: '[href*="ref=nav_brws_gca"]',
          fallbacks: []
        },
        characters: {
          primary: '.BookDetails__list span.BookDetails__label:contains("Characters") + span.BookDetails__value',
          fallbacks: []
        },
        settings: {
          primary: '.BookDetails__list span.BookDetails__label:contains("Setting") + span.BookDetails__value',
          fallbacks: []
        },
        rating_count: {
          primary: '[data-testid="ratingsCount"]',
          fallbacks: ['.RatingStatistics__meta span:first-child']
        },
        review_count: {
          primary: '[data-testid="reviewsCount"]',
          fallbacks: ['.RatingStatistics__meta span:last-child']
        },
        cover_image: {
          primary: '[data-testid="bookCover"] img',
          fallbacks: ['img.ResponsiveImage', '.BookCover__image img']
        }
      };

      const metadata = {
        title: '',
        author: '',
        rating: null,
        description: '',
        genres: [],
        isbn: '',
        isbn13: '',
        publisher: '',
        language: '',
        format: '',
        pages: '',
        publication_date: '',
        series: '',
        awards: [],
        characters: '',
        settings: '',
        rating_count: '',
        review_count: '',
        cover_image: ''
      };

      for (const [field, selectors] of Object.entries(SELECTORS)) {
        if (typeof selectors !== 'object' || typeof selectors.primary !== 'string') {
          console.warn('Invalid selector configuration for field:', field, selectors);
          continue;
        }

        const rawValue = extractWithFallbacks(selectors);
        if (!rawValue) continue;
        const value = cleanText(rawValue);

        switch (field) {
          case 'rating':
            const ratingMatch = value.match(/(\d+\.?\d*)/);
            metadata.rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;
            break;
          case 'genres':
            metadata.genres = value.split(/[,;]/).map(item => cleanText(item)).filter(item => item.length > 0);
            break;
          case 'pages':
            // Extract only the number from the pages value
            const pagesMatch = value.match(/(\d+)/);
            metadata.pages = pagesMatch ? pagesMatch[1] : value;
            break;
          case 'publication_date':
            // Try to parse the date into a standardized format (YYYY-MM-DD)
            try {
              const dateStr = value.replace(/[^\w\s,]/g, ' ').trim();
              const date = new Date(dateStr);
              if (!isNaN(date.getTime())) {
                metadata.publication_date = date.toISOString().split('T')[0];
              } else {
                metadata.publication_date = value;
              }
            } catch (e) {
              metadata.publication_date = value;
            }
            break;
          case 'awards':
            metadata.awards = value.split(/[,;]/).map(item => cleanText(item)).filter(item => item.length > 0);
            break;
          case 'cover_image':
            // For cover image, we need to extract the src attribute
            const imgElement = document.querySelector(selectors.primary) ||
                              (selectors.fallbacks && selectors.fallbacks.map(sel => document.querySelector(sel)).find(el => el));
            if (imgElement && imgElement.tagName === 'IMG') {
              metadata.cover_image = imgElement.src;
            }
            break;
          case 'rating_count':
          case 'review_count':
            // Extract only the number from the count
            const countMatch = value.match(/(\d+(?:,\d+)*)/);
            if (countMatch) {
              metadata[field] = countMatch[1].replace(/,/g, '');
            } else {
              metadata[field] = value;
            }
            break;
          default:
            metadata[field] = value;
        }
      }

      return metadata;
    });

    if (!metadata) {
      addStep('metadata_extraction', 'error', 'Failed to extract metadata from page');
      throw new Error('Failed to extract metadata from page');
    }

    // Log extracted metadata with raw values for debugging
    addStep('metadata_extraction', 'success', 'Successfully extracted metadata', {
      metadata: {
        ...metadata,
        _raw: metadata._raw
      }
    });

    // Validate required fields
    const requiredFields = ['title', 'author'];
    const missingFields = requiredFields.filter(field => !metadata[field]);
    if (missingFields.length > 0) {
      addStep('metadata_validation', 'error', `Missing required fields: ${missingFields.join(', ')}`);
      throw new Error(`Missing required metadata fields: ${missingFields.join(', ')}`);
    }

    // Extract work ID for GraphQL queries
    addStep('work_id_extraction', 'in_progress', 'Extracting work ID for GraphQL queries');
    const html = await page.content();
    const workId = extractWorkId(html);
    if (!workId) {
      addStep('work_id_extraction', 'error', 'Failed to extract work ID');
      throw new Error('Could not extract work ID');
    }
    addStep('work_id_extraction', 'success', 'Successfully extracted work ID', { workId });

    // Initialize reviews array with cached reviews if continuing
    let reviews = cachedData?.reviews || [];
    let nextCursor = cachedData?.graphql_state?.next_token;
    let hasMore = true;
    let pageCount = cachedData?.graphql_state?.current_page || 0;

    // Fetch reviews via GraphQL
    while (hasMore && pageCount < maxPages && (continueFromLast || reviews.length < limit)) {
      pageCount++;
      addStep('graphql_request', 'in_progress', `Fetching GraphQL page ${pageCount}/${maxPages}`, {
        workId,
        cursor: nextCursor
      });

      const result = await makeGraphQLRequest(page, workId, nextCursor);
      if (!result) {
        addStep('graphql_request', 'error', 'GraphQL request failed');
        break;
      }

      addStep('graphql_request', 'success', 'Successfully fetched GraphQL data', {
        new_reviews: result.reviews.length,
        has_more: result.hasMore,
        metadata_updated: Object.keys(result.metadata || {}).length > 0
      });

      // Merge metadata
      Object.assign(metadata, result.metadata);

      // Add new reviews
      const newReviews = result.reviews.filter(review => {
        // Skip duplicates when continuing from cache
        if (continueFromLast) {
          return !reviews.some(existing =>
            existing.reviewer_name === review.reviewer_name &&
            existing.review_text.substring(0, 50) === review.review_text.substring(0, 50)
          );
        }
        return true;
      });

      reviews = [...reviews, ...newReviews];
      logger.info(`Added ${newReviews.length} reviews, total: ${reviews.length}`);

      hasMore = result.hasMore;
      nextCursor = result.nextCursor;

      // Add small delay between requests
      await page.waitForTimeout(1000);
    }

    // Prepare final result
    const result = {
      source: 'scrape',
      ...metadata,
      reviews: reviews.slice(0, limit),
      total: reviews.length,
      hasMoreReviews: hasMore || reviews.length > limit,
      graphql_state: {
        current_page: pageCount,
        next_token: nextCursor,
        total_fetched: reviews.length
      }
    };

    // Save to cache
    addStep('cache_save', 'in_progress', 'Saving results to cache');
    await saveToCache(bookId, result);
    addStep('cache_save', 'success', 'Successfully saved to cache', {
      reviews_count: result.reviews.length,
      metadata_fields: Object.keys(result).filter(k => k !== 'reviews').length
    });

    // Add steps to result
    result.steps = steps;
    return result;

  } catch (error) {
    logger.error(`Error scraping Goodreads: ${error.message}`);
    throw error;
  } finally {
    await page.close();
  }
}

module.exports = {
  scrapeGoodreadsReviews
};