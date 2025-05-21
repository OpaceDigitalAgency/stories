/**
 * Goodreads GraphQL queries and processing
 */
const logger = require('../../utils/logger');

// GraphQL query for book data and reviews
const BOOK_QUERY = `
query BookPageQuery($workId: ID!) {
  work(id: $workId) {
    id
    title
    originalTitle
    description
    language {
      name
      code
    }
    details {
      format
      pages
      publishedDate
      publisher {
        name
      }
      isbn10
      isbn13
      awards {
        name
        year
      }
      series {
        name
        seriesIndex
      }
    }
    stats {
      ratingsCount
      reviewsCount
      averageRating
    }
    reviews(first: 50) {
      totalCount
      pageInfo {
        hasNextPage
        endCursor
      }
      edges {
        node {
          id
          rating
          text
          createdAt
          updatedAt
          user {
            name
            imageUrl
          }
        }
      }
    }
  }
}`;

/**
 * Process GraphQL response into standardized format
 */
function processGraphQLResponse(response) {
  if (!response.data || !response.data.work) {
    logger.warn('Invalid GraphQL response structure');
    return {
      metadata: {},
      reviews: [],
      hasMore: false,
      nextCursor: null
    };
  }

  const work = response.data.work;

  // Extract metadata
  const metadata = {
    title: work.title || work.originalTitle,
    description: work.description,
    language: work.language?.name,
    format: work.details?.format,
    pages: work.details?.pages,
    publication_date: work.details?.publishedDate,
    publisher: work.details?.publisher?.name,
    isbn: work.details?.isbn10,
    isbn13: work.details?.isbn13,
    awards: work.details?.awards?.map(a => a.name) || [],
    series: work.details?.series?.name,
    rating: work.stats?.averageRating,
    rating_count: work.stats?.ratingsCount,
    review_count: work.stats?.reviewsCount
  };

  // Helper function to clean review text
  function cleanReviewText(text) {
    if (!text) return '';

    // Replace escaped HTML entities
    let cleaned = text
      .replace(/\\u003c/g, '<')
      .replace(/\\u003e/g, '>')
      .replace(/\\u0026/g, '&')
      .replace(/\\u0027/g, "'")
      .replace(/\\u0022/g, '"')
      .replace(/\\n/g, ' ')
      .replace(/\\t/g, ' ');

    // Remove HTML tags
    cleaned = cleaned.replace(/<[^>]*>/g, ' ');

    // Normalize whitespace
    cleaned = cleaned.replace(/\s+/g, ' ').trim();

    return cleaned;
  }

  // Extract reviews
  const reviews = work.reviews?.edges?.map(edge => {
    const node = edge.node;
    return {
      reviewer_name: node.user?.name || 'Goodreads User',
      rating: node.rating || 0,
      rating_normalised: (node.rating || 0) / 5,
      review_text: cleanReviewText(node.text || ''),
      review_date: node.updatedAt || node.createdAt || new Date().toISOString().split('T')[0],
      metadata: JSON.stringify({
        reviewer_image: node.user?.imageUrl,
        review_id: node.id
      })
    };
  }).filter(review => review.rating > 0 && review.review_text.length > 10) || [];

  return {
    metadata,
    reviews,
    hasMore: work.reviews?.pageInfo?.hasNextPage || false,
    nextCursor: work.reviews?.pageInfo?.endCursor || null
  };
}

/**
 * Make GraphQL request with proper headers and error handling
 */
async function makeGraphQLRequest(page, workId, cursor = null) {
  const variables = {
    workId,
    reviewsFirst: 50,
    reviewsAfter: cursor
  };

  try {
    const response = await page.evaluate(
      async ({ query, variables }) => {
        const response = await fetch('https://www.goodreads.com/graphql', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            operationName: 'BookPageQuery',
            query,
            variables
          })
        });
        return response.json();
      },
      { query: BOOK_QUERY, variables }
    );

    return processGraphQLResponse(response);
  } catch (error) {
    logger.error(`GraphQL request failed: ${error.message}`);
    return null;
  }
}

module.exports = {
  BOOK_QUERY,
  processGraphQLResponse,
  makeGraphQLRequest
};