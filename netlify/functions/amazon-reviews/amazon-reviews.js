const chromium = require('@sparticuz/chromium');
const puppeteer = require('puppeteer-core');

// Configure Puppeteer to use Chromium
const getBrowser = async () => {
  return puppeteer.launch({
    args: chromium.args,
    defaultViewport: chromium.defaultViewport,
    executablePath: await chromium.executablePath(),
    headless: chromium.headless,
    ignoreHTTPSErrors: true,
  });
};

// Helper: Parse reviews from HTML using regex
const parseReviewsWithRegex = (html, asin) => {
  const reviews = [];
  
  // Match review blocks
  const reviewRegex = /<div[^>]*data-hook="review"[^>]*>([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/gi;
  let reviewMatch;
  
  while ((reviewMatch = reviewRegex.exec(html)) !== null) {
    const reviewBlock = reviewMatch[0];
    
    // Extract rating
    const ratingRegex = /<i[^>]*class="[^"]*a-icon-star[^"]*"[^>]*><span[^>]*>([0-9.]+) out of 5 stars<\/span><\/i>/i;
    const ratingMatch = reviewBlock.match(ratingRegex);
    const rating = ratingMatch ? parseFloat(ratingMatch[1]) : null;
    
    // Extract title
    const titleRegex = /<a[^>]*data-hook="review-title"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/a>/i;
    const titleMatch = reviewBlock.match(titleRegex);
    const title = titleMatch ? titleMatch[1].trim() : '';
    
    // Extract reviewer name
    const nameRegex = /<span[^>]*class="a-profile-name"[^>]*>([\s\S]*?)<\/span>/i;
    const nameMatch = reviewBlock.match(nameRegex);
    const reviewerName = nameMatch ? nameMatch[1].trim() : 'Anonymous';
    
    // Extract review date
    const dateRegex = /<span[^>]*data-hook="review-date"[^>]*>([\s\S]*?)<\/span>/i;
    const dateMatch = reviewBlock.match(dateRegex);
    let reviewDate = dateMatch ? dateMatch[1].trim() : '';
    
    // Convert date format (e.g., "Reviewed in the United Kingdom on 15 April 2023")
    if (reviewDate) {
      const dateOnlyMatch = reviewDate.match(/on\s+(\d+\s+[A-Za-z]+\s+\d{4})/i);
      if (dateOnlyMatch) {
        reviewDate = dateOnlyMatch[1];
      }
    }
    
    // Extract review text
    const textRegex = /<span[^>]*data-hook="review-body"[^>]*><span[^>]*>([\s\S]*?)<\/span><\/span>/i;
    const textMatch = reviewBlock.match(textRegex);
    const reviewText = textMatch ? textMatch[1].trim() : '';
    
    // Only add reviews with valid ratings
    if (rating !== null) {
      reviews.push({
        source_id: 1, // Amazon source ID
        reviewer_name: reviewerName,
        review_date: reviewDate,
        original_rating: `${rating}/5`,
        rating_value: rating,
        rating_scale: 5,
        rating_normalised: rating / 5, // Normalize to 0-1 scale
        review_text: reviewText,
        review_title: title,
        metadata: JSON.stringify({
          asin: asin,
          review_url: `https://www.amazon.co.uk/product-reviews/${asin}`,
          affiliate_url: `https://www.amazon.co.uk/dp/${asin}?tag=storiesfro0f0-20`,
        }),
      });
    }
  }
  
  return reviews;
};

// Helper: Extract aggregate rating
const extractAggregateRating = (html, asin) => {
  // Extract average rating
  const ratingRegex = /(\d+\.\d+) out of 5 stars/i;
  const ratingMatch = html.match(ratingRegex);
  
  if (!ratingMatch) {
    return null;
  }
  
  const avg = parseFloat(ratingMatch[1]);
  
  // Extract number of ratings
  const countRegex = /(\d+(?:,\d+)*) ratings?/i;
  const countMatch = html.match(countRegex);
  const count = countMatch ? parseInt(countMatch[1].replace(/,/g, '')) : 1;
  
  return {
    source_id: 1, // Amazon source ID
    reviewer_name: 'Amazon Aggregate',
    review_date: new Date().toISOString().split('T')[0],
    original_rating: `${avg}/5`,
    rating_value: avg,
    rating_scale: 5,
    rating_normalised: avg / 5,
    review_text: `Average rating ${avg}/5 based on ${count} ratings on Amazon.`,
    metadata: JSON.stringify({
      asin: asin,
      review_url: `https://www.amazon.co.uk/product-reviews/${asin}`,
      affiliate_url: `https://www.amazon.co.uk/dp/${asin}?tag=storiesfro0f0-20`,
      is_aggregate: true,
      ratings_count: count,
    }),
  };
};

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
    const { asin, limit = 10, domain = 'www.amazon.co.uk' } = body;
    
    if (!asin) {
      return {
        statusCode: 400,
        body: JSON.stringify({ error: 'ASIN is required' }),
      };
    }
    
    console.log(`Scraping reviews for ASIN: ${asin} from ${domain}`);
    
    // Launch browser
    browser = await getBrowser();
    const page = await browser.newPage();
    
    // Set user agent to avoid detection
    await page.setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Set extra headers to avoid detection
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'en-GB,en-US;q=0.9,en;q=0.8',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
      'Connection': 'keep-alive',
      'Cache-Control': 'max-age=0',
    });
    
    // Navigate to reviews page
    const reviewsUrl = `https://${domain}/product-reviews/${asin}`;
    console.log(`Navigating to: ${reviewsUrl}`);
    await page.goto(reviewsUrl, { waitUntil: 'networkidle2', timeout: 15000 });
    
    // Check if we're on a login page
    const isLoginPage = await page.evaluate(() => {
      return document.body.innerHTML.includes('Sign-In') || 
             document.body.innerHTML.includes('sign-in') ||
             document.body.innerHTML.includes('Sign in');
    });
    
    if (isLoginPage) {
      console.log('Detected login page, trying product page instead');
      
      // Try the product page instead
      const productUrl = `https://${domain}/dp/${asin}`;
      await page.goto(productUrl, { waitUntil: 'networkidle2', timeout: 15000 });
      
      // Check if still on login page
      const stillLoginPage = await page.evaluate(() => {
        return document.body.innerHTML.includes('Sign-In') || 
               document.body.innerHTML.includes('sign-in') ||
               document.body.innerHTML.includes('Sign in');
      });
      
      if (stillLoginPage) {
        console.log('Still on login page, returning error');
        return {
          statusCode: 403,
          body: JSON.stringify({ 
            error: 'Login page detected',
            reviews: [] 
          }),
        };
      }
    }
    
    // Get page content
    const content = await page.content();
    
    // Parse reviews
    let reviews = parseReviewsWithRegex(content, asin);
    console.log(`Found ${reviews.length} reviews on first page`);
    
    // If we need more reviews and have less than the limit, try more pages
    let page_num = 2;
    while (reviews.length < limit && page_num <= 5) {
      try {
        const nextPageUrl = `https://${domain}/product-reviews/${asin}?pageNumber=${page_num}`;
        console.log(`Navigating to page ${page_num}: ${nextPageUrl}`);
        await page.goto(nextPageUrl, { waitUntil: 'networkidle2', timeout: 10000 });
        
        const pageContent = await page.content();
        const pageReviews = parseReviewsWithRegex(pageContent, asin);
        
        if (pageReviews.length === 0) {
          console.log(`No more reviews found on page ${page_num}`);
          break;
        }
        
        console.log(`Found ${pageReviews.length} reviews on page ${page_num}`);
        reviews = [...reviews, ...pageReviews];
        page_num++;
        
        // Add a small delay between pages
        await new Promise(resolve => setTimeout(resolve, 1000));
      } catch (err) {
        console.log(`Error fetching page ${page_num}: ${err.message}`);
        break;
      }
    }
    
    // If no reviews found, try to get aggregate rating
    if (reviews.length === 0) {
      console.log('No individual reviews found, trying to get aggregate rating');
      const aggregate = extractAggregateRating(content, asin);
      
      if (aggregate) {
        console.log('Found aggregate rating');
        reviews = [aggregate];
      }
    }
    
    // Return reviews (limited to requested amount)
    return {
      statusCode: 200,
      body: JSON.stringify({ 
        reviews: reviews.slice(0, limit),
        total: reviews.length
      }),
    };
    
  } catch (error) {
    console.log(`Error: ${error.message}`);
    return {
      statusCode: 500,
      body: JSON.stringify({ error: error.message, reviews: [] }),
    };
  } finally {
    // Close browser
    if (browser !== null) {
      await browser.close();
    }
  }
};
