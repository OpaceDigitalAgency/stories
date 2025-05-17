const axios = require('axios');

// Outscraper API key
const OUTSCRAPER_API_KEY = 'NTNjYjkxMTUwOWI3NDBlYzg2MmI5NzY2ZTYxNDYxMTl8ZmVjODc2ZDI5ZA';

// Main handler function
exports.handler = async (event, context) => {
  // Only allow POST requests
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      body: JSON.stringify({ error: 'Method Not Allowed' }),
    };
  }

  try {
    // Parse the request body
    const body = JSON.parse(event.body);
    const goodreadsUrl = body.goodreadsUrl;
    const limit = body.limit || 50;
    const maxPages = body.maxPages || 5;

    if (!goodreadsUrl) {
      return {
        statusCode: 400,
        body: JSON.stringify({ error: 'Missing goodreadsUrl parameter' }),
      };
    }

    console.log(`Starting Goodreads scraping for URL: ${goodreadsUrl}`);
    console.log(`Requested limit: ${limit}, Max pages: ${maxPages}`);

    // Extract book ID from URL
    const bookId = extractBookIdFromUrl(goodreadsUrl);
    if (!bookId) {
      return {
        statusCode: 400,
        body: JSON.stringify({ error: 'Invalid Goodreads URL. Could not extract book ID.' }),
      };
    }

    // Prepare the request to Outscraper API
    const response = await fetchReviewsFromOutscraper(bookId, limit);
    
    // Process the response
    const { bookTitle, reviews } = processOutscraperResponse(response, limit);
    
    // Return the reviews
    return {
      statusCode: 200,
      body: JSON.stringify({
        book_title: bookTitle,
        total: reviews.length,
        reviews: reviews
      }),
    };
  } catch (error) {
    console.log(`Error: ${error.message}`);
    
    return {
      statusCode: 500,
      body: JSON.stringify({ error: `Failed to scrape reviews: ${error.message}` }),
    };
  }
};

// Function to extract book ID from Goodreads URL
function extractBookIdFromUrl(url) {
  // Try to extract book ID from URL
  const patterns = [
    /goodreads\.com\/book\/show\/(\d+)/,
    /goodreads\.com\/book\/show\/([\w.-]+)/
  ];
  
  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match && match[1]) {
      return match[1];
    }
  }
  
  return null;
}

// Function to fetch reviews from Outscraper API
async function fetchReviewsFromOutscraper(bookId, limit) {
  console.log(`Fetching reviews for book ID: ${bookId} from Outscraper API`);
  
  try {
    // Create the request to Outscraper API
    const response = await axios.post(
      'https://api.outscraper.com/api/v1/goodreads/reviews',
      {
        query: [bookId],
        limit: limit,
        async: false
      },
      {
        headers: {
          'X-API-KEY': OUTSCRAPER_API_KEY,
          'Content-Type': 'application/json'
        }
      }
    );
    
    console.log(`Outscraper API response status: ${response.status}`);
    
    if (response.status !== 200) {
      throw new Error(`Outscraper API returned status ${response.status}`);
    }
    
    return response.data;
  } catch (error) {
    console.error(`Error fetching from Outscraper: ${error.message}`);
    if (error.response) {
      console.error(`Response data: ${JSON.stringify(error.response.data)}`);
    }
    throw error;
  }
}

// Function to process Outscraper API response
function processOutscraperResponse(response, limit) {
  // Check if we have valid data
  if (!response || !response.data || !Array.isArray(response.data) || response.data.length === 0) {
    throw new Error('Invalid response from Outscraper API');
  }
  
  const bookData = response.data[0];
  const bookTitle = bookData.title || 'Unknown Book';
  
  // Extract reviews
  const reviews = [];
  
  if (bookData.reviews && Array.isArray(bookData.reviews)) {
    console.log(`Found ${bookData.reviews.length} reviews from Outscraper`);
    
    bookData.reviews.forEach(review => {
      // Skip reviews without text or rating
      if (!review.text || !review.rating) {
        return;
      }
      
      // Format the review
      reviews.push({
        reviewer_name: review.author || 'Goodreads User',
        rating: parseFloat(review.rating),
        review_text: review.text,
        review_date: review.date || new Date().toISOString().split('T')[0]
      });
    });
  }
  
  // Add aggregate rating if available
  if (bookData.rating) {
    const aggregateRating = {
      reviewer_name: 'Goodreads Aggregate',
      rating: parseFloat(bookData.rating),
      review_text: `This book has an average rating of ${bookData.rating}/5 based on ${bookData.ratings_count || 0} ratings and ${bookData.reviews_count || 0} reviews on Goodreads.`,
      review_date: new Date().toISOString().split('T')[0]
    };
    
    reviews.unshift(aggregateRating);
  }
  
  // Limit the number of reviews
  const limitedReviews = reviews.slice(0, limit);
  
  return {
    bookTitle,
    reviews: limitedReviews
  };
}
