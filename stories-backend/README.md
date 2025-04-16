# Stories API

A secure PHP API layer for connecting an Astro site to a MySQL/MariaDB database. This API provides endpoints for all content types (stories, authors, blog posts, directory items, games, AI tools, tags) with proper security measures, CORS support, and pagination.

## Features

- **Secure Authentication**: JWT-based authentication with role-based access control
- **Content Endpoints**: Full CRUD operations for all content types
- **Security Measures**: Input validation, prepared statements, and XSS protection
- **CORS Support**: Configured for Netlify-hosted Astro frontend
- **Pagination**: Support for paginated responses with metadata
- **Filtering**: Filter content by various attributes
- **Sorting**: Sort content by supported fields
- **Response Format**: Matches the expected format of the Astro frontend

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3 or higher
- Apache with mod_rewrite enabled
- PDO PHP extension
- JSON PHP extension

## Installation

1. **Clone the repository**

```bash
git clone https://github.com/yourusername/stories-api.git
cd stories-api
```

2. **Set up the database**

Create a new MySQL/MariaDB database and import the schema:

```bash
mysql -u username -p database_name < database.sql
```

3. **Configure the API**

Edit the configuration file at `api/v1/config/config.php`:

```php
// Database configuration
$config['db'] = [
    'host'     => 'localhost',      // Your database host
    'name'     => 'stories_db',     // Your database name
    'user'     => 'stories_user',   // Your database username
    'password' => 'your_secure_password', // Your database password
    'charset'  => 'utf8mb4',
    'port'     => 3306
];

// Security configuration
$config['security'] = [
    'jwt_secret'   => 'your_jwt_secret_key', // Change this to a secure random string
    'token_expiry' => 86400,                 // Token expiry time in seconds (24 hours)
    'cors' => [
        'allowed_origins' => [
            'https://storiesfromtheweb.netlify.app', // Production Netlify site
            'http://localhost:3000',                 // Local development
            'http://localhost:4321'                  // Astro dev server
        ],
        // ... other CORS settings
    ]
];
```

4. **Upload to your server**

Upload the files to your cPanel/PHP hosting environment.

5. **Set up the .htaccess file**

Make sure the `.htaccess` file is properly configured for your server.

## API Endpoints

### Authentication

- `POST /api/v1/auth/login` - Login with email and password
- `POST /api/v1/auth/register` - Register a new user
- `GET /api/v1/auth/me` - Get the current authenticated user

### Stories

- `GET /api/v1/stories` - Get all stories (paginated)
- `GET /api/v1/stories/{slug}` - Get a single story by slug
- `POST /api/v1/stories` - Create a new story (requires authentication)
- `PUT /api/v1/stories/{id}` - Update a story (requires authentication)
- `DELETE /api/v1/stories/{id}` - Delete a story (requires authentication)

### Authors

- `GET /api/v1/authors` - Get all authors (paginated)
- `GET /api/v1/authors/{slug}` - Get a single author by slug
- `PUT /api/v1/authors/{id}` - Update an author (requires authentication)

### Blog Posts

- `GET /api/v1/blog-posts` - Get all blog posts (paginated)
- `GET /api/v1/blog-posts/{slug}` - Get a single blog post by slug
- `POST /api/v1/blog-posts` - Create a new blog post (requires admin/editor role)
- `PUT /api/v1/blog-posts/{id}` - Update a blog post (requires admin/editor role)
- `DELETE /api/v1/blog-posts/{id}` - Delete a blog post (requires admin/editor role)

### Directory Items

- `GET /api/v1/directory-items` - Get all directory items (paginated)
- `GET /api/v1/directory-items/{id}` - Get a single directory item by ID
- `POST /api/v1/directory-items` - Create a new directory item (requires admin/editor role)
- `PUT /api/v1/directory-items/{id}` - Update a directory item (requires admin/editor role)
- `DELETE /api/v1/directory-items/{id}` - Delete a directory item (requires admin/editor role)

### Games

- `GET /api/v1/games` - Get all games (paginated)
- `GET /api/v1/games/{id}` - Get a single game by ID
- `POST /api/v1/games` - Create a new game (requires admin/editor role)
- `PUT /api/v1/games/{id}` - Update a game (requires admin/editor role)
- `DELETE /api/v1/games/{id}` - Delete a game (requires admin/editor role)

### AI Tools

