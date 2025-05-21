/**
 * Goodreads HTML selectors and extraction logic
 */

// Book metadata selectors
const SELECTORS = {
  title: {
    primary: '[data-testid="bookTitle"]',
    fallbacks: [
      'h1.BookPageTitleSection__title',
      '.BookPageTitleSection h1'
    ]
  },
  series: {
    primary: '[data-testid="seriesLink"]',
    fallbacks: [
      '.BookPageTitleSection__series',
      '.SeriesLink',
      '.DescListItem dt:contains("Series") + dd'
    ]
  },
  publisher: {
    primary: '[data-testid="publicationInfo"]',
    fallbacks: [
      '.FeaturedDetails [data-testid="publisher"]',
      '.BookDetails [data-testid="publisher"]'
    ]
  },
  publication_date: {
    primary: '[data-testid="publicationInfo"]',
    fallbacks: [
      '.FeaturedDetails [data-testid="publicationDate"]',
      '.BookDetails [data-testid="publicationDate"]'
    ]
  },
  author: {
    primary: '[data-testid="authorLink"]',
    fallbacks: [
      '.BookPageTitleSection__authorLink',
      '.AuthorLink__name'
    ]
  },
  rating: {
    primary: '[data-testid="averageRating"]',
    fallbacks: [
      '.RatingStatistics__rating',
      '.RatingStars__average',
      '.BookRatingStars [aria-label*="rating"]'
    ]
  },
  rating_count: {
    primary: '[data-testid="ratingsCount"]',
    fallbacks: [
      '.RatingStatistics__meta span',
      '.BookRatingStars [aria-label*="ratings"]'
    ]
  },
  review_count: {
    primary: '[data-testid="reviewsCount"]',
    fallbacks: [
      '.ReviewsCount'
    ]
  },
  format: {
    primary: '[data-testid="pagesFormat"]',
    fallbacks: [
      '.FeatureDetails [data-testid="format"]'
    ]
  },
  pages: {
    primary: '[data-testid="pagesFormat"]',
    fallbacks: [
      '.FeatureDetails [data-testid="pages"]'
    ]
  },
  isbn: {
    primary: '.TruncatedContent [data-testid="isbn"]',
    fallbacks: [
      '.FeatureDetails [data-testid="isbn"]'
    ]
  },
  isbn13: {
    primary: '.TruncatedContent [data-testid="isbn13"]',
    fallbacks: [
      '.FeatureDetails [data-testid="isbn13"]'
    ]
  },
  awards: {
    primary: '.DescListItem dt:contains("Literary awards") + dd',
    fallbacks: [
      '.BookDetails__list .DescListItem dt:contains("Literary awards") + dd',
      '.Awards'
    ]
  },
  characters: {
    primary: '.DescListItem dt:contains("Characters") + dd',
    fallbacks: [
      '.BookDetails__list .DescListItem dt:contains("Characters") + dd',
      '.Characters'
    ]
  },
  settings: {
    primary: '.DescListItem dt:contains("Setting") + dd',
    fallbacks: [
      '.BookDetails__list .DescListItem dt:contains("Setting") + dd',
      '.Settings'
    ]
  }
};

/**
 * Extract text content using primary selector and fallbacks
 */
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

/**
 * Extract book metadata from page using selectors
 * This function runs in the browser context via page.evaluate()
 */
function extractBookMetadata() {
  try {
    const metadata = {
      title: '',
      author: '',
      publisher: '',
      publication_date: '',
      rating: null,
      rating_count: 0,
      review_count: 0,
      format: '',
      pages: null,
      isbn: '',
      isbn13: '',
      series: '',
      awards: [],
      characters: [],
      settings: [],
      _raw: {} // Store raw values for debugging
    };

    // Helper function to clean text
    function cleanText(text) {
      if (!text) return '';
      return text.trim()
        .replace(/\s+/g, ' ') // Normalize whitespace
        .replace(/[\u200B-\u200D\uFEFF]/g, ''); // Remove zero-width spaces
    }

    // Extract each field using selectors
    for (const [field, selectors] of Object.entries(SELECTORS)) {
      const rawValue = extractWithFallbacks(selectors);
      if (!rawValue) continue;

      // Store raw value for debugging
      metadata._raw[field] = rawValue;
      const value = cleanText(rawValue);

      // Handle special cases
      switch (field) {
        case 'rating':
          const ratingMatch = value.match(/(\d+\.?\d*)/);
          metadata.rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;
          break;

        case 'rating_count':
          const ratingCountMatch = value.match(/([0-9,]+)/);
          metadata.rating_count = ratingCountMatch ?
            parseInt(ratingCountMatch[1].replace(/,/g, '')) : 0;
          break;

        case 'review_count':
          const reviewCountMatch = value.match(/([0-9,]+)/);
          metadata.review_count = reviewCountMatch ?
            parseInt(reviewCountMatch[1].replace(/,/g, '')) : 0;
          break;

        case 'pages':
          const pagesMatch = value.match(/(\d+)/);
          metadata.pages = pagesMatch ? parseInt(pagesMatch[1]) : null;
          break;

        case 'format':
          const formatMatch = value.match(/(Hardcover|Paperback|Kindle Edition|ebook|Audiobook)/i);
          metadata.format = formatMatch ? formatMatch[1] : '';
          break;

        case 'publisher':
          // Handle "Published by Publisher (Year)" format
          const publisherMatch = value.match(/(?:Published by\s+)?([^,(]+)(?:\s*\(|$)/i);
          metadata.publisher = publisherMatch ? cleanText(publisherMatch[1]) : '';
          break;

        case 'publication_date':
          // Handle various date formats including "First published"
          const dateMatch = value.match(/(?:First published|Published)\s+(?:in\s+)?([A-Z][a-z]+ \d{1,2},? \d{4}|[A-Z][a-z]+ \d{4}|\d{4}(?:-\d{2}-\d{2})?)/i);
          metadata.publication_date = dateMatch ? cleanText(dateMatch[1]) : '';
          break;

        case 'awards':
        case 'characters':
        case 'settings':
          // Split on commas and clean
          metadata[field] = value.split(/[,;]/)
            .map(item => cleanText(item))
            .filter(item => item.length > 0);
          break;

        default:
          metadata[field] = value;
      }
    }

    return metadata;
  } catch (error) {
    console.error('Error extracting metadata:', error);
    return null;
  }
}

module.exports = {
  SELECTORS,
  extractBookMetadata
};