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
  // Method 1: Try to extract from kca:// format
  const kcaMatch = html.match(/kca:\/\/work\/amzn1\.gr\.work\.v1\.[a-zA-Z0-9_-]+/);
  if (kcaMatch) {
    const workId = kcaMatch[0].replace('kca://', '');
    logger.info(`Extracted work ID (kca format): ${workId}`);
    return workId;
  }

  // Method 2: Try to extract from data-work-id attribute
  const dataWorkIdMatch = html.match(/data-work-id="([^"]+)"/);
  if (dataWorkIdMatch && dataWorkIdMatch[1]) {
    logger.info(`Extracted work ID (data-work-id): ${dataWorkIdMatch[1]}`);
    return dataWorkIdMatch[1];
  }

  // Method 3: Try to extract from URL pattern
  const urlWorkIdMatch = html.match(/\/work\/(\d+)/);
  if (urlWorkIdMatch && urlWorkIdMatch[1]) {
    logger.info(`Extracted work ID (URL): ${urlWorkIdMatch[1]}`);
    return urlWorkIdMatch[1];
  }

  // Method 4: Try to extract from meta tags
  const metaMatch = html.match(/<meta[^>]*content="[^"]*work\/([^"\/]+)"/);
  if (metaMatch && metaMatch[1]) {
    logger.info(`Extracted work ID (meta): ${metaMatch[1]}`);
    return metaMatch[1];
  }

  logger.warn('Could not extract work ID from HTML using any method');
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
  
  // CRITICAL FIX: Handle pagination parameters from PHP
  const nextPageToken = options.nextPageToken || null;
  const startPage = options.startPage ? parseInt(options.startPage) : null;
  
  logger.info(`Pagination parameters from PHP:
    - nextPageToken: ${nextPageToken}
    - startPage: ${startPage}
    - continueFromLast: ${continueFromLast}
  `);

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

      // Helper function to clean field values
      function cleanFieldValue(value) {
        if (!value) return '';

        // First, handle JSON-like content
        if (typeof value === 'string' && value.startsWith('"') && value.includes('{"@type"')) {
          try {
            // Try to extract just the meaningful part from JSON
            const jsonMatch = value.match(/"name":"([^"]+)"/);
            if (jsonMatch && jsonMatch[1]) {
              return jsonMatch[1];
            }
          } catch (e) {
            // If JSON parsing fails, continue with other cleaning
          }
        }

        // Remove HTML attributes that cause display issues
        let cleaned = value;

        // Remove role="link" attribute
        cleaned = cleaned.replace(/\s+role="link"/g, '');

        // Remove ", link, opens in new tab" text completely
        cleaned = cleaned.replace(/, link, opens in new tab/g, '');

        // Remove other problematic attributes
        cleaned = cleaned.replace(/\s+data-testid="[^"]*"/g, '');
        cleaned = cleaned.replace(/\s+class="[^"]*"/g, '');
        cleaned = cleaned.replace(/\s+id="[^"]*"/g, '');
        cleaned = cleaned.replace(/\s+style="[^"]*"/g, '');
        cleaned = cleaned.replace(/\s+tabindex="[^"]*"/g, '');

        // Remove all HTML tags completely
        cleaned = cleaned.replace(/<[^>]*>/g, '');

        // Remove escaped unicode and other artifacts
        cleaned = cleaned.replace(/\\u\d+/g, '');
        cleaned = cleaned.replace(/^[?]ref=.*tabindex=.*>/, '');

        // Clean up series format like "The Worst Witch (#1)"
        if (cleaned.match(/^(.*?)\s*\(#\d+\)$/)) {
          cleaned = cleaned.replace(/\s*\(#\d+\)$/, '');
        }

        // Clean up whitespace
        cleaned = cleaned.replace(/\s+/g, ' ').trim();

        // Remove any remaining quotes at beginning/end
        cleaned = cleaned.replace(/^["']|["']$/g, '');

        return cleaned;
      }

      // Extract book details from the page
      function extractBookDetails() {
        const details = {};

        // Method 1: Try to extract from the table in the UI
        const rows = document.querySelectorAll('table tr');
        rows.forEach(row => {
          const cells = row.querySelectorAll('td');
          if (cells.length >= 2) {
            const label = cells[0].textContent.trim().toLowerCase();
            let value = cells[1].textContent.trim();

            // Clean the value
            value = cleanFieldValue(value);

            if (label.includes('isbn-10') || (label.includes('isbn') && !label.includes('13'))) {
              details.isbn = value;
            } else if (label.includes('isbn-13')) {
              details.isbn13 = value;
            } else if (label.includes('publisher')) {
              details.publisher = value;
            } else if (label.includes('language')) {
              details.language = value;
            } else if (label.includes('pages')) {
              details.pages = value;
            } else if (label.includes('published')) {
              details.publication_date = value;
            } else if (label.includes('format')) {
              details.format = value;
            } else if (label.includes('series')) {
              details.series = value;
            } else if (label.includes('characters')) {
              details.characters = value;
            } else if (label.includes('setting')) {
              details.settings = value;
            } else if (label.includes('author')) {
              details.author = value;
            }
          }
        });

        // Method 2: Try to extract from the book details section
        if (Object.keys(details).length === 0) {
          const detailsSection = document.querySelector('[data-testid="bookEditionDetails"]') ||
                                document.querySelector('.BookDetails__list') ||
                                document.querySelector('.FeatureItems');

          if (detailsSection) {
            // Process each detail item
            const detailItems = detailsSection.querySelectorAll('div.FeatureItem, div.BookDetails__item, div');

            detailItems.forEach(item => {
              const label = item.querySelector('.FeatureItem__label, .BookDetails__label, dt');
              const value = item.querySelector('.FeatureItem__value, .BookDetails__value, dd');

              if (!label || !value) return;

              const labelText = label.textContent.trim().toLowerCase();
              let valueText = value.textContent.trim();

              // Get the HTML content for links that might have attributes
              if (value.querySelector('a')) {
                valueText = value.innerHTML;
              }

              // Clean the value
              valueText = cleanFieldValue(valueText);

              if (labelText.includes('isbn') && !labelText.includes('13')) {
                details.isbn = valueText;
              } else if (labelText.includes('isbn13') || labelText.includes('isbn-13')) {
                details.isbn13 = valueText;
              } else if (labelText.includes('publisher')) {
                details.publisher = valueText;
              } else if (labelText.includes('language')) {
                details.language = valueText;
              } else if (labelText.includes('pages')) {
                details.pages = valueText;
              } else if (labelText.includes('published')) {
                details.publication_date = valueText;
              } else if (labelText.includes('format')) {
                details.format = valueText;
              } else if (labelText.includes('characters')) {
                details.characters = valueText;
              } else if (labelText.includes('setting')) {
                details.settings = valueText;
              } else if (labelText.includes('series')) {
                details.series = valueText;
              }
            });
          }
        }

        // Method 3: Try to extract from structured data in the page
        try {
          const scriptTags = document.querySelectorAll('script[type="application/ld+json"]');
          scriptTags.forEach(script => {
            try {
              const jsonData = JSON.parse(script.textContent);

              if (jsonData && jsonData['@type'] === 'Book') {
                if (jsonData.isbn && !details.isbn) {
                  details.isbn = jsonData.isbn;
                }
                if (jsonData.bookFormat && !details.format) {
                  details.format = jsonData.bookFormat;
                }
                if (jsonData.numberOfPages && !details.pages) {
                  details.pages = jsonData.numberOfPages.toString();
                }
                if (jsonData.inLanguage && !details.language) {
                  details.language = jsonData.inLanguage;
                }
                if (jsonData.publisher && jsonData.publisher.name && !details.publisher) {
                  details.publisher = jsonData.publisher.name;
                }
                if (jsonData.datePublished && !details.publication_date) {
                  details.publication_date = jsonData.datePublished;
                }
                if (jsonData.author && jsonData.author.name && !details.author) {
                  details.author = jsonData.author.name;
                }
              }
            } catch (e) {
              // Ignore JSON parse errors
            }
          });
        } catch (e) {
          // Ignore errors in structured data extraction
        }

        return details;
      }

      function cleanText(text) {
        if (!text) return '';
        return text.trim().replace(/\s+/g, ' ').replace(/[\u200B-\u200D\uFEFF]/g, '');
      }

      const SELECTORS = {
        title: {
          primary: '[data-testid="bookTitle"]',
          fallbacks: ['h1.BookPageTitleSection__title', '.BookPageTitleSection h1', 'h1']
        },
        author: {
          primary: '[data-testid="authorLink"]',
          fallbacks: ['.BookPageTitleSection__authorLink', '.AuthorLink__name', 'span.ContributorLink__name', 'a[href*="/author/show/"]']
        },
        rating: {
          primary: '[data-testid="averageRating"]',
          fallbacks: ['.RatingStatistics__rating', '.RatingStars__average', '.BookRatingStars [aria-label*="rating"]']
        },
        description: {
          primary: '[data-testid="description"] span',
          fallbacks: ['.DetailsLayoutRightParagraph__widthConstrained', '.BookPageMetadataSection__description']
        },
        genres: {
          primary: '[data-testid="genresList"] a',
          fallbacks: ['.BookPageMetadataSection__genreShelf a', '.bookPageGenreLink', 'span.BookPageMetadataSection__genreButton']
        },
        // We'll rely on extractBookDetails() for these fields instead of selectors
        isbn: {
          primary: 'table tr td',
          fallbacks: []
        },
        isbn13: {
          primary: 'table tr td',
          fallbacks: []
        },
        publisher: {
          primary: 'table tr td',
          fallbacks: []
        },
        language: {
          primary: 'table tr td',
          fallbacks: []
        },
        format: {
          primary: 'table tr td',
          fallbacks: []
        },
        pages: {
          primary: 'table tr td',
          fallbacks: []
        },
        publication_date: {
          primary: 'table tr td',
          fallbacks: []
        },
        series: {
          primary: '.BookPageTitleSection__series a',
          fallbacks: ['.SeriesLink', 'table tr td']
        },
        awards: {
          primary: '[href*="ref=nav_brws_gca"]',
          fallbacks: ['table tr td']
        },
        characters: {
          primary: '.TruncatedContent__text--small[data-testid="contentContainer"]',
          fallbacks: [
            '.BookDetails .DescListItem dt',
            '.CollapsableList .DescListItem dt',
            '.BookDetails__list .BookDetails__item',
            '.WorkDetails .DescListItem dt',
            'div.DescListItem dt',
            'dt',
            'table tr td'
          ]
        },
        settings: {
          primary: 'table tr td',
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
          fallbacks: ['img.ResponsiveImage', '.BookCover__image img', '.BookPage__leftColumn img']
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

      // First extract book details using the dedicated function
      const bookDetails = extractBookDetails();

      // Merge book details into metadata
      Object.entries(bookDetails).forEach(([key, value]) => {
        if (value && metadata.hasOwnProperty(key)) {
          metadata[key] = value;
        }
      });

      // Then process each selector for additional data
      for (const [field, selectors] of Object.entries(SELECTORS)) {
        // Skip fields we already have from bookDetails
        if (bookDetails[field] && metadata[field]) continue;

        if (typeof selectors !== 'object' || typeof selectors.primary !== 'string') {
          console.warn('Invalid selector configuration for field:', field, selectors);
          continue;
        }

        let rawValue = extractWithFallbacks(selectors);
        if (!rawValue) continue;

        // For fields that might contain HTML attributes, get the HTML content
        if (field === 'series' || field === 'characters' || field === 'settings' || field === 'format') {
          try {
            const element = document.querySelector(selectors.primary) ||
                          (selectors.fallbacks && selectors.fallbacks.map(sel => document.querySelector(sel)).find(el => el));
            if (element) {
              rawValue = element.innerHTML;
            }
          } catch (e) {
            // Fallback to text content if there's an error
          }
        }

        // Clean the value using both functions
        const value = cleanFieldValue(cleanText(rawValue));

        switch (field) {
          case 'rating':
            const ratingMatch = value.match(/(\d+\.?\d*)/);
            metadata.rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;
            break;
          case 'genres':
            metadata.genres = value.split(/[,;]/).map(item => cleanText(item)).filter(item => item.length > 0);
            break;
          case 'isbn':
          case 'isbn13':
            // Extract only the ISBN number
            const isbnMatch = value.match(/(\d+[\dX]+)/i);
            metadata[field] = isbnMatch ? isbnMatch[1] : '';
            break;
          case 'publisher':
            // Extract publisher name from the element
            const publisherElement = document.querySelector(selectors.primary) ||
                                    (selectors.fallbacks && selectors.fallbacks.map(sel => document.querySelector(sel)).find(el => el));
            if (publisherElement) {
              // Try to find the actual publisher text
              const labelElement = publisherElement.querySelector('.FeatureItem__label, .BookDetails__label');
              const valueElement = publisherElement.querySelector('.FeatureItem__value, .BookDetails__value');

              if (labelElement && valueElement && labelElement.textContent.toLowerCase().includes('publisher')) {
                metadata.publisher = cleanText(valueElement.textContent);
              } else {
                // If we can't find specific elements, use the whole text
                metadata.publisher = value;
              }
            }
            break;
          case 'pages':
            // Extract only the number from the pages value
            const pagesMatch = value.match(/(\d+)/);
            metadata.pages = pagesMatch ? pagesMatch[1] : '';
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
          case 'format':
            // Special handling for format field which might contain JSON
            if (value.includes('{"@type"') || value.includes('"name"')) {
              try {
                // Try to extract format name from JSON-like string
                const formatMatch = value.match(/"name":\s*"([^"]+)"/);
                if (formatMatch && formatMatch[1]) {
                  metadata.format = formatMatch[1];
                } else {
                  // Try another pattern
                  const typeMatch = value.match(/"@type":\s*"([^"]+)"/);
                  if (typeMatch && typeMatch[1]) {
                    metadata.format = typeMatch[1];
                  } else {
                    metadata.format = value;
                  }
                }
              } catch (e) {
                // If parsing fails, use the cleaned value
                metadata.format = value;
              }
            } else {
              metadata.format = value;
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
          case 'series':
            // Special handling for series to remove link attributes
            if (value.includes('link, opens in new tab') || value.includes('role="link"')) {
              // Try to extract just the series name
              const seriesMatch = value.match(/([^,]+)/);
              if (seriesMatch) {
                metadata.series = seriesMatch[0].trim();
              } else {
                // Apply multiple cleanups
                let cleanedSeries = value;
                cleanedSeries = cleanedSeries.replace(/, link, opens in new tab.*$/, '');
                cleanedSeries = cleanedSeries.replace(/role="link".*$/, '');
                cleanedSeries = cleanedSeries.replace(/<[^>]*>/g, '');
                metadata.series = cleanedSeries.trim();
              }
            } else {
              metadata.series = value;
            }

            // Remove series number if present
            if (metadata.series.match(/\(#\d+\)$/)) {
              metadata.series = metadata.series.replace(/\s*\(#\d+\)$/, '');
            }
            break;
          case 'characters':
          case 'settings':
            // Special handling for characters based on the HTML structure
            if (field === 'characters') {
              try {
                // First try to find the characters container - using standard DOM methods instead of jQuery-like selectors
                let characterContainer = null;

                // Find all dt elements and check their text content
                const allDtElements = document.querySelectorAll('.BookDetails .DescListItem dt, .CollapsableList .DescListItem dt, dt');
                for (const dt of allDtElements) {
                  if (dt.textContent && dt.textContent.includes('Characters')) {
                    characterContainer = dt.closest('.DescListItem') || dt.parentElement;
                    break;
                  }
                }

                if (characterContainer) {
                  // Find the dd element that contains the character links
                  const ddElement = characterContainer.querySelector('dd');

                  if (ddElement) {
                    // Find all character links
                    const characterLinks = ddElement.querySelectorAll('a');

                    if (characterLinks && characterLinks.length > 0) {
                      // Extract character names from links
                      const characterNames = Array.from(characterLinks).map(link => {
                        return link.textContent.trim();
                      }).filter(name => name.length > 0);

                      // Join with commas
                      if (characterNames.length > 0) {
                        metadata.characters = characterNames.join(', ');
                        break;
                      }
                    }
                  }
                }

                // Try the new Goodreads layout with TruncatedContent
                const truncatedContent = document.querySelector('.TruncatedContent__text--small[data-testid="contentContainer"]');
                if (truncatedContent) {
                  // Find all character links
                  const characterLinks = truncatedContent.querySelectorAll('a[href*="/characters/"]');

                  if (characterLinks && characterLinks.length > 0) {
                    // Extract character names from links
                    const characterNames = Array.from(characterLinks).map(link => {
                      return link.textContent.trim();
                    }).filter(name => name.length > 0);

                    // Join with commas
                    if (characterNames.length > 0) {
                      metadata.characters = characterNames.join(', ');
                      break;
                    }
                  }
                }
              } catch (e) {
                console.error('Error extracting characters:', e);
                // Fall through to default extraction
              }
            }

            // Default extraction logic for both characters and settings
            const detailElement = document.querySelector(selectors.primary) ||
                                 (selectors.fallbacks && selectors.fallbacks.map(sel => document.querySelector(sel)).find(el => el));
            if (detailElement) {
              // For characters, try to extract from the dd element directly
              if (field === 'characters') {
                // Try to find character links anywhere in the document
                const allLinks = document.querySelectorAll('a[href*="/characters/"]');
                if (allLinks && allLinks.length > 0) {
                  // Extract character names from links
                  const characterNames = Array.from(allLinks).map(link => {
                    return link.textContent.trim();
                  }).filter(name => name.length > 0);

                  // Join with commas
                  if (characterNames.length > 0) {
                    metadata.characters = characterNames.join(', ');
                    break;
                  }
                }

                // If we found a DT element, try to get its sibling DD
                if (detailElement && detailElement.tagName === 'DT') {
                  const ddElement = detailElement.nextElementSibling;
                  if (ddElement && ddElement.tagName === 'DD') {
                    // Find all character links
                    const characterLinks = ddElement.querySelectorAll('a');

                    if (characterLinks && characterLinks.length > 0) {
                      // Extract character names from links
                      const characterNames = Array.from(characterLinks).map(link => {
                        return link.textContent.trim();
                      }).filter(name => name.length > 0);

                      // Join with commas
                      if (characterNames.length > 0) {
                        metadata.characters = characterNames.join(', ');
                        break;
                      }
                    }
                  }
                }
              }

              // Try to find label and value elements
              const labelElement = detailElement.querySelector('.FeatureItem__label, .BookDetails__label');
              const valueElement = detailElement.querySelector('.FeatureItem__value, .BookDetails__value');

              if (labelElement && valueElement) {
                // For characters, try to extract links directly
                if (field === 'characters') {
                  const characterLinks = valueElement.querySelectorAll('a');

                  if (characterLinks && characterLinks.length > 0) {
                    // Extract character names from links
                    const characterNames = Array.from(characterLinks).map(link => {
                      return link.textContent.trim();
                    }).filter(name => name.length > 0);

                    // Join with commas
                    if (characterNames.length > 0) {
                      metadata.characters = characterNames.join(', ');
                      break;
                    }
                  }
                }

                // Get both innerHTML and textContent for thorough cleaning
                let htmlValue = valueElement.innerHTML;
                let textValue = valueElement.textContent;

                // Clean both values
                let cleanedHtmlValue = cleanFieldValue(cleanText(htmlValue));
                let cleanedTextValue = cleanFieldValue(cleanText(textValue));

                // Use the shorter/cleaner version
                if (cleanedTextValue.length < cleanedHtmlValue.length) {
                  metadata[field] = cleanedTextValue;
                } else {
                  metadata[field] = cleanedHtmlValue;
                }

                // Additional cleaning for links
                if (metadata[field].includes('link, opens in new tab') ||
                    metadata[field].includes('role="link"')) {
                  // Split by commas and clean each part
                  const parts = metadata[field].split(',').map(part => {
                    return cleanFieldValue(part.trim());
                  }).filter(part => part.length > 0);

                  metadata[field] = parts.join(', ');
                }
              } else {
                metadata[field] = value;
              }
            } else {
              // If we couldn't find the element, use the cleaned value
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
    
    // CRITICAL FIX: Use pagination parameters from PHP if provided
    let nextCursor = nextPageToken || cachedData?.graphql_state?.next_token;
    let hasMore = true;
    let pageCount = startPage ? (startPage - 1) : (cachedData?.graphql_state?.current_page || 0);
    
    logger.info(`Pagination state initialization:
      - Using nextCursor: ${nextCursor} (from ${nextPageToken ? 'PHP' : 'cache'})
      - Starting pageCount: ${pageCount} (from ${startPage ? 'PHP' : 'cache'})
      - Cached reviews: ${reviews.length}
    `);

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