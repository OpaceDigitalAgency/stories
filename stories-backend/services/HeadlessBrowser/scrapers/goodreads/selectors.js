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
function extractWithFallbacks(page, selectorObj) {
  const { primary, fallbacks } = selectorObj;
  let element = page.querySelector(primary);
  
  if (!element && fallbacks) {
    for (const fallback of fallbacks) {
      element = page.querySelector(fallback);
      if (element) break;
    }
  }

  return element ? element.textContent.trim() : null;
}

/**
 * Extract book metadata from page using selectors
 */
async function extractBookMetadata(page) {
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
    awards: [],
    selectors_used: {}
  };

  // Extract each field using selectors
  for (const [field, selectors] of Object.entries(SELECTORS)) {
    const value = extractWithFallbacks(page, selectors);
    if (value) {
      // Handle special cases
      switch (field) {
        case 'rating':
          metadata.rating = parseFloat(value);
          break;
        case 'ratingCount':
        case 'reviewCount':
          metadata[field] = parseInt(value.replace(/[^0-9]/g, ''));
          break;
        case 'pages':
          const pagesMatch = value.match(/(\d+)\s+pages/);
          if (pagesMatch) {
            metadata.pages = parseInt(pagesMatch[1]);
          }
          break;
        case 'format':
          const formatMatch = value.match(/,\s*([^,]+)$/);
          if (formatMatch) {
            metadata.format = formatMatch[1].trim();
          }
          break;
        case 'publisher':
          const publisherMatch = value.match(/by\s+([^(]+)/);
          if (publisherMatch) {
            metadata.publisher = publisherMatch[1].trim();
          }
          break;
        case 'publicationDate':
          const dateMatch = value.match(/published\s+([^(]+)/i);
          if (dateMatch) {
            metadata.publication_date = dateMatch[1].trim();
          }
          break;
        case 'awards':
        case 'characters':
        case 'settings':
          metadata[field] = value.split(',').map(item => item.trim());
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