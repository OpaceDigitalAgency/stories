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

// GraphQL query for fetching reviews
const REVIEWS_QUERY = `
query getReviews($filters: BookReviewsFilterInput!, $pagination: PaginationInput) {
  getReviews(filters: $filters, pagination: $pagination) {
    ...BookReviewsFragment
    __typename
  }
}

fragment BookReviewsFragment on BookReviewsConnection {
  totalCount
  edges {
    node {
      ...ReviewCardFragment
      __typename
    }
    __typename
  }
  pageInfo {
    prevPageToken
    nextPageToken
    __typename
  }
  __typename
}

fragment ReviewCardFragment on Review {
  __typename
  id
  creator {
    ...ReviewerProfileFragment
    __typename
  }
  recommendFor
  updatedAt
  createdAt
  spoilerStatus
  lastRevisionAt
  text
  rating
  shelving {
    shelf {
      name
      webUrl
      __typename
    }
    taggings {
      tag {
        name
        webUrl
        __typename
      }
      __typename
    }
    webUrl
    __typename
  }
  likeCount
  viewerHasLiked
  commentCount
}

fragment ReviewerProfileFragment on User {
  id: legacyId
  imageUrlSquare
  isAuthor
  ...SocialUserFragment
  textReviewsCount
  viewerRelationshipStatus {
    isBlockedByViewer
    __typename
  }
  name
  webUrl
  contributor {
    id
    works {
      totalCount
      __typename
    }
    __typename
  }
  __typename
}

fragment SocialUserFragment on User {
  viewerRelationshipStatus {
    isFollowing
    isFriend
    __typename
  }
  followersCount
  __typename
}`;

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

  // Try to extract work ID from URL (used in GraphQL queries)
  const workIdMatch = url.match(/amzn1\.gr\.work\.v1\.[a-zA-Z0-9]+/);
  if (workIdMatch && workIdMatch[0]) {
    logger.info(`Extracted work ID ${workIdMatch[0]} from URL`);
    return workIdMatch[0];
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
 * Extract work ID from HTML content
 * @param {string} html - HTML content of the page
 * @returns {string|null} - Work ID or null if not found
 */
function extractWorkIdFromHtml(html) {
  // Look for the work ID in the HTML
  const workIdMatch = html.match(/kca:\/\/work\/amzn1\.gr\.work\.v1\.[a-zA-Z0-9]+/);
  if (workIdMatch && workIdMatch[0]) {
    const workId = workIdMatch[0].replace('kca://', '');
    logger.info(`Extracted work ID ${workId} from HTML`);
    return workId;
  }

  logger.warn('Could not extract work ID from HTML');
  return null;
}

/**
 * Extract comprehensive book metadata from the book page
 * @param {Page} page - Puppeteer page object
 * @returns {Promise<Object>} - Book metadata
 */
async function extractBookMetadata(page) {
  logger.info('Extracting comprehensive book metadata from page');

  try {
    // Take a screenshot for debugging
    await browser.takeScreenshot(page, 'goodreads-metadata-extraction');

    // Save the HTML for debugging
    const pageHtml = await page.content();
    fs.writeFileSync(path.join(debugDir, 'goodreads-metadata-page.html'), pageHtml);

    // Extract metadata using page.evaluate
    const metadata = await page.evaluate(() => {
      console.log('Starting book metadata extraction...');

      const bookData = {
        title: '',
        author: '',
        isbn: '',
        isbn13: '',
        publisher: '',
        publication_date: '',
        page_count: '',
        language: '',
        format: '',
        series: '',
        genres: [],
        awards: [],
        characters: [],
        settings: [],
        rating: '',
        rating_count: '',
        review_count: '',
        description: '',
        cover_url: '',
        selectors_used: {} // Track which selectors were used successfully
      };

      // Extract title - try multiple selectors
      const titleSelectors = [
        'h1.BookPageTitleSection__title',
        'h1[data-testid="bookTitle"]',
        '.BookPageTitleSection h1'
      ];

      for (const selector of titleSelectors) {
        const element = document.querySelector(selector);
        if (element) {
          bookData.title = element.textContent.trim();
          bookData.selectors_used.title = selector;
          break;
        }
      }

      // Extract author - try multiple selectors
      const authorSelectors = [
        '.BookPageTitleSection__authorLink',
        '.BookPageTitleSection a[data-testid="authorLink"]',
        '.AuthorLink__name'
      ];

      for (const selector of authorSelectors) {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
          bookData.author = Array.from(elements)
            .map(el => el.textContent.trim())
            .join(', ');
          bookData.selectors_used.author = selector;
          break;
        }
      }

      // Extract cover image - try multiple selectors
      const coverSelectors = [
        '.BookCover__image img',
        '.BookPage__bookCover img',
        'img.ResponsiveImage'
      ];

      for (const selector of coverSelectors) {
        const element = document.querySelector(selector);
        if (element && element.src) {
          bookData.cover_url = element.src;
          bookData.selectors_used.cover_url = selector;
          break;
        }
      }

      // Extract rating - try multiple selectors
      const ratingSelectors = [
        '.RatingStatistics__rating',
        '[data-testid="averageRating"]'
      ];

      for (const selector of ratingSelectors) {
        const element = document.querySelector(selector);
        if (element) {
          bookData.rating = parseFloat(element.textContent.trim());
          bookData.selectors_used.rating = selector;
          break;
        }
      }

      // Extract rating count - try multiple selectors
      const ratingCountSelectors = [
        '.RatingStatistics__meta span',
        '[data-testid="ratingsCount"]'
      ];

      for (const selector of ratingCountSelectors) {
        const element = document.querySelector(selector);
        if (element) {
          const countText = element.textContent.trim();
          const countMatch = countText.match(/(\d+(?:,\d+)*)/);
          if (countMatch) {
            bookData.rating_count = parseInt(countMatch[1].replace(/,/g, ''));
            bookData.selectors_used.rating_count = selector;
            break;
          }
        }
      }

      // Extract description - try multiple selectors
      const descriptionSelectors = [
        '.BookPageMetadataSection__description .Formatted',
        '[data-testid="description"] .Formatted',
        '[data-testid="description"]'
      ];

      for (const selector of descriptionSelectors) {
        const element = document.querySelector(selector);
        if (element) {
          bookData.description = element.textContent.trim();
          bookData.selectors_used.description = selector;
          break;
        }
      }

      // Extract genres - try multiple selectors
      const genreSelectors = [
        '.BookPageMetadataSection__genres .Button__labelItem',
        '[data-testid="genresList"] a',
        '.BookPageMetadataSection__genres a'
      ];

      for (const selector of genreSelectors) {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
          bookData.genres = Array.from(elements)
            .map(el => el.textContent.trim())
            .filter(text => text.length > 0);
          bookData.selectors_used.genres = selector;
          break;
        }
      }

      // Extract book details from the BookDetails section
      // Based on the screenshot, we need to look for the CollapsableList inside BookDetails
      const detailsContainers = [
        '.BookDetails .CollapsableList',
        '.BookDetails__list',
        '.EditionDetails',
        '.MoreInformation'
      ];

      // Function to process details from various containers
      const processDetailsContainer = (container) => {
        // Look for key-value pairs in the container
        const rows = container.querySelectorAll('div, span');

        rows.forEach(row => {
          const text = row.textContent.trim();

          // Format: 267 pages, Paperback
          if (text.match(/pages.*,.*paperback/i) || text.match(/pages/i)) {
            const pagesMatch = text.match(/(\d+)\s+pages/i);
            if (pagesMatch) {
              bookData.page_count = parseInt(pagesMatch[1]);
              bookData.selectors_used.page_count = 'BookDetails text';
            }

            const formatMatch = text.match(/pages,\s+([^,]+)/i);
            if (formatMatch) {
              bookData.format = formatMatch[1].trim();
              bookData.selectors_used.format = 'BookDetails text';
            }
          }

          // Published date
          if (text.match(/published/i)) {
            const dateMatch = text.match(/published\s+([^by]+)(?:by\s+(.+))?/i);
            if (dateMatch) {
              bookData.publication_date = dateMatch[1].trim();
              bookData.selectors_used.publication_date = 'BookDetails text';

              if (dateMatch[2]) {
                bookData.publisher = dateMatch[2].trim();
                bookData.selectors_used.publisher = 'BookDetails text';
              }
            }
          }

          // ISBN
          if (text.match(/isbn/i)) {
            const isbnMatch = text.match(/isbn\s*:?\s*(\d+)/i);
            if (isbnMatch) {
              bookData.isbn = isbnMatch[1];
              bookData.selectors_used.isbn = 'BookDetails text';
            }

            const isbn13Match = text.match(/isbn13\s*:?\s*(\d+)/i);
            if (isbn13Match) {
              bookData.isbn13 = isbn13Match[1];
              bookData.selectors_used.isbn13 = 'BookDetails text';
            }
          }

          // Language
          if (text.match(/language/i)) {
            const languageMatch = text.match(/language\s*:?\s*(.+)/i);
            if (languageMatch) {
              bookData.language = languageMatch[1].trim();
              bookData.selectors_used.language = 'BookDetails text';
            }
          }

          // Awards
          if (text.match(/awards/i)) {
            const awardsMatch = text.match(/awards\s*:?\s*(.+)/i);
            if (awardsMatch) {
              bookData.awards = awardsMatch[1].trim().split(',').map(a => a.trim());
              bookData.selectors_used.awards = 'BookDetails text';
            }
          }
        });
      };

      // Try each container type
      for (const selector of detailsContainers) {
        const containers = document.querySelectorAll(selector);
        if (containers.length > 0) {
          containers.forEach(container => processDetailsContainer(container));
          break;
        }
      }

      // Look for specific format sections in the "This edition" section
      const editionDetails = document.querySelectorAll('.EditionDetails, [data-testid="editionDetails"]');
      editionDetails.forEach(section => {
        // Format
        const formatRow = section.querySelector('div:contains("Format")');
        if (formatRow) {
          const formatText = formatRow.textContent.replace('Format', '').trim();
          if (formatText && !bookData.format) {
            bookData.format = formatText;
            bookData.selectors_used.format = 'EditionDetails Format';
          }
        }

        // Published
        const publishedRow = section.querySelector('div:contains("Published")');
        if (publishedRow) {
          const publishedText = publishedRow.textContent.replace('Published', '').trim();
          if (publishedText) {
            const pubMatch = publishedText.match(/([^by]+)(?:by\s+(.+))?/);
            if (pubMatch) {
              if (!bookData.publication_date) {
                bookData.publication_date = pubMatch[1].trim();
                bookData.selectors_used.publication_date = 'EditionDetails Published';
              }

              if (pubMatch[2] && !bookData.publisher) {
                bookData.publisher = pubMatch[2].trim();
                bookData.selectors_used.publisher = 'EditionDetails Published';
              }
            }
          }
        }

        // ISBN
        const isbnRow = section.querySelector('div:contains("ISBN")');
        if (isbnRow) {
          const isbnText = isbnRow.textContent.trim();

          const isbn13Match = isbnText.match(/(\d{13})/);
          if (isbn13Match && !bookData.isbn13) {
            bookData.isbn13 = isbn13Match[1];
            bookData.selectors_used.isbn13 = 'EditionDetails ISBN';
          }

          const isbn10Match = isbnText.match(/\(ISBN10:\s*(\d+X?)\)/i);
          if (isbn10Match && !bookData.isbn) {
            bookData.isbn = isbn10Match[1];
            bookData.selectors_used.isbn = 'EditionDetails ISBN';
          }
        }

        // Language
        const languageRow = section.querySelector('div:contains("Language")');
        if (languageRow) {
          const languageText = languageRow.textContent.replace('Language', '').trim();
          if (languageText && !bookData.language) {
            bookData.language = languageText;
            bookData.selectors_used.language = 'EditionDetails Language';
          }
        }
      });

      // Try to extract additional metadata from JSON-LD
      try {
        const scriptElements = document.querySelectorAll('script[type="application/ld+json"]');
        scriptElements.forEach(script => {
          try {
            const jsonData = JSON.parse(script.textContent);

            // Check if this is book data
            if (jsonData && jsonData['@type'] === 'Book') {
              console.log('Found JSON-LD Book data');

              // Fill in missing data from JSON-LD
              if (!bookData.title && jsonData.name) {
                bookData.title = jsonData.name;
                bookData.selectors_used.title = 'JSON-LD name';
              }

              if (!bookData.author && jsonData.author) {
                if (Array.isArray(jsonData.author)) {
                  bookData.author = jsonData.author.map(a => a.name).join(', ');
                } else if (jsonData.author.name) {
                  bookData.author = jsonData.author.name;
                }
                bookData.selectors_used.author = 'JSON-LD author';
              }

              if (!bookData.isbn && jsonData.isbn) {
                bookData.isbn = jsonData.isbn;
                bookData.selectors_used.isbn = 'JSON-LD isbn';
              }

              if (!bookData.publisher && jsonData.publisher && jsonData.publisher.name) {
                bookData.publisher = jsonData.publisher.name;
                bookData.selectors_used.publisher = 'JSON-LD publisher';
              }

              if (!bookData.description && jsonData.description) {
                bookData.description = jsonData.description;
                bookData.selectors_used.description = 'JSON-LD description';
              }

              // Extract additional data that might not be in the HTML
              if (jsonData.bookFormat && !bookData.format) {
                bookData.format = jsonData.bookFormat;
                bookData.selectors_used.format = 'JSON-LD bookFormat';
              }

              if (jsonData.numberOfPages && !bookData.page_count) {
                bookData.page_count = jsonData.numberOfPages;
                bookData.selectors_used.page_count = 'JSON-LD numberOfPages';
              }

              if (jsonData.inLanguage && !bookData.language) {
                bookData.language = jsonData.inLanguage;
                bookData.selectors_used.language = 'JSON-LD inLanguage';
              }

              if (jsonData.datePublished && !bookData.publication_date) {
                bookData.publication_date = jsonData.datePublished;
                bookData.selectors_used.publication_date = 'JSON-LD datePublished';
              }

              // Extract awards if available
              if (jsonData.awards && Array.isArray(jsonData.awards)) {
                bookData.awards = jsonData.awards;
                bookData.selectors_used.awards = 'JSON-LD awards';
              }

              // Extract characters if available
              if (jsonData.character && Array.isArray(jsonData.character)) {
                bookData.characters = jsonData.character.map(c => c.name || c);
                bookData.selectors_used.characters = 'JSON-LD character';
              }
            }
          } catch (err) {
            console.error(`Error parsing JSON-LD: ${err.message}`);
          }
        });
      } catch (err) {
        console.error(`Error processing JSON-LD scripts: ${err.message}`);
      }

      // Try to extract data from meta tags
      try {
        // Check for Open Graph meta tags
        const ogTitleElement = document.querySelector('meta[property="og:title"]');
        if (ogTitleElement && !bookData.title) {
          bookData.title = ogTitleElement.getAttribute('content');
          bookData.selectors_used.title = 'meta og:title';
        }

        const ogDescriptionElement = document.querySelector('meta[property="og:description"]');
        if (ogDescriptionElement && !bookData.description) {
          bookData.description = ogDescriptionElement.getAttribute('content');
          bookData.selectors_used.description = 'meta og:description';
        }

        const ogImageElement = document.querySelector('meta[property="og:image"]');
        if (ogImageElement && !bookData.cover_url) {
          bookData.cover_url = ogImageElement.getAttribute('content');
          bookData.selectors_used.cover_url = 'meta og:image';
        }
      } catch (err) {
        console.error(`Error extracting meta tags: ${err.message}`);
      }

      // Look for characters and settings in the book description
      if (bookData.description) {
        // Look for character mentions in the description
        const characterMatches = bookData.description.match(/character(?:s)? (?:of|include|named) ([^\.]+)/i);
        if (characterMatches && characterMatches[1] && !bookData.characters.length) {
          const characterNames = characterMatches[1].split(/,|and/).map(name => name.trim());
          bookData.characters = characterNames.filter(name => name.length > 0);
          bookData.selectors_used.characters = 'description text analysis';
        }

        // Look for settings mentions in the description
        const settingMatches = bookData.description.match(/set (?:in|on) ([^\.]+)/i);
        if (settingMatches && settingMatches[1] && !bookData.settings.length) {
          const settingNames = settingMatches[1].split(/,|and/).map(name => name.trim());
          bookData.settings = settingNames.filter(name => name.length > 0);
          bookData.selectors_used.settings = 'description text analysis';
        }
      }

      console.log('Completed book metadata extraction');
      return bookData;
    });

    logger.info('Successfully extracted book metadata:');
    logger.info(`- Title: ${metadata.title}`);
    logger.info(`- Author: ${metadata.author}`);
    logger.info(`- ISBN: ${metadata.isbn}`);
    logger.info(`- ISBN-13: ${metadata.isbn13}`);
    logger.info(`- Publisher: ${metadata.publisher}`);
    logger.info(`- Publication Date: ${metadata.publication_date}`);
    logger.info(`- Page Count: ${metadata.page_count}`);
    logger.info(`- Language: ${metadata.language}`);
    logger.info(`- Format: ${metadata.format}`);
    logger.info(`- Series: ${metadata.series}`);
    logger.info(`- Genres: ${metadata.genres.join(', ')}`);

    return metadata;
  } catch (error) {
    logger.error(`Error extracting book metadata: ${error.message}`);
    return {
      error: error.message,
      title: '',
      author: ''
    };
  }
}

