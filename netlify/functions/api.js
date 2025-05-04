// Mock API function for Netlify
exports.handler = async function(event, context) {
  try {
    // Parse the path from the event
    const path = event.path.replace('/.netlify/functions/api', '').replace('/api', '');

    // Log the request for debugging
    console.log('API Request:', {
      path,
      method: event.httpMethod,
      queryParams: event.queryStringParameters
    });

    // Set CORS headers
    const headers = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Headers': 'Content-Type',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE',
      'Content-Type': 'application/json'
    };

    // Handle OPTIONS request (CORS preflight)
    if (event.httpMethod === 'OPTIONS') {
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({ message: 'CORS preflight successful' })
      };
    }

    // Handle different API endpoints
    if (path === '/stories' || path === '/stories/') {
    // Return mock stories data
    return {
      statusCode: 200,
      headers,
      body: JSON.stringify([
        {
          id: 1,
          title: 'The Magical Forest',
          slug: 'the-magical-forest',
          excerpt: 'A young girl discovers a hidden forest with magical creatures.',
          content: 'Once upon a time, there was a young girl named Lily who loved to explore...',
          cover_url: '/images/stories/magical-forest.jpg',
          cover_urls: {
            thumbnail: '/images/stories/magical-forest-thumb.jpg',
            small: '/images/stories/magical-forest-small.jpg',
            medium: '/images/stories/magical-forest-medium.jpg',
            large: '/images/stories/magical-forest-large.jpg'
          },
          publishedAt: '2023-01-15T12:00:00Z',
          featured: true,
          is_sponsored: false,
          is_ai_enhanced: false,
          is_self_published: true,
          needs_moderation: false,
          is_published: true,
          average_rating: 4.8,
          review_count: 24,
          source_type: 'child',
          allow_reviews: true,
          estimated_reading_time: '5',
          age_group: '7-12',
          tags: ['adventure', 'fantasy', 'magic'],
          author: {
            name: 'Emma Johnson',
            bio: 'Emma is a 10-year-old who loves writing fantasy stories.',
            avatar_url: '/images/authors/emma.jpg',
            slug: 'emma-johnson',
            author_type: 'child',
            age: 10,
            location: 'London, UK'
          }
        },
        {
          id: 2,
          title: 'Space Explorers',
          slug: 'space-explorers',
          excerpt: 'Join Captain Max and his crew as they explore the galaxy.',
          content: 'Captain Max checked the ship's controls one last time before takeoff...',
          cover_url: '/images/stories/space-explorers.jpg',
          cover_urls: {
            thumbnail: '/images/stories/space-explorers-thumb.jpg',
            small: '/images/stories/space-explorers-small.jpg',
            medium: '/images/stories/space-explorers-medium.jpg',
            large: '/images/stories/space-explorers-large.jpg'
          },
          publishedAt: '2023-02-20T14:30:00Z',
          featured: false,
          is_sponsored: true,
          is_ai_enhanced: true,
          is_self_published: false,
          needs_moderation: false,
          is_published: true,
          average_rating: 4.5,
          review_count: 18,
          source_type: 'parent',
          allow_reviews: true,
          estimated_reading_time: '8',
          age_group: '9-14',
          tags: ['science fiction', 'space', 'adventure'],
          author: {
            name: 'David Smith',
            bio: 'David is a science teacher who writes stories to inspire young minds.',
            avatar_url: '/images/authors/david.jpg',
            slug: 'david-smith',
            author_type: 'parent',
            age: 35,
            location: 'Chicago, USA'
          }
        },
        {
          id: 3,
          title: 'The Brave Little Robot',
          slug: 'brave-little-robot',
          excerpt: 'A story about a small robot with a big heart.',
          content: 'In a world where robots did all the work, there was one little robot who wanted more...',
          cover_url: '/images/stories/brave-robot.jpg',
          cover_urls: {
            thumbnail: '/images/stories/brave-robot-thumb.jpg',
            small: '/images/stories/brave-robot-small.jpg',
            medium: '/images/stories/brave-robot-medium.jpg',
            large: '/images/stories/brave-robot-large.jpg'
          },
          publishedAt: '2023-03-10T09:15:00Z',
          featured: true,
          is_sponsored: false,
          is_ai_enhanced: true,
          is_self_published: true,
          needs_moderation: false,
          is_published: true,
          average_rating: 4.9,
          review_count: 32,
          source_type: 'child',
          allow_reviews: true,
          estimated_reading_time: '6',
          age_group: '5-8',
          tags: ['robots', 'friendship', 'courage'],
          author: {
            name: 'Sophie Williams',
            bio: 'Sophie loves robots and technology. She writes stories to inspire other kids.',
            avatar_url: '/images/authors/sophie.jpg',
            slug: 'sophie-williams',
            author_type: 'child',
            age: 12,
            location: 'Sydney, Australia'
          }
        }
      ])
    };
  } else if (path === '/authors' || path === '/authors/') {
    // Return mock authors data
    return {
      statusCode: 200,
      headers,
      body: JSON.stringify([
        {
          id: 1,
          name: 'Emma Johnson',
          bio: 'Emma is a 10-year-old who loves writing fantasy stories.',
          avatar_url: '/images/authors/emma.jpg',
          slug: 'emma-johnson',
          author_type: 'child',
          featured: true,
          age: 10,
          location: 'London, UK'
        },
        {
          id: 2,
          name: 'David Smith',
          bio: 'David is a science teacher who writes stories to inspire young minds.',
          avatar_url: '/images/authors/david.jpg',
          slug: 'david-smith',
          author_type: 'parent',
          featured: false,
          age: 35,
          location: 'Chicago, USA'
        },
        {
          id: 3,
          name: 'Sophie Williams',
          bio: 'Sophie loves robots and technology. She writes stories to inspire other kids.',
          avatar_url: '/images/authors/sophie.jpg',
          slug: 'sophie-williams',
          author_type: 'child',
          featured: true,
          age: 12,
          location: 'Sydney, Australia'
        },
        {
          id: 4,
          name: 'James Peterson',
          bio: 'Award-winning children\'s book author with over 20 published books.',
          avatar_url: '/images/authors/james.jpg',
          slug: 'james-peterson',
          author_type: 'retail',
          featured: true,
          age: 45,
          location: 'New York, USA'
        }
      ])
    };
  } else if (path === '/tags' || path === '/tags/') {
    // Return mock tags data
    return {
      statusCode: 200,
      headers,
      body: JSON.stringify([
        { id: 1, name: 'Adventure', slug: 'adventure' },
        { id: 2, name: 'Fantasy', slug: 'fantasy' },
        { id: 3, name: 'Educational', slug: 'educational' },
        { id: 4, name: 'Animals', slug: 'animals' },
        { id: 5, name: 'Science', slug: 'science' },
        { id: 6, name: 'Friendship', slug: 'friendship' },
        { id: 7, name: 'Robots', slug: 'robots' },
        { id: 8, name: 'Space', slug: 'space' },
        { id: 9, name: 'Courage', slug: 'courage' },
        { id: 10, name: 'Magic', slug: 'magic' }
      ])
    };
  }

    // Default response for unhandled endpoints
    return {
      statusCode: 404,
      headers,
      body: JSON.stringify({ error: 'Not Found' })
    };
  } catch (error) {
    console.error('API Error:', error);

    // Return error response
    return {
      statusCode: 500,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        error: 'Internal Server Error',
        message: error.message
      })
    };
  }
};
