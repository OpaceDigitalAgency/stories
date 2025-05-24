/**
 * Scrape reviews using a pre-provided resource ID (more efficient)
 */
async function scrapeReviewsWithResourceId(page, options = {}) {
    console.log(`[GraphQL] Starting GraphQL scrape with resource ID`);
    console.log(`[GraphQL] Options:`, JSON.stringify(options, null, 2));
    
    const maxReviews = options.maxReviews || 50;
    const nextPageToken = options.nextPageToken || null;
    const startPage = options.startPage || 1;
    const resourceId = options.resourceId;
    
    if (!resourceId) {
        console.error('[GraphQL] No resource ID provided');
        return { reviews: [], hasMore: false, nextPageToken: null };
    }
    
    console.log(`[GraphQL] Using resource ID: ${resourceId}`);
    console.log(`[GraphQL] Pagination state - nextPageToken: ${nextPageToken}, startPage: ${startPage}`);
    
    try {
        // Set up headers for GraphQL request (matching the working cURL exactly)
        const headers = {
            'Content-Type': 'application/json',
            'Accept': '*/*',
            'Sec-Fetch-Site': 'cross-site',
            'Accept-Language': 'en-GB,en;q=0.9',
            'Sec-Fetch-Mode': 'cors',
            'Accept-Encoding': 'gzip, deflate, br',
            'Origin': 'https://www.goodreads.com',
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15',
            'Referer': 'https://www.goodreads.com/',
            'Sec-Fetch-Dest': 'empty',
            'Priority': 'u=3, i',
            'x-api-key': 'da2-xpgsdydkbregjhpr6ejzqdhuwy'
        };
        
        // Build the GraphQL query (exact copy from working cURL)
        const query = `query getReviews($filters: BookReviewsFilterInput!, $pagination: PaginationInput) {
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
      displayName
      editable
      default
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
        
        // Build variables (exact structure from working cURL)
        const variables = {
            filters: {
                resourceType: "WORK",
                resourceId: resourceId
            },
            pagination: {
                limit: Math.min(maxReviews, 30) // Goodreads seems to limit to 30 per request
            }
        };
        
        // Add pagination token if we have one
        if (nextPageToken) {
            variables.pagination.after = nextPageToken;
        }
        
        console.log(`[GraphQL] Making request with variables:`, JSON.stringify(variables, null, 2));
        
        // Make the GraphQL request
        const response = await page.evaluate(async (url, headers, query, variables) => {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({
                        operationName: 'getReviews',
                        variables: variables,
                        query: query
                    })
                });
                
                const text = await response.text();
                console.log(`[GraphQL] Response status: ${response.status}`);
                console.log(`[GraphQL] Response text: ${text.substring(0, 500)}...`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                }
                
                return JSON.parse(text);
            } catch (error) {
                console.error('[GraphQL] Request failed:', error);
                throw error;
            }
        }, 'https://kxbwmqov6jgg3daaamb744ycu4.appsync-api.us-east-1.amazonaws.com/graphql', headers, query, variables);
        
        console.log(`[GraphQL] Successfully fetched GraphQL data`);
        
        if (!response.data || !response.data.getReviews) {
            console.log('[GraphQL] No reviews data in response');
            return { reviews: [], hasMore: false, nextPageToken: null };
        }
        
        const reviewsData = response.data.getReviews;
        const edges = reviewsData.edges || [];
        const pageInfo = reviewsData.pageInfo || {};
        
        console.log(`[GraphQL] Found ${edges.length} reviews`);
        console.log(`[GraphQL] Page info:`, JSON.stringify(pageInfo, null, 2));
        
        // Convert GraphQL reviews to our format
        const reviews = edges.map(edge => {
            const review = edge.node;
            const creator = review.creator || {};
            
            return {
                id: review.id,
                reviewer_name: creator.name || 'Anonymous',
                rating_value: review.rating || null,
                review_text: review.text || '',
                review_date: review.createdAt || review.updatedAt || null,
                helpful_count: review.likeCount || 0,
                verified_purchase: false, // GraphQL doesn't provide this
                review_url: creator.webUrl || '',
                metadata: {
                    source: 'graphql',
                    graphql_id: review.id,
                    creator_id: creator.id,
                    spoiler_status: review.spoilerStatus,
                    comment_count: review.commentCount,
                    like_count: review.likeCount,
                    viewer_has_liked: review.viewerHasLiked,
                    next_token: pageInfo.nextPageToken,
                    graphql_page: startPage
                }
            };
        });
        
        console.log(`[GraphQL] Converted ${reviews.length} reviews`);
        
        return {
            reviews: reviews,
            hasMore: !!pageInfo.nextPageToken,
            nextPageToken: pageInfo.nextPageToken,
            totalCount: reviewsData.totalCount
        };
        
    } catch (error) {
        console.error('[GraphQL] Error during GraphQL scraping:', error);
        return { reviews: [], hasMore: false, nextPageToken: null };
    }
}

async function scrapeReviews(page, isbn, options = {}) {
    console.log(`[GraphQL] Starting GraphQL scrape for ISBN: ${isbn}`);
    console.log(`[GraphQL] Options:`, JSON.stringify(options, null, 2));
    
    const maxReviews = options.maxReviews || 50;
    const nextPageToken = options.nextPageToken || null;
    const startPage = options.startPage || 1;
    
    console.log(`[GraphQL] Pagination state - nextPageToken: ${nextPageToken}, startPage: ${startPage}`);
    
    try {
        // Navigate to the book page first to get the resource ID
        const bookUrl = `https://www.goodreads.com/book/isbn/${isbn}`;
        console.log(`[GraphQL] Navigating to: ${bookUrl}`);
        
        await page.goto(bookUrl, { 
            waitUntil: 'networkidle0',
            timeout: 30000 
        });
        
        // Wait a bit for the page to fully load
        await page.waitForTimeout(2000);
        
        // Extract resource ID from the page - need to find the kca://work format
        const resourceId = await page.evaluate(() => {
            // Look for the new resource ID format in scripts
            const scripts = document.querySelectorAll('script');
            for (let script of scripts) {
                const content = script.textContent || script.innerText;
                
                // Look for the kca://work format
                const kcaMatch = content.match(/kca:\/\/work\/amzn1\.gr\.work\.v1\.[A-Za-z0-9_-]+/);
                if (kcaMatch) {
                    console.log('[GraphQL] Found kca resource ID:', kcaMatch[0]);
                    return kcaMatch[0];
                }
                
                // Look for work ID to construct the resource ID
                const workIdMatch = content.match(/work_id['":\s]*(\d+)/);
                if (workIdMatch) {
                    // Try to find the corresponding resource ID
                    const workId = workIdMatch[1];
                    console.log('[GraphQL] Found work ID:', workId);
                    
                    // Look for the resource ID pattern near the work ID
                    const resourceMatch = content.match(new RegExp(`amzn1\\.gr\\.work\\.v1\\.[A-Za-z0-9_-]+`));
                    if (resourceMatch) {
                        const fullResourceId = `kca://work/${resourceMatch[0]}`;
                        console.log('[GraphQL] Constructed resource ID:', fullResourceId);
                        return fullResourceId;
                    }
                }
            }
            
            return null;
        });
        
        if (!resourceId) {
            console.log('[GraphQL] Could not find resource ID, falling back to HTML scraping');
            return { reviews: [], hasMore: false, nextPageToken: null };
        }
        
        console.log(`[GraphQL] Found resource ID: ${resourceId}`);
        
        // Set up headers for GraphQL request (matching the working cURL exactly)
        const headers = {
            'Content-Type': 'application/json',
            'Accept': '*/*',
            'Sec-Fetch-Site': 'cross-site',
            'Accept-Language': 'en-GB,en;q=0.9',
            'Sec-Fetch-Mode': 'cors',
            'Accept-Encoding': 'gzip, deflate, br',
            'Origin': 'https://www.goodreads.com',
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15',
            'Referer': 'https://www.goodreads.com/',
            'Sec-Fetch-Dest': 'empty',
            'Priority': 'u=3, i',
            'x-api-key': 'da2-xpgsdydkbregjhpr6ejzqdhuwy'
        };
        
        // Build the GraphQL query (exact copy from working cURL)
        const query = `query getReviews($filters: BookReviewsFilterInput!, $pagination: PaginationInput) {
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
      displayName
      editable
      default
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
        
        // Build variables (exact structure from working cURL)
        const variables = {
            filters: {
                resourceType: "WORK",
                resourceId: resourceId
            },
            pagination: {
                limit: Math.min(maxReviews, 30) // Goodreads seems to limit to 30 per request
            }
        };
        
        // Add pagination token if we have one
        if (nextPageToken) {
            variables.pagination.after = nextPageToken;
        }
        
        console.log(`[GraphQL] Making request with variables:`, JSON.stringify(variables, null, 2));
        
        // Make the GraphQL request
        const response = await page.evaluate(async (url, headers, query, variables) => {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: headers,
                    body: JSON.stringify({
                        operationName: 'getReviews',
                        variables: variables,
                        query: query
                    })
                });
                
                const text = await response.text();
                console.log(`[GraphQL] Response status: ${response.status}`);
                console.log(`[GraphQL] Response text: ${text.substring(0, 500)}...`);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                }
                
                return JSON.parse(text);
            } catch (error) {
                console.error('[GraphQL] Request failed:', error);
                throw error;
            }
        }, 'https://kxbwmqov6jgg3daaamb744ycu4.appsync-api.us-east-1.amazonaws.com/graphql', headers, query, variables);
        
        console.log(`[GraphQL] Successfully fetched GraphQL data`);
        
        if (!response.data || !response.data.getReviews) {
            console.log('[GraphQL] No reviews data in response');
            return { reviews: [], hasMore: false, nextPageToken: null };
        }
        
        const reviewsData = response.data.getReviews;
        const edges = reviewsData.edges || [];
        const pageInfo = reviewsData.pageInfo || {};
        
        console.log(`[GraphQL] Found ${edges.length} reviews`);
        console.log(`[GraphQL] Page info:`, JSON.stringify(pageInfo, null, 2));
        
        // Convert GraphQL reviews to our format
        const reviews = edges.map(edge => {
            const review = edge.node;
            const creator = review.creator || {};
            
            return {
                id: review.id,
                reviewer_name: creator.name || 'Anonymous',
                rating_value: review.rating || null,
                review_text: review.text || '',
                review_date: review.createdAt || review.updatedAt || null,
                helpful_count: review.likeCount || 0,
                verified_purchase: false, // GraphQL doesn't provide this
                review_url: creator.webUrl || '',
                metadata: {
                    source: 'graphql',
                    graphql_id: review.id,
                    creator_id: creator.id,
                    spoiler_status: review.spoilerStatus,
                    comment_count: review.commentCount,
                    like_count: review.likeCount,
                    viewer_has_liked: review.viewerHasLiked,
                    next_token: pageInfo.nextPageToken,
                    graphql_page: startPage
                }
            };
        });
        
        console.log(`[GraphQL] Converted ${reviews.length} reviews`);
        
        return {
            reviews: reviews,
            hasMore: !!pageInfo.nextPageToken,
            nextPageToken: pageInfo.nextPageToken,
            totalCount: reviewsData.totalCount
        };
        
    } catch (error) {
        console.error('[GraphQL] Error during GraphQL scraping:', error);
        return { reviews: [], hasMore: false, nextPageToken: null };
    }
}

/**
 * Legacy function for compatibility with existing index.js
 * Bridges the old makeGraphQLRequest interface to our new scrapeReviews implementation
 */
async function makeGraphQLRequest(page, workId, cursor = null) {
    try {
        console.log(`[GraphQL] makeGraphQLRequest called with workId: ${workId}, cursor: ${cursor}`);
        
        // Convert workId to the resource ID format we need
        // workId comes in as "work/amzn1.gr.work.v1.xxxxx"
        // We need it as "kca://work/amzn1.gr.work.v1.xxxxx"
        let resourceId;
        if (workId.startsWith('work/')) {
            resourceId = `kca://${workId}`;
        } else if (workId.startsWith('kca://')) {
            resourceId = workId;
        } else {
            resourceId = `kca://work/${workId}`;
        }
        
        console.log(`[GraphQL] Converted workId to resourceId: ${resourceId}`);
        
        // Call our GraphQL implementation directly with the resource ID
        const options = {
            maxReviews: 30, // Match the limit from the working cURL
            nextPageToken: cursor,
            startPage: cursor ? 2 : 1, // If we have a cursor, we're on page 2+
            resourceId: resourceId // Pass the resource ID directly
        };
        
        const result = await scrapeReviewsWithResourceId(page, options);
        
        // Convert our result format to the expected format
        return {
            reviews: result.reviews || [],
            hasMore: result.hasMore || false,
            nextCursor: result.nextPageToken || null,
            metadata: {} // We don't extract metadata in the GraphQL function
        };
        
    } catch (error) {
        console.error('[GraphQL] makeGraphQLRequest error:', error);
        return {
            reviews: [],
            hasMore: false,
            nextCursor: null,
            metadata: {}
        };
    }
}

module.exports = { scrapeReviews, scrapeReviewsWithResourceId, makeGraphQLRequest };