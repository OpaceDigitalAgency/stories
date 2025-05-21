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
      fallbacks: ['.BookPageTitleSection__authorLink', '.AuthorLink__name']
    },
    rating: {
      primary: '[data-testid="averageRating"]',
      fallbacks: ['.RatingStatistics__rating', '.RatingStars__average', '.BookRatingStars [aria-label*="rating"]']
    },
    description: {
      primary: '[data-testid="description"] span',
      fallbacks: []
    },
    genres: {
      primary: '.BookPageMetadataSection__genreShelf',
      fallbacks: ['.bookPageGenreLink']
    }
  };

  const metadata = {
    title: '',
    author: '',
    rating: null,
    description: '',
    genres: []
  };

  for (const [field, selectors] of Object.entries(SELECTORS)) {
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
      default:
        metadata[field] = value;
    }
  }

  return metadata;
});