- `GET /api/v1/ai-tools` - Get all AI tools (paginated)
- `GET /api/v1/ai-tools/{id}` - Get a single AI tool by ID
- `POST /api/v1/ai-tools` - Create a new AI tool (requires admin/editor role)
- `PUT /api/v1/ai-tools/{id}` - Update an AI tool (requires admin/editor role)
- `DELETE /api/v1/ai-tools/{id}` - Delete an AI tool (requires admin/editor role)

### Tags

- `GET /api/v1/tags` - Get all tags (paginated)
- `GET /api/v1/tags/{slug}` - Get a single tag by slug
- `POST /api/v1/tags` - Create a new tag (requires admin/editor role)
- `PUT /api/v1/tags/{id}` - Update a tag (requires admin/editor role)
- `DELETE /api/v1/tags/{id}` - Delete a tag (requires admin/editor role)

## Request Parameters

### Pagination

- `page` - Page number (default: 1)
- `pageSize` - Number of items per page (default: 25, max: 100)

Example: `GET /api/v1/stories?page=2&pageSize=10`

### Sorting

- `sort` - Field to sort by, with optional direction (asc or desc)

Example: `GET /api/v1/stories?sort=publishedAt:desc`

### Filtering

Filter by field values:

Example: `GET /api/v1/stories?featured=1&author_id=5`

## Response Format

All responses follow the same format to match the expected format of the Astro frontend:

```json
{
  "data": [
    {
      "id": 1,
      "attributes": {
        "title": "The Magic Forest",
        "slug": "the-magic-forest",
        "excerpt": "A story about a magical forest...",
        "publishedAt": "2025-04-15T12:00:00.000Z",
        "featured": true,
        "averageRating": 4.5,
        "cover": {
          "data": {
            "id": 1,
            "attributes": {
              "url": "/uploads/magic-forest-cover.jpg",
              "width": 1200,
              "height": 800,
              "alternativeText": "The Magic Forest Cover"
            }
          }
        },
        "author": {
          "data": [
            {
              "id": 1,
              "attributes": {
                "name": "John Doe",
                "slug": "john-doe",
                "bio": "Children's book author and storyteller"
              }
            }
          ],
          "meta": {
            "pagination": {
              "page": 1,
              "pageSize": 1,
              "pageCount": 1,
              "total": 1
            }
          }
        }
      }
    }
  ],
  "meta": {
    "pagination": {
      "page": 1,
      "pageSize": 25,
      "pageCount": 1,
      "total": 1
    }
  }
}
```

## Authentication

To access protected endpoints, you need to include an `Authorization` header with a JWT token:

```
Authorization: Bearer your_jwt_token
```

You can obtain a JWT token by logging in with the `/api/v1/auth/login` endpoint.

## Security Measures

This API implements several security measures:

1. **Authentication**: JWT-based authentication with role-based access control
2. **Input Validation**: All input is validated and sanitized
3. **Prepared Statements**: All database queries use prepared statements to prevent SQL injection
4. **XSS Protection**: Output is sanitized to prevent cross-site scripting
5. **CORS**: Properly configured CORS headers to prevent unauthorized access
6. **Password Hashing**: Passwords are hashed using bcrypt
7. **Rate Limiting**: Basic rate limiting to prevent abuse

## Error Handling

All errors follow a consistent format:

```json
{
  "error": true,
  "message": "Error message",
  "statusCode": 400,
  "errors": {
    "field": "Field-specific error message"
  }
}
```

## Connecting to the Astro Frontend

Update your Astro frontend's API configuration to point to this API:

```typescript
// src/lib/api.ts
const API_URL = 'https://your-api-domain.com/api/v1';
const API_TOKEN = 'your_jwt_token';

export const fetchFromAPI = async (endpoint, params = {}) => {
  try {
    const queryString = new URLSearchParams(params).toString();
    const url = `${API_URL}/${endpoint}${queryString ? `?${queryString}` : ''}`;
    
    const headers = {
      'Content-Type': 'application/json'
    };
    
    if (API_TOKEN) {
      headers['Authorization'] = `Bearer ${API_TOKEN}`;
    }
    
    const res = await fetch(url, { headers });
    
    if (!res.ok) {
      throw new Error(`Failed to fetch from API: ${res.status} ${res.statusText}`);
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from API:', error);
    return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
  }
};
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.# Test change
