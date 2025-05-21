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
  publicationDate: {
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
      '.RatingStars__average'
    ]
  },
  ratingCount: {
    primary: '[data-testid="ratingsCount"]',
    fallbacks: [
      '.RatingStatistics__meta span'
    ]
  },
  reviewCount: {
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
    selectors_used: {},
    _raw: {} // Store raw values for debugging
  };

  // Extract each field using selectors
  // SELECTORS is injected into page context
  for (const [field, selectors] of Object.entries(SELECTORS)) {
    const value = extractWithFallbacks(selectors);
    if (value) {
      // Store raw value for debugging
      metadata._raw[field] = value;
      // Handle special cases
      switch (field) {
        case 'rating':
          metadata.rating = parseFloat(value);
          break;
        case 'ratingCount':
          // Handle "187,550 ratings" format
          const ratingMatch = value.match(/([0-9,]+)\s+ratings?/);
          if (ratingMatch) {
            metadata.rating_count = parseInt(ratingMatch[1].replace(/,/g, ''));
          }
          break;
        case 'reviewCount':
          // Handle "16,653 reviews" format
          const reviewMatch = value.match(/([0-9,]+)\s+reviews?/);
          if (reviewMatch) {
            metadata.review_count = parseInt(reviewMatch[1].replace(/,/g, ''));
          }
          break;
        case 'pages':
          const pagesMatch = value.match(/(\d+)\s+pages/);
          if (pagesMatch) {
            metadata.pages = parseInt(pagesMatch[1]);
          }
          break;
        case 'format':
          // Extract format (e.g. "Hardcover", "Paperback")
          const formatMatch = value.match(/(Hardcover|Paperback|Kindle Edition|ebook|Audiobook)/i);
          if (formatMatch) {
            metadata.format = formatMatch[1];
          }
          break;
        case 'publisher':
          // Handle both "by Publisher" and direct "Publisher" formats
          const publisherMatch = value.match(/(?:by\s+)?([^,(]+)(?:\s*\(|$)/);
          if (publisherMatch) {
            metadata.publisher = publisherMatch[1].trim();
          }
          break;
        case 'publicationDate':
          // Handle various date formats
          const dateMatch = value.match(/(?:published\s+)?([A-Z][a-z]+ \d{1,2}, \d{4}|[A-Z][a-z]+ \d{4}|\d{4}-\d{2}-\d{2})/i);
          if (dateMatch) {
            metadata.publication_date = dateMatch[1].trim();
          }
          break;
        case 'awards':
          // Split on commas and semicolons, clean up each award
          metadata.awards = value.split(/[,;]/)
            .map(award => award.trim())
            .filter(award => award.length > 0);
          break;
        case 'characters':
          // Split on commas and semicolons, clean up each character
          metadata.characters = value.split(/[,;]/)
            .map(char => char.trim())
            .filter(char => char.length > 0);
          break;
        case 'settings':
          // Split on commas and handle parenthetical locations
          metadata.settings = value.split(',')
            .map(setting => {
              const match = setting.match(/([^(]+)(?:\s*\(([^)]+)\))?/);
              if (match) {
                const [, location, context] = match;
                return context ? `${location.trim()} (${context.trim()})` : location.trim();
              }
              return setting.trim();
            })
            .filter(setting => setting.length > 0);
          break;
        default:
          metadata[field] = value;
      }
      metadata.selectors_used[field] = selectors.primary;
    }
  }

  return metadata;
}

module.exports = {
  SELECTORS,
  extractBookMetadata
};