/**
 * Extract comprehensive book metadata from the book page
 * @param {Page} page - Puppeteer page object
 * @returns {Promise<Object>} - Book metadata
 */
async function extractBookMetadata(page) {
  logger.info('Extracting comprehensive book metadata from page');

  try {
    // Take a screenshot for debugging
    await browser.takeScreenshot(page, 'goodreads-metadata-extraction');

    // Extract metadata using page.evaluate
    const metadata = await page.evaluate(() => {
      console.log('Starting book metadata extraction...');

      const bookData = {
        title: '',
        author: '',
        isbn: '',
        isbn13: '',
        publisher: '',
        publication_date: '',
        page_count: '',
        language: '',
        format: '',
        series: '',
        genres: [],
        awards: [],
        characters: [],
        settings: [],
        rating: '',
        rating_count: '',
        review_count: '',
        description: '',
        cover_url: '',
        selectors_used: {} // Track which selectors were used successfully
      };

      // Extract title
      const titleElement = document.querySelector('h1.BookPageTitleSection__title');
      if (titleElement) {
        bookData.title = titleElement.textContent.trim();
        bookData.selectors_used.title = 'h1.BookPageTitleSection__title';
      }

      // Extract author
      const authorElement = document.querySelector('.BookPageTitleSection__authorLink');
      if (authorElement) {
        bookData.author = authorElement.textContent.trim();
        bookData.selectors_used.author = '.BookPageTitleSection__authorLink';
      }

      // Extract cover image
      const coverElement = document.querySelector('.BookCover__image img');
      if (coverElement) {
        bookData.cover_url = coverElement.src;
        bookData.selectors_used.cover_url = '.BookCover__image img';
      }

      // Extract rating
      const ratingElement = document.querySelector('.RatingStatistics__rating');
      if (ratingElement) {
        bookData.rating = parseFloat(ratingElement.textContent.trim());
        bookData.selectors_used.rating = '.RatingStatistics__rating';
      }

      // Extract rating count
      const ratingCountElement = document.querySelector('.RatingStatistics__meta span');
      if (ratingCountElement) {
        const countText = ratingCountElement.textContent.trim();
        const countMatch = countText.match(/(\d+(?:,\d+)*)/);
        if (countMatch) {
          bookData.rating_count = parseInt(countMatch[1].replace(/,/g, ''));
          bookData.selectors_used.rating_count = '.RatingStatistics__meta span';
        }
      }

      // Extract book details from the BookDetails section
      const detailsElements = document.querySelectorAll('.BookDetails .FeatureDetails');
      detailsElements.forEach(element => {
        const labelElement = element.querySelector('.FeatureDetails__label');
        const valueElement = element.querySelector('.FeatureDetails__value');

        if (labelElement && valueElement) {
          const label = labelElement.textContent.trim().toLowerCase();
          const value = valueElement.textContent.trim();

          if (label.includes('format')) {
            bookData.format = value;
            bookData.selectors_used.format = '.BookDetails .FeatureDetails (format)';
          } else if (label.includes('pages')) {
            const pagesMatch = value.match(/(\d+)/);
            if (pagesMatch) {
              bookData.page_count = parseInt(pagesMatch[1]);
              bookData.selectors_used.page_count = '.BookDetails .FeatureDetails (pages)';
            }
          } else if (label.includes('published') || label.includes('publication')) {
            // Extract publication date and publisher
            const pubMatch = value.match(/(.*?)(?:\s+by\s+(.*?))?$/);
            if (pubMatch) {
              bookData.publication_date = pubMatch[1].trim();
              bookData.selectors_used.publication_date = '.BookDetails .FeatureDetails (published)';

              if (pubMatch[2]) {
                bookData.publisher = pubMatch[2].trim();
                bookData.selectors_used.publisher = '.BookDetails .FeatureDetails (published)';
              }
            }
          } else if (label.includes('isbn')) {
            if (label.includes('13')) {
              bookData.isbn13 = value.replace(/[^0-9X]/g, '');
              bookData.selectors_used.isbn13 = '.BookDetails .FeatureDetails (isbn13)';
            } else {
              bookData.isbn = value.replace(/[^0-9X]/g, '');
              bookData.selectors_used.isbn = '.BookDetails .FeatureDetails (isbn)';
            }
          } else if (label.includes('language')) {
            bookData.language = value;
            bookData.selectors_used.language = '.BookDetails .FeatureDetails (language)';
          } else if (label.includes('series')) {
            bookData.series = value;
            bookData.selectors_used.series = '.BookDetails .FeatureDetails (series)';
          }
        }
      });

      // Extract description
      const descriptionElement = document.querySelector('.BookPageMetadataSection__description .Formatted');
      if (descriptionElement) {
        bookData.description = descriptionElement.textContent.trim();
        bookData.selectors_used.description = '.BookPageMetadataSection__description .Formatted';
      }

      // Extract genres/shelves
      const genreElements = document.querySelectorAll('.BookPageMetadataSection__genres .Button__labelItem');
      if (genreElements.length > 0) {
        bookData.genres = Array.from(genreElements).map(el => el.textContent.trim());
        bookData.selectors_used.genres = '.BookPageMetadataSection__genres .Button__labelItem';
      }

      // Try to extract additional metadata from JSON-LD
      try {
        const scriptElements = document.querySelectorAll('script[type="application/ld+json"]');
        scriptElements.forEach(script => {
          try {
            const jsonData = JSON.parse(script.textContent);

            // Check if this is book data
            if (jsonData && jsonData['@type'] === 'Book') {
              console.log('Found JSON-LD Book data');

              // Fill in missing data from JSON-LD
              if (!bookData.title && jsonData.name) {
                bookData.title = jsonData.name;
                bookData.selectors_used.title = 'JSON-LD name';
              }

              if (!bookData.author && jsonData.author) {
                if (Array.isArray(jsonData.author)) {
                  bookData.author = jsonData.author.map(a => a.name).join(', ');
                } else if (jsonData.author.name) {
                  bookData.author = jsonData.author.name;
                }
                bookData.selectors_used.author = 'JSON-LD author';
              }

              if (!bookData.isbn && jsonData.isbn) {
                bookData.isbn = jsonData.isbn;
                bookData.selectors_used.isbn = 'JSON-LD isbn';
              }

              if (!bookData.publisher && jsonData.publisher && jsonData.publisher.name) {
                bookData.publisher = jsonData.publisher.name;
                bookData.selectors_used.publisher = 'JSON-LD publisher';
              }

              if (!bookData.description && jsonData.description) {
                bookData.description = jsonData.description;
                bookData.selectors_used.description = 'JSON-LD description';
              }

              // Extract additional data that might not be in the HTML
              if (jsonData.bookFormat && !bookData.format) {
                bookData.format = jsonData.bookFormat;
                bookData.selectors_used.format = 'JSON-LD bookFormat';
              }

              if (jsonData.numberOfPages && !bookData.page_count) {
                bookData.page_count = jsonData.numberOfPages;
                bookData.selectors_used.page_count = 'JSON-LD numberOfPages';
              }

              if (jsonData.inLanguage && !bookData.language) {
                bookData.language = jsonData.inLanguage;
                bookData.selectors_used.language = 'JSON-LD inLanguage';
              }

              if (jsonData.datePublished && !bookData.publication_date) {
                bookData.publication_date = jsonData.datePublished;
                bookData.selectors_used.publication_date = 'JSON-LD datePublished';
              }

              // Extract awards if available
              if (jsonData.awards && Array.isArray(jsonData.awards)) {
                bookData.awards = jsonData.awards;
                bookData.selectors_used.awards = 'JSON-LD awards';
              }

              // Extract characters if available
              if (jsonData.character && Array.isArray(jsonData.character)) {
                bookData.characters = jsonData.character.map(c => c.name || c);
                bookData.selectors_used.characters = 'JSON-LD character';
              }
            }
          } catch (err) {
            console.error(`Error parsing JSON-LD: ${err.message}`);
          }
        });
      } catch (err) {
        console.error(`Error processing JSON-LD scripts: ${err.message}`);
      }

      // Try to extract data from meta tags
      try {
        // Check for Open Graph meta tags
        const ogTitleElement = document.querySelector('meta[property="og:title"]');
        if (ogTitleElement && !bookData.title) {
          bookData.title = ogTitleElement.getAttribute('content');
          bookData.selectors_used.title = 'meta og:title';
        }

        const ogDescriptionElement = document.querySelector('meta[property="og:description"]');
        if (ogDescriptionElement && !bookData.description) {
          bookData.description = ogDescriptionElement.getAttribute('content');
          bookData.selectors_used.description = 'meta og:description';
        }

        const ogImageElement = document.querySelector('meta[property="og:image"]');
        if (ogImageElement && !bookData.cover_url) {
          bookData.cover_url = ogImageElement.getAttribute('content');
          bookData.selectors_used.cover_url = 'meta og:image';
        }
      } catch (err) {
        console.error(`Error extracting meta tags: ${err.message}`);
      }

      // Look for characters and settings in the book description
      if (bookData.description) {
        // Look for character mentions in the description
        const characterMatches = bookData.description.match(/character(?:s)? (?:of|include|named) ([^\.]+)/i);
        if (characterMatches && characterMatches[1] && !bookData.characters.length) {
          const characterNames = characterMatches[1].split(/,|and/).map(name => name.trim());
          bookData.characters = characterNames.filter(name => name.length > 0);
          bookData.selectors_used.characters = 'description text analysis';
        }

        // Look for settings mentions in the description
        const settingMatches = bookData.description.match(/set (?:in|on) ([^\.]+)/i);
        if (settingMatches && settingMatches[1] && !bookData.settings.length) {
          const settingNames = settingMatches[1].split(/,|and/).map(name => name.trim());
          bookData.settings = settingNames.filter(name => name.length > 0);
          bookData.selectors_used.settings = 'description text analysis';
        }
      }

      console.log('Completed book metadata extraction');
      return bookData;
    });

    logger.info('Successfully extracted book metadata:');
    logger.info(`- Title: ${metadata.title}`);
    logger.info(`- Author: ${metadata.author}`);
    logger.info(`- ISBN: ${metadata.isbn}`);
    logger.info(`- ISBN-13: ${metadata.isbn13}`);
    logger.info(`- Publisher: ${metadata.publisher}`);
    logger.info(`- Publication Date: ${metadata.publication_date}`);
    logger.info(`- Page Count: ${metadata.page_count}`);
    logger.info(`- Language: ${metadata.language}`);
    logger.info(`- Format: ${metadata.format}`);
    logger.info(`- Series: ${metadata.series}`);
    logger.info(`- Genres: ${metadata.genres.join(', ')}`);

    return metadata;
  } catch (error) {
    logger.error(`Error extracting book metadata: ${error.message}`);
    return {
      error: error.message,
      title: '',
      author: ''
    };
  }
}

/**
 * Scrape reviews from a Goodreads book page
 * @param {string} goodreadsUrl - The URL of the Goodreads book page
 * @param {number} limit - Maximum number of reviews to return
 * @returns {Promise<Object>} - Object containing book title and reviews
 * @param {string} goodreadsUrl - The URL of the Goodreads book page
 * @param {number} limit - Maximum number of reviews to return
 * @param {Object} options - Additional options
 * @param {number} options.maxPages - Maximum number of pages to scrape
 * @param {boolean} options.continueFromLast - Whether to continue from the last scrape
 * @returns {Promise<Object>} - Object containing reviews and metadata
 */
async function scrapeGoodreadsReviews(goodreadsUrl, limit = 50, options = {}) {
  // Ensure options are properly parsed
  const maxPages = parseInt(options.maxPages || 100, 10);
  const continueFromLast = options.continueFromLast === true || options.continueFromLast === 'true' || options.continueFromLast === 1 || options.continueFromLast === '1';
  const startPage = parseInt(options.startPage || 1, 10);

  // Log options for debugging
  logger.info(`Goodreads scraper options:
    - url: ${goodreadsUrl}
    - limit: ${limit}
    - maxPages: ${maxPages}
    - continueFromLast: ${continueFromLast}
    - startPage: ${startPage}
  `);

  logger.info(`Starting Goodreads scraping with settings:
    - URL: ${goodreadsUrl}
    - Limit: ${limit}
    - Max Pages: ${maxPages}
    - Continue From Last: ${continueFromLast}
    - Start Page: ${startPage}`);

  logger.info(`Starting Goodreads scraping for URL: ${goodreadsUrl}, limit: ${limit}, maxPages: ${maxPages}, continueFromLast: ${continueFromLast}`);

  // Extract book ID for caching
  const bookId = extractBookIdFromUrl(goodreadsUrl);
  if (!bookId) {
    logger.warn(`Could not extract book ID from URL: ${goodreadsUrl}`);
  } else {
    // Check cache first
    const cacheKey = `${bookId}`;
    const cachedData = await cache.get('goodreads', cacheKey);

    // Log cache status
    if (cachedData) {
      logger.info(`Found cached data for Goodreads book ID: ${bookId}`);
      logger.info(`Cache contains ${cachedData.reviews ? cachedData.reviews.length : 0} reviews`);
      logger.info(`Requested limit: ${limit}`);
      logger.info(`Continue from last: ${continueFromLast}`);
    } else {
      logger.info(`No cached data found for Goodreads book ID: ${bookId}`);
    }

    if (cachedData) {
      // Handle cached data differently based on continueFromLast parameter
      if (continueFromLast) {
        // Properly resume from last GraphQL state, not just return cache
        reviews = cachedData.reviews || [];
        pageCount = cachedData.graphql_state?.current_page || cachedData.currentPage || 1;
        nextPageToken = cachedData.graphql_state?.next_token || null;
        totalCount = cachedData.graphql_state?.total_available || cachedData.totalAvailable || 0;

        logger.info(`✅ Resuming from cache:
- Total cached reviews: ${reviews.length}
- Page count: ${pageCount}
- Next token: ${nextPageToken}
- Total available: ${totalCount}`);

        // If we know total available, adjust limit
        if (totalCount) {
          const remaining = totalCount - reviews.length;
          if (remaining > 0) {
            logger.info(`${remaining} more reviews available to scrape`);
          }
        }

        logger.info(`Starting with ${reviews.length} reviews from cache, will continue scraping`);
      } else {
        // Updated logic: allow continued scraping if continueFromLast is true, even if cache has >= limit reviews
        if (cachedData.reviews && cachedData.reviews.length >= limit) {
          if (continueFromLast) {
            logger.info(`Cached data has ${cachedData.reviews.length} reviews, but continueFromLast=true — will fetch more`);
          } else {
            logger.info(`Using cached data for Goodreads book ID: ${bookId} (${cachedData.reviews.length} reviews)`);
            return {
              source: 'cache',
              ...cachedData
            };
          }
        } else {
          logger.info(`Cache has insufficient reviews (${cachedData.reviews ? cachedData.reviews.length : 0} < ${limit}), fetching fresh data`);
        }
      }
    }
  }

  const page = await browser.getNewPage();
  // Ensure variables are always defined globally for this function
  let reviews = [];
  let pageCount = 1; // Track how many pages we've scraped
  let newToken = null;        // 👈 prevents “newToken is not defined”
  let nextPageToken = null;
  let totalCount = null;
  let bookTitle = 'Unknown Book';
  // --- Request interception setup ---
  await page.setRequestInterception(true);

  function shouldAbortRequest(request) {
    const url = request.url();
    return (
      request.resourceType() === 'image' ||
      url.includes('google-analytics.com') ||
      url.includes('facebook.net') ||
      url.includes('doubleclick.net')
    );
  }

  page.on('request', request => {
    if (shouldAbortRequest(request)) {
      request.abort().catch(err => {
        // ignore "Request is already handled" errors
        if (!err.message.includes('already handled')) {
          console.error('❌ Abort error:', err.message);
        }
      });
    } else {
      request.continue().catch(err => {
        // ignore "Request is already handled" errors
        if (!err.message.includes('already handled')) {
          console.error('❌ Continue error:', err.message);
        }
      });
    }
  });

  try {
    // Navigate to the book page
    logger.info(`Navigating to: ${goodreadsUrl}`);
    await page.goto(goodreadsUrl, { waitUntil: 'networkidle2', timeout: 30000 });

    // Save the page HTML for debugging
    const initialPageHtml = await page.content();
    fs.writeFileSync(path.join(debugDir, `goodreads-${bookId || 'unknown'}-page.html`), initialPageHtml);

    // Extract comprehensive book metadata
    logger.info('Extracting comprehensive book metadata');
    const bookMetadata = await extractBookMetadata(page);

    // Set book title from metadata
    bookTitle = bookMetadata.title || 'Unknown Book';
    logger.info(`Book title: ${bookTitle}`);

    // Create aggregate rating object from metadata
    const aggregateRating = {
      rating: bookMetadata.rating || null,
      count: bookMetadata.rating_count || 0
    };

    // Store the book metadata for later use
    const bookInfo = {
      title: bookMetadata.title,
      author: bookMetadata.author,
      isbn: bookMetadata.isbn,
      isbn13: bookMetadata.isbn13,
      publisher: bookMetadata.publisher,
      publication_date: bookMetadata.publication_date,
      page_count: bookMetadata.page_count,
      language: bookMetadata.language,
      format: bookMetadata.format,
      series: bookMetadata.series,
      genres: bookMetadata.genres,
      awards: bookMetadata.awards,
      characters: bookMetadata.characters,
      settings: bookMetadata.settings,
      cover_url: bookMetadata.cover_url,
      description: bookMetadata.description
    };

    // Log the extracted metadata
    logger.info('Extracted book metadata:');
    Object.entries(bookInfo).forEach(([key, value]) => {
      if (value && (typeof value !== 'object' || (Array.isArray(value) && value.length > 0))) {
        logger.info(`- ${key}: ${Array.isArray(value) ? value.join(', ') : value}`);
      }
    });

    // Add aggregate rating review
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
          ratings_count: aggregateRating.count,
          book_metadata: bookInfo
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
      // Log the current URL for debugging
      const currentUrl = await page.url();
      logger.info(`Extracting reviews from URL: ${currentUrl}`);

      return page.evaluate(() => {
        // Log what we're doing to the console (for screenshots)
        console.log('Starting review extraction...');

        // Try multiple selectors for review elements
        const selectors = [
          'div.ReviewsList__item',
          'div.ReviewCard',
          'div[data-testid="review"]',
          'article.Review'
        ];

        let reviewElements = [];

        // Try each selector until we find reviews
        for (const selector of selectors) {
          const elements = document.querySelectorAll(selector);
          if (elements.length > 0) {
            console.log(`Found ${elements.length} reviews using selector: ${selector}`);
            reviewElements = Array.from(elements);
            break;
          }
        }

        if (reviewElements.length === 0) {
          console.log('No reviews found with standard selectors, trying alternative approach');

          // Try a more generic approach - look for elements that contain both reviewer name and rating stars
          const allDivs = document.querySelectorAll('div');
          reviewElements = Array.from(allDivs).filter(div => {
            return div.querySelector('.ReviewerProfile__name') ||
                   div.querySelector('.RatingStars') ||
                   div.querySelector('[data-testid="rating-stars"]');
          });

          console.log(`Found ${reviewElements.length} reviews using alternative approach`);
        }

        const pageReviews = [];

        reviewElements.forEach((reviewElement, index) => {
          try {
            // Extract reviewer name - try multiple selectors
            let reviewerName = 'Goodreads User';
            const nameSelectors = [
              '.ReviewerProfile__name',
              '.UserLink__name',
              '[data-testid="reviewer-name"]',
              '.ReviewerProfile a'
            ];

            for (const selector of nameSelectors) {
              const nameElement = reviewElement.querySelector(selector);
              if (nameElement) {
                reviewerName = nameElement.textContent.trim();
                break;
              }
            }

            // Extract rating - try multiple approaches
            let ratingValue = null;

            // Approach 1: RatingStars with aria-label
            const ratingElement = reviewElement.querySelector('.RatingStars') ||
                                  reviewElement.querySelector('[data-testid="rating-stars"]');

            if (ratingElement) {
              const ariaLabel = ratingElement.getAttribute('aria-label');
              if (ariaLabel) {
                const match = ariaLabel.match(/(\d+)/);
                if (match) {
                  ratingValue = parseInt(match[1]);
                }
              }

              // Approach 2: Count the filled stars
              if (!ratingValue) {
                const filledStars = ratingElement.querySelectorAll('.RatingStar__filled') ||
                                    ratingElement.querySelectorAll('[data-testid="filled-star"]');
                if (filledStars.length > 0) {
                  ratingValue = filledStars.length;
                }
              }
            }

            // Approach 3: Look for text that contains "rated it X stars"
            if (!ratingValue) {
              const ratedText = reviewElement.textContent;
              const ratingMatch = ratedText.match(/rated it (\d+) stars?/i);
              if (ratingMatch) {
                ratingValue = parseInt(ratingMatch[1]);
              }
            }

            // Extract review text - try multiple selectors
            let reviewText = '';
            const textSelectors = [
              '.ReviewText__content',
              '.Formatted',
              '[data-testid="review-text"]',
              '.ReviewText'
            ];

            for (const selector of textSelectors) {
              const textElement = reviewElement.querySelector(selector);
              if (textElement) {
                reviewText = textElement.textContent.trim();
                break;
              }
            }

            // If no specific text element found, try to get all text excluding certain elements
            if (!reviewText) {
              // Clone the element to avoid modifying the original
              const clone = reviewElement.cloneNode(true);

              // Remove elements we don't want in the review text
              const elementsToRemove = [
                '.ReviewerProfile',
                '.RatingStars',
                '.ReviewCard__date',
                '.ReviewActions'
              ];

              elementsToRemove.forEach(selector => {
                const elements = clone.querySelectorAll(selector);
                elements.forEach(el => el.remove());
              });

              reviewText = clone.textContent.trim()
                .replace(/\s+/g, ' ')  // Replace multiple spaces with a single space
                .substring(0, 2000);   // Limit length to avoid huge reviews
            }

            // Extract date - try multiple selectors
            let reviewDate = new Date().toISOString().split('T')[0]; // Default to today
            const dateSelectors = [
              '.ReviewCard__date',
              '[data-testid="review-date"]',
              '.ReviewDate'
            ];

            for (const selector of dateSelectors) {
              const dateElement = reviewElement.querySelector(selector);
              if (dateElement) {
                const dateText = dateElement.textContent.trim().replace(/^reviewed\s+/i, '');

                // Try to parse the date
                try {
                  const parsedDate = new Date(dateText);
                  if (!isNaN(parsedDate.getTime())) {
                    reviewDate = parsedDate.toISOString().split('T')[0];
                  }
                } catch (e) {
                  // If parsing fails, keep the default date
                  console.log(`Could not parse date: ${dateText}`);
                }

                break;
              }
            }

            // Only add reviews with both rating and text
            if (ratingValue && reviewText && reviewText.length > 10) {
              pageReviews.push({
                reviewer_name: reviewerName,
                rating: ratingValue,
                rating_normalised: ratingValue / 5,
                review_text: reviewText,
                review_date: reviewDate
              });

              console.log(`Extracted review ${index + 1}: ${reviewerName}, ${ratingValue} stars`);
            }
          } catch (err) {
            console.error(`Error extracting review ${index + 1}: ${err.message}`);
          }
        });

        console.log(`Successfully extracted ${pageReviews.length} reviews`);
        return pageReviews;
      });
    };

    /**
     * Extract reviews from GraphQL response
     * @param {Object} response - GraphQL response
     * @returns {Object} - Object containing reviews and next page token
     */
    const extractReviewsFromGraphQL = (response, pageNumber) => {
      if (!response.data || !response.data.getReviews || !response.data.getReviews.edges) {
        logger.warn('Invalid GraphQL response structure');
        return { reviews: [], nextPageToken: null };
      }

      logger.info(`Processing GraphQL reviews from page ${pageNumber}`);

      const reviews = response.data.getReviews.edges.map(edge => {
        const node = edge.node;
        const reviewer = node.creator || {};

        return {
          reviewer_name: reviewer.name || 'Goodreads User',
          rating: node.rating || 0,
          rating_normalised: (node.rating || 0) / 5,
          review_text: node.text || '',
          review_date: node.updatedAt || node.createdAt || new Date().toISOString().split('T')[0]
        };
      }).filter(review => review.rating > 0 && review.review_text.length > 10);

      const nextPageToken = response.data.getReviews.pageInfo.nextPageToken;
      const totalCount = response.data.getReviews.totalCount;

      logger.info(`Extracted ${reviews.length} reviews from GraphQL response. Total available: ${totalCount}`);

      return { reviews, nextPageToken, totalCount };
    };

    // Extract reviews from first page, but skip if resuming from cursor and have a nextPageToken
    if (!continueFromLast || (continueFromLast && (!reviews.length || !nextPageToken))) {
      let pageReviews = await extractReviewsFromPage();
      reviews = [...reviews, ...pageReviews.map((review, index) => ({
        source_id: 4, // Goodreads source ID
        ...review,
        metadata: JSON.stringify({
          book_id: bookId,
          graphql_batch: 1,
          position_in_batch: index + 1,
          total_in_batch: pageReviews.length,
          total_available: aggregateRating.count,
          scrape_date: new Date().toISOString(),
          source: 'initial_page'
        })
      }))];

      logger.info(`Extracted ${pageReviews.length} reviews from first page`);
    } else {
      logger.info(`Skipping initial page extraction, resuming GraphQL from token: ${nextPageToken}`);
    }

    // Extract work ID from the HTML
    const reviewsPageHtml = await page.content();
    let workId = extractWorkIdFromHtml(reviewsPageHtml);
    let graphqlData = null;

    if (workId) {
      logger.info(`Found work ID for GraphQL requests: ${workId}`);
    } else {
      logger.warn('Could not find work ID for GraphQL requests, will try to capture it from network requests');
    }

    // Function to make direct GraphQL requests
    const fetchMoreReviewsViaGraphQL = async (nextPageToken) => {
      if (!workId) {
        logger.warn('Cannot make GraphQL request without work ID');
        return null;
      }

      const requestData = {
        operationName: "getReviews",
        query: REVIEWS_QUERY,
        variables: {
          filters: {
            resourceType: "WORK",
            resourceId: workId,
            orderBy: "date_updated",
            orderDir: "DESC"
          },
          pagination: {
            after: nextPageToken,
            limit: 200, // Increased to get more reviews per request
            includeStats: true // Request additional stats if available
          }
        }
      };

      logger.info(`Making GraphQL request:
        - Work ID: ${workId}
        - Next Token: ${nextPageToken}
        - Batch Size: 200
        - Current Reviews: ${reviews.length}
        - Total Available: ${aggregateRating.count || 'unknown'}`);

      logger.info(`Making GraphQL request:
        - Work ID: ${workId}
        - Next Token: ${nextPageToken}
        - Batch Size: 200
        - Current Reviews: ${reviews.length}
        - Total Expected: ${aggregateRating.count || 'unknown'}`);

      try {
        const response = await page.evaluate(async (url, data) => {
          const response = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
          });
          return response.json();
        }, 'https://www.goodreads.com/graphql', requestData);

        return response;
      } catch (err) {
        logger.error(`Error making GraphQL request: ${err.message}`);
        return null;
      }
    };

    // Click "More reviews" button and extract more reviews until we reach the limit
    let pageNum = startPage || 2;
    let clickAttempts = 0;
    const maxClickAttempts = 50; // Increased from 20 to 50
    let consecutiveEmptyPages = 0;
    const maxConsecutiveEmptyPages = 3;

    logger.info(`📊 Starting enhanced review extraction - target: ${limit} reviews`);

    // Take a screenshot of the initial page for debugging
    await browser.takeScreenshot(page, `goodreads-initial-page-${bookId || 'unknown'}`);

    // Save the initial HTML for debugging
    const pageContent = await page.content();
    fs.writeFileSync(path.join(debugDir, `goodreads-initial-page-${bookId || 'unknown'}.html`), pageContent);

    // Log all buttons on the page for debugging
    const allButtons = await page.$$('button');
    logger.info(`Found ${allButtons.length} buttons on the page`);

    // Log the text content of each button
    for (let i = 0; i < allButtons.length; i++) {
      try {
        const buttonText = await page.evaluate(el => el.textContent.trim(), allButtons[i]);
        const buttonClass = await page.evaluate(el => el.className, allButtons[i]);
        logger.info(`Button ${i+1}: Text="${buttonText}", Class="${buttonClass}"`);
      } catch (err) {
        logger.error(`Error getting button ${i+1} details: ${err.message}`);
      }
    }

    // Click "Show more reviews" once to capture the GraphQL request
    logger.info('Attempting to click "Show more reviews" button to capture GraphQL request');
    let buttonClicked = false;

    // Try XPath selector first (most reliable)
    try {
      const moreReviewsButton = await page.$x("//button[contains(., 'Show more reviews')]");
      if (moreReviewsButton.length > 0) {
        await moreReviewsButton[0].click();
        await page.waitForTimeout(3000); // Wait for request to be captured
        buttonClicked = true;
        logger.info('Successfully clicked "Show more reviews" button to capture GraphQL request');
      }
    } catch (err) {
      logger.error(`Error clicking "Show more reviews" button: ${err.message}`);
    }

    // If XPath didn't work, try CSS selector
    if (!buttonClicked) {
      try {
        const hasButton = await page.evaluate(() => {
          const button = document.querySelector('button.Button--secondary');
          if (button && button.textContent.toLowerCase().includes('more reviews')) {
            button.click();
            return true;
          }
          return false;
        });

        if (hasButton) {
          await page.waitForTimeout(3000); // Wait for request to be captured
          buttonClicked = true;
          logger.info('Successfully clicked "Show more reviews" button using CSS selector');
        }
      } catch (err) {
        logger.error(`Error with CSS button click: ${err.message}`);
      }
    }

    // Extract the new reviews after clicking
    if (buttonClicked) {
      const newReviews = await extractReviewsFromPage();
      const uniqueNewReviews = newReviews.filter(newReview => {
        return !reviews.some(existingReview => {
          // Check if reviewer name matches
          const nameMatch = existingReview.reviewer_name === newReview.reviewer_name;

          // For aggregate reviews, just check the name
          if (nameMatch && (
              existingReview.reviewer_name === 'Goodreads Aggregate' ||
              newReview.reviewer_name === 'Goodreads Aggregate'
          )) {
            return true;
          }

          // For regular reviews, check name and first 50 chars of text
          // This is more reliable than comparing entire text which might have small differences
          if (nameMatch) {
            const existingTextStart = existingReview.review_text.substring(0, 50);
            const newTextStart = newReview.review_text.substring(0, 50);
            return existingTextStart === newTextStart;
          }

          return false;
        });
      });

      if (uniqueNewReviews.length > 0) {
        reviews = [...reviews, ...uniqueNewReviews.map(review => ({
          source_id: 4,
          ...review,
          metadata: JSON.stringify({
            book_id: bookId
          })
        }))];

        logger.info(`Added ${uniqueNewReviews.length} new reviews after initial button click`);
      }
    }

    // Check if we captured GraphQL data
    if (graphqlData && workId) {
      logger.info('Successfully captured GraphQL data, using GraphQL API for pagination');

      // Get the next page token from the captured request
      let nextPageToken = null;

      if (graphqlData.variables && graphqlData.variables.pagination && graphqlData.variables.pagination.after) {
        nextPageToken = graphqlData.variables.pagination.after;
      } else if (buttonClicked) {
        // If we clicked the button but didn't get a token, we need to extract it from the response
        try {
          const responseHtml = await page.content();
          const tokenMatch = responseHtml.match(/"nextPageToken":"([^"]+)"/);
          if (tokenMatch && tokenMatch[1]) {
            nextPageToken = tokenMatch[1];
            logger.info(`Extracted next page token from HTML: ${nextPageToken}`);
          }
        } catch (err) {
          logger.error(`Error extracting token from HTML: ${err.message}`);
        }
      }

      // If we still don't have a token, try to make a request with null token to get the first page
      if (!nextPageToken) {
        logger.info('No next page token found, starting with null token');
        nextPageToken = null;
      }

      // Initialize reviews array with cached reviews if continuing from last scrape
      if (continueFromLast && cachedData && cachedData.reviews) {
        reviews = cachedData.reviews;
        logger.info(`Starting with ${reviews.length} reviews from cache for GraphQL pagination`);

        // Get the GraphQL state from the cached data
        if (cachedData.graphql_state) {
          nextPageToken = cachedData.graphql_state.next_token;
          pageCount = cachedData.graphql_state.current_page || 0;
          totalCount = cachedData.graphql_state.total_available;

          logger.info(`Resuming GraphQL pagination from cached state:
            - Page: ${pageCount}
            - Token: ${nextPageToken}
            - Total available: ${totalCount}
            - Reviews so far: ${reviews.length}`);
        } else {
          // Fallback to getting state from review metadata
          const lastReview = reviews[reviews.length - 1];
          if (lastReview && lastReview.metadata) {
            try {
              const metadata = JSON.parse(lastReview.metadata);
              if (metadata.next_token) {
                nextPageToken = metadata.next_token;
                pageCount = metadata.graphql_page || 0;
                logger.info(`Resuming GraphQL pagination from review metadata:
                  - Page: ${pageCount}
                  - Token: ${nextPageToken}
                  - Total available: ${metadata.total_available}
                  - Reviews so far: ${reviews.length}`);
              }
            } catch (err) {
              logger.error(`Error parsing review metadata: ${err.message}`);
            }
          }
        }

        // Log the state of the pagination variables
        logger.info(`GraphQL pagination variables after initialization:
          - nextPageToken: ${nextPageToken}
          - pageCount: ${pageCount}
          - totalCount: ${totalCount}
          - reviews.length: ${reviews.length}
        `);
      }

    // Use GraphQL pagination to fetch more reviews
    // Use maxPages to limit the number of pages we fetch
    // pageCount, nextPageToken, totalCount are now set correctly above if continuing from last
    // Ensure nextToken is declared before the loop
    let nextToken = null;
    logger.info(`📋 ENTERING GRAPHQL LOOP
- nextPageToken: ${nextPageToken}
- pageCount: ${pageCount}/${maxPages}
- reviews.length: ${reviews.length}
- limit: ${limit}
- continueFromLast: ${continueFromLast}
- totalCount: ${totalCount}`);

    while ((nextPageToken !== undefined && nextPageToken !== null) &&
           pageCount < maxPages &&
           (continueFromLast || reviews.length < limit) &&
           (totalCount === undefined || totalCount === null || reviews.length < totalCount)) {

      logger.info(`Fetching GraphQL page ${pageCount + 1}/${maxPages}:
        - Reviews so far: ${reviews.length}
        - Total available: ${totalCount || 'unknown'}
        - Next token: ${nextPageToken}
        - Continue from last: ${continueFromLast}`);
      pageCount++;
      logger.info(`Fetching page ${pageCount}/${maxPages} of reviews via GraphQL API`);
      logger.info(`Fetching GraphQL page ${pageCount + 1}/${maxPages}, ` +
                 `reviews so far: ${reviews.length}/${totalCount || 'unknown'}`);

      const response = await fetchMoreReviewsViaGraphQL(nextPageToken);

      if (!response) {
        logger.warn('GraphQL request failed, falling back to button clicking');
        break;
      }

      const { reviews: newReviews, nextPageToken: nextToken, totalCount } = extractReviewsFromGraphQL(response);
      logger.info(`GraphQL response: ${newReviews.length} reviews, next token: ${nextToken}, total: ${totalCount}`);

        if (newReviews.length === 0) {
          logger.info('No more reviews returned from GraphQL API');
          break;
        }

        // Add the new reviews to our collection
        // Add new reviews with GraphQL pagination metadata
        const processedReviews = newReviews.map((review, index) => ({
          source_id: 4,
          ...review,
          metadata: JSON.stringify({
            book_id: bookId,
            graphql_page: pageCount,
            next_token: nextToken,
            batch_position: index + 1,
            batch_size: newReviews.length,
            total_available: totalCount,
            scrape_date: new Date().toISOString(),
            source: 'graphql',
            is_continuation: continueFromLast
          })
        }));

        reviews = [...reviews, ...processedReviews];

        logger.info(`Added ${newReviews.length} reviews from GraphQL API, total: ${reviews.length}/${totalCount}`);

        // Update the token for the next request
        nextPageToken = nextToken;

        // If there's no next token, we've reached the end
        if (!nextPageToken) {
          logger.info('No next page token returned, reached end of reviews');
          break;
        }

        // Add a small delay between requests
        await page.waitForTimeout(1000);
      }

      // If we've reached the limit or have no more reviews, we're done
      if (reviews.length >= limit) {
        logger.info(`Reached review limit (${limit}) using GraphQL API`);
      } else if (nextPageToken === undefined) {
        logger.info('No more reviews available via GraphQL API');
      } else {
        logger.info('Falling back to button clicking approach for more reviews');
      }
    } else {
      logger.warn('Could not capture GraphQL data or work ID, falling back to button clicking approach');
    }

    // If we still need more reviews, fall back to the button clicking approach
    if (reviews.length < limit) {
      logger.info(`Still need more reviews (${reviews.length}/${limit}), trying button clicking approach`);

      // Track consecutive attempts with no new reviews
      let consecutiveFailedAttempts = 0;
      const maxConsecutiveFailedAttempts = 5; // Stop after 5 consecutive attempts with no new reviews

      // Set a timeout for the entire scraping process (10 minutes)
      const startTime = Date.now();
      const maxScrapingTime = 10 * 60 * 1000; // 10 minutes in milliseconds

      while ((continueFromLast || reviews.length < limit) &&
             pageNum <= maxPages &&
             clickAttempts < maxClickAttempts &&
             consecutiveFailedAttempts < maxConsecutiveFailedAttempts &&
             consecutiveEmptyPages < maxConsecutiveEmptyPages &&
             (Date.now() - startTime) < maxScrapingTime) {

      logger.info(`� Progress: ${reviews.length}/${limit} reviews, page ${pageNum}, attempt ${clickAttempts + 1}/${maxClickAttempts}, consecutive fails: ${consecutiveFailedAttempts}`);

      // Check if we've been running too long
      const runningTime = Math.floor((Date.now() - startTime) / 1000);
      if (runningTime > 300) { // 5 minutes
        logger.warn(`⚠️ Scraping taking too long (${runningTime} seconds), may be stuck in a loop`);
      }

      // Scroll to bottom to ensure the button is visible
      await page.evaluate(() => {
        window.scrollTo(0, document.body.scrollHeight);
      });

      await page.waitForTimeout(2000); // Increased wait time

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

              // Log the button details
              const buttonText = await page.evaluate(el => el.textContent.trim(), buttons[0]);
              const buttonClass = await page.evaluate(el => el.className, buttons[0]);
              logger.info(`Button details: Text="${buttonText}", Class="${buttonClass}"`);

              // Take a screenshot before clicking
              await browser.takeScreenshot(page, `goodreads-before-click-${bookId}-attempt-${clickAttempts}`);

              // Click the first matching button
              try {
                // Try direct click first
                await buttons[0].click();
                logger.info(`Clicked button using direct click`);
              } catch (clickErr) {
                logger.warn(`Direct click failed: ${clickErr.message}, trying evaluate click`);

                // If direct click fails, try clicking via evaluate
                await page.evaluate(el => {
                  el.click();
                }, buttons[0]);
                logger.info(`Clicked button using evaluate click`);
              }

              // Take a screenshot after clicking
              await page.waitForTimeout(1000);
              await browser.takeScreenshot(page, `goodreads-after-click-${bookId}-attempt-${clickAttempts}`);

              buttonFound = true;
              break;
            }
          } catch (err) {
            logger.error(`Error with XPath selector ${xpathSelector}: ${err.message}`);
          }
        }
      }

      // If still no button found, try a more aggressive approach with direct DOM manipulation
      if (!buttonFound) {
        try {
          logger.info(`Trying direct DOM manipulation to load more reviews`);

          // Try to find the load more button by its role and text content
          const buttonFound = await page.evaluate(() => {
            // Look for buttons with specific text content
            const buttons = Array.from(document.querySelectorAll('button'));
            const loadMoreButton = buttons.find(btn => {
              const text = btn.textContent.toLowerCase();
              return text.includes('more reviews') || text.includes('show more') || text.includes('load more');
            });

            if (loadMoreButton) {
              // Try to click it
              loadMoreButton.click();
              console.log('Found and clicked button via DOM: ' + loadMoreButton.textContent);
              return true;
            }

            // If no button found, try to trigger the load more functionality directly
            // This is a last resort and might break with Goodreads updates
            try {
              // Check if there's a pagination container
              const paginationContainer = document.querySelector('.Pagination');
              if (paginationContainer) {
                // Try to find the next page link
                const nextPageLink = paginationContainer.querySelector('a[rel="next"]');
                if (nextPageLink) {
                  // Simulate clicking the next page link
                  nextPageLink.click();
                  console.log('Clicked next page link');
                  return true;
                }
              }
            } catch (e) {
              console.error('Error trying pagination:', e);
            }

            return false;
          });

          if (buttonFound) {
            logger.info(`✅ Successfully triggered more reviews loading via DOM manipulation`);
          } else {
            logger.warn(`⚠️ Could not find any way to load more reviews via DOM manipulation`);
          }
        } catch (err) {
          logger.error(`Error with DOM manipulation approach: ${err.message}`);
        }
      }

      // No URL pagination fallback - Goodreads only works with GraphQL pagination
      if (!buttonFound) {
        logger.warn('Could not find "Show more reviews" button, trying to resume GraphQL pagination');

        // Try to extract GraphQL token from the page
        try {
          const responseHtml = await page.content();
          const tokenMatch = responseHtml.match(/"nextPageToken":"([^"]+)"/);
          if (tokenMatch && tokenMatch[1]) {
            nextPageToken = tokenMatch[1];
            logger.info(`Found GraphQL token on page: ${nextPageToken}`);
            return; // Exit the button clicking loop to resume GraphQL pagination
          }
        } catch (err) {
          logger.error(`Error extracting GraphQL token: ${err.message}`);
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

      // Process new reviews with GraphQL metadata
      const processedReviews = newReviews.map((review, index) => ({
        ...review,
        fromGraphQL: true,
        graphqlPage: pageCount,
        position: index + 1,
        nextToken: newToken,
        totalAvailable: totalCount
      }));

      // Save the current page HTML for debugging
      const currentHtml = await page.content();
      fs.writeFileSync(path.join(debugDir, `goodreads-page-${bookId || 'unknown'}-attempt-${clickAttempts}.html`), currentHtml);

      // Log the number of reviews found
      logger.info(`📊 Found ${newReviews.length} reviews on current page (attempt ${clickAttempts + 1})`);

      // Check if we got new unique reviews by comparing with what we already have
      // When continuing from last scrape, we want all reviews after our last page
      const uniqueNewReviews = continueFromLast ?
        // If continuing, keep all reviews from new GraphQL pages
        processedReviews.filter(review => {
          // Always keep aggregate reviews
          if (review.reviewer_name === 'Goodreads Aggregate') {
            return true;
          }

          // Check if we already have this review from a previous page
          return !reviews.some(existingReview => {
            try {
              const metadata = JSON.parse(existingReview.metadata || '{}');
              // If the existing review is from a previous GraphQL page, this is a new review
              if (metadata.graphql_page && metadata.graphql_page < pageCount) {
                return false;
              }
            } catch (err) {
              logger.error(`Error parsing metadata: ${err.message}`);
            }

            // Fall back to content comparison
            return existingReview.reviewer_name === review.reviewer_name &&
                   existingReview.review_text.substring(0, 50) === review.review_text.substring(0, 50);
          });
        }) :
        // If not continuing, only keep reviews we haven't seen before
        newReviews.filter(review => {
          return !reviews.some(existingReview =>
            existingReview.reviewer_name === review.reviewer_name &&
            existingReview.review_text.substring(0, 50) === review.review_text.substring(0, 50)
          );
        });

      logger.info(`✅ Found ${uniqueNewReviews.length} unique new reviews`);

      if (uniqueNewReviews.length > 0) {
        // Add the unique new reviews to our collection
        reviews = [...reviews, ...uniqueNewReviews.map(review => ({
          source_id: 4,
          ...review,
          metadata: JSON.stringify({
            book_id: bookId,
            graphql_page: pageCount,
            next_token: newToken,
            batch_position: review.position,
            batch_size: uniqueNewReviews.length,
            total_available: totalCount,
            scrape_date: new Date().toISOString(),
            source: 'graphql',
            is_continuation: continueFromLast
          })
        }))];

        logger.info(`Added ${uniqueNewReviews.length} reviews from GraphQL page ${pageCount}, total: ${reviews.length}/${totalCount}`);

        logger.info(`📊 Total reviews collected: ${reviews.length}`);
        pageNum++;

        // Reset click attempts on success
        clickAttempts = 0;

        // Update pageReviews with all reviews from this page for next comparison
        pageReviews = newReviews;
      } else {
        logger.info(`⚠️ No new reviews found after clicking, attempt ${clickAttempts + 1}/${maxClickAttempts}`);
        consecutiveEmptyPages++;

        if (consecutiveEmptyPages >= maxConsecutiveEmptyPages) {
          logger.info(`⚠️ Stopping after ${consecutiveEmptyPages} consecutive empty pages`);
          break;
        }

        // If we couldn't find the button or extract a token, increment attempts
        clickAttempts++;

        clickAttempts++;
      }

      // Add a delay between attempts
      await page.waitForTimeout(2000);
    }
    } // Close the if (reviews.length < limit) block

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

    // Only limit reviews if we're not continuing from last scrape
    const limitedReviews = continueFromLast ? reviews : reviews.slice(0, limit);

    // Cache the results
    if (bookId) {
      // Save the next page token if we have more reviews to fetch
      let nextPageToken = null;

      // If we didn't get all reviews and we're using GraphQL, save the token
      if (reviews.length > limitedReviews.length && workId) {
        // Try to extract the next page token from the last GraphQL response
        try {
          const responseHtml = await page.content();
          const tokenMatch = responseHtml.match(/"nextPageToken":"([^"]+)"/);
          if (tokenMatch && tokenMatch[1]) {
            nextPageToken = tokenMatch[1];
            logger.info(`Saving next page token for future scraping: ${nextPageToken}`);
          }
        } catch (err) {
          logger.error(`Error extracting token for caching: ${err.message}`);
        }
      }

      // Prepare data to cache
      const dataToCache = {
        book_title: bookTitle,
        book_author: bookInfo.author,
        book_isbn: bookInfo.isbn,
        book_isbn13: bookInfo.isbn13,
        book_publisher: bookInfo.publisher,
        book_publication_date: bookInfo.publication_date,
        book_page_count: bookInfo.page_count,
        book_language: bookInfo.language,
        book_format: bookInfo.format,
        book_series: bookInfo.series,
        book_genres: bookInfo.genres,
        book_awards: bookInfo.awards,
        book_characters: bookInfo.characters,
        book_settings: bookInfo.settings,
        book_cover_url: bookInfo.cover_url,
        book_description: bookInfo.description,
        total: reviews.length,
        reviews: limitedReviews,
        currentPage: pageNum,
        hasMoreReviews: reviews.length < (aggregateRating.count || limit),
        totalAvailable: aggregateRating.count || null,
        lastScrapedAt: new Date().toISOString(),
        requestedLimit: limit,
        graphql_state: {
          next_token: nextPageToken,
          current_page: pageCount,
          batch_size: 200,
          total_available: totalCount,
          total_fetched: reviews.length,
          last_scrape: new Date().toISOString(),
          has_more: reviews.length < totalCount
        }
      };

      // Log detailed cache information
      logger.info(`Preparing to cache data for book ID ${bookId}:`);
      logger.info(`- Book title: ${bookTitle}`);
      logger.info(`- Total reviews: ${reviews.length}`);
      logger.info(`- Limited reviews: ${limitedReviews.length}`);
      logger.info(`- Current page: ${pageNum}`);
      logger.info(`- Has more reviews: ${reviews.length < (aggregateRating.count || limit)}`);
      logger.info(`- Total available: ${aggregateRating.count || 'unknown'}`);
      logger.info(`- Requested limit: ${limit}`);
      logger.info(`- GraphQL state:`);
      logger.info(`  - Next token: ${nextPageToken || 'null'}`);
      logger.info(`  - Current page: ${pageCount}`);
      logger.info(`  - Total available: ${totalCount || 'unknown'}`);
      logger.info(`  - Total fetched: ${reviews.length}`);
      logger.info(`  - Has more: ${reviews.length < totalCount}`);

      // Use the same cache key format as when retrieving
      const cacheKey = `${bookId}`;
      logger.info(`Setting cache with key: goodreads:${cacheKey}`);
      await cache.set('goodreads', cacheKey, dataToCache);

      // Verify the cache was set
      const verifyCachedData = await cache.get('goodreads', cacheKey);
      if (verifyCachedData) {
        logger.info(`Cache verification successful. Cached ${verifyCachedData.reviews.length} reviews.`);
      } else {
        logger.warn(`Cache verification failed. Could not retrieve cached data.`);
      }
    }

    return {
      source: 'scrape',
      book_title: bookTitle,
      book_author: bookInfo.author,
      book_isbn: bookInfo.isbn,
      book_isbn13: bookInfo.isbn13,
      book_publisher: bookInfo.publisher,
      book_publication_date: bookInfo.publication_date,
      book_page_count: bookInfo.page_count,
      book_language: bookInfo.language,
      book_format: bookInfo.format,
      book_series: bookInfo.series,
      book_genres: bookInfo.genres,
      book_awards: bookInfo.awards,
      book_characters: bookInfo.characters,
      book_settings: bookInfo.settings,
      book_cover_url: bookInfo.cover_url,
      book_description: bookInfo.description,
      total: reviews.length,
      reviews: limitedReviews,
      currentPage: pageNum,
      hasMoreReviews: reviews.length < (aggregateRating.count || limit),
      totalAvailable: aggregateRating.count || null
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
