# Stories from the Web - API Documentation

This document provides comprehensive documentation for the Stories from the Web API, including endpoints, request parameters, response formats, authentication, and error handling.

## Table of Contents

- [API Overview](#api-overview)
- [Base URL](#base-url)
- [Authentication](#authentication)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Content Endpoints](#content-endpoints)
  - [Stories](#stories)
  - [Authors](#authors)
  - [Tags](#tags)
  - [Games](#games)
  - [Directory Items](#directory-items)
  - [AI Tools](#ai-tools)
  - [Blog Posts](#blog-posts)
- [Authentication Endpoints](#authentication-endpoints)
- [Admin Endpoints](#admin-endpoints)
- [Pagination](#pagination)
- [Sorting](#sorting)
- [Filtering](#filtering)
- [Rate Limiting](#rate-limiting)
- [CORS](#cors)

## API Overview

The Stories from the Web API is a RESTful API that provides access to all content on the platform. It uses standard HTTP methods and returns JSON responses.

## Base URL

All API endpoints are relative to the base URL:

```
https://api.storiesfromtheweb.org/api/v1
```

## Authentication

Most read operations do not require authentication. Write operations and accessing protected resources require authentication using JSON Web Tokens (JWT).

To authenticate, include the JWT token in the Authorization header:

```
Authorization: Bearer <token>
```

Tokens can be obtained using the `/auth/login` endpoint and refreshed using the `/auth/refresh` endpoint.

## Response Format

All API endpoints return responses in a consistent flat array format:

```json
[
  {
    "id": 1,
    "title": "Example Item",
    "slug": "example-item",
    "other_fields": "values"
  },
  {
    "id": 2,
    "title": "Another Item",
    "slug": "another-item",
    "other_fields": "values"
  }
]
```

Single item responses return a single object:

```json
{
  "id": 1,
  "title": "Example Item",
  "slug": "example-item",
  "other_fields": "values"
}
```

## Error Handling

Errors are returned with appropriate HTTP status codes and a JSON response body:

```json
{
  "error": {
    "status": 404,
    "message": "Resource not found"
  }
}
```

Common error status codes:

- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or failed
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation error
- `500 Internal Server Error`: Server error

## Content Endpoints

### Stories

#### Get All Stories

```
GET /stories
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `title:asc`, `publishedAt:desc`)
- `featured`: Filter by featured status (boolean)
- `author`: Filter by author ID

Response:

```json
[
  {
    "id": 1,
    "title": "Example Story",
    "slug": "example-story",
    "excerpt": "This is an example story...",
    "content": "Full story content here...",
    "publishedAt": "2025-04-26T09:17:50+01:00"
  },
  {
    "id": 2,
    "title": "Another Story",
    "slug": "another-story",
    "excerpt": "Another great story...",
    "content": "More story content...",
    "publishedAt": "2025-04-26T09:17:50+01:00"
  }
]
```

#### Get Story by ID

```
GET /stories/{id}
```

Response:

```json
{
  "id": 1,
  "title": "Example Story",
  "slug": "example-story",
  "excerpt": "This is an example story...",
  "content": "Full story content here...",
  "publishedAt": "2025-04-26T09:17:50+01:00",
  "featured": true,
  "averageRating": 4.5,
  "reviewCount": 10,
  "estimatedReadingTime": "5 minutes",
  "isSponsored": false,
  "ageGroup": "12+",
  "needsModeration": false,
  "isSelfPublished": true,
  "isAiEnhanced": false,
  "coverUrl": "https://example.com/cover.jpg"
}
```

#### Get Story by Slug

```
GET /stories/slug/{slug}
```

Response: Same as Get Story by ID

### Authors

#### Get All Authors

```
GET /authors
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `name:asc`)

Response:

```json
[
  {
    "id": 1,
    "name": "John Doe",
    "slug": "john-doe",
    "bio": "A test author",
    "avatar_url": "https://example.com/avatar1.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  },
  {
    "id": 2,
    "name": "Jane Smith",
    "slug": "jane-smith",
    "bio": "Another test author",
    "avatar_url": "https://example.com/avatar2.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  }
]
```

#### Get Author by ID

```
GET /authors/{id}
```

Response:

```json
{
  "id": 1,
  "name": "John Doe",
  "slug": "john-doe",
  "bio": "A test author",
  "avatar_url": "https://example.com/avatar1.jpg",
  "is_published": 1,
  "created_at": "2025-04-26 09:17:50",
  "updated_at": "2025-04-26 09:17:50"
}
```

#### Get Author by Slug

```
GET /authors/slug/{slug}
```

Response: Same as Get Author by ID

### Tags

#### Get All Tags

```
GET /tags
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `name:asc`)

Response:

```json
[
  {
    "id": 1,
    "name": "Fiction",
    "slug": "fiction",
    "created_at": "2025-04-26 08:17:50",
    "updated_at": "2025-04-26 08:17:50"
  },
  {
    "id": 2,
    "name": "Fantasy",
    "slug": "fantasy",
    "created_at": "2025-04-26 08:17:50",
    "updated_at": "2025-04-26 08:17:50"
  }
]
```

#### Get Tag by ID

```
GET /tags/{id}
```

Response:

```json
{
  "id": 1,
  "name": "Fiction",
  "slug": "fiction",
  "created_at": "2025-04-26 08:17:50",
  "updated_at": "2025-04-26 08:17:50"
}
```

### Games

#### Get All Games

```
GET /games
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `title:asc`)
- `genre`: Filter by genre

Response:

```json
[
  {
    "id": 1,
    "title": "Test Game",
    "description": "Test game description",
    "slug": "test-game",
    "website_url": "http://example.com",
    "genre": "Action",
    "platform": "PC",
    "developer": "Test Dev",
    "publisher": "Test Pub",
    "release_date": null,
    "rating": "0.0",
    "price": "0.00",
    "cover_url": "https://example.com/game1.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  },
  {
    "id": 2,
    "title": "Another Game",
    "description": "More game content",
    "slug": "another-game",
    "website_url": "http://example.org",
    "genre": "RPG",
    "platform": "Console",
    "developer": "Dev2",
    "publisher": "Pub2",
    "release_date": null,
    "rating": "0.0",
    "price": "0.00",
    "cover_url": "https://example.com/game2.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  }
]
```

#### Get Game by ID

```
GET /games/{id}
```

Response: Single game object as shown above

### Directory Items

#### Get All Directory Items

```
GET /directory-items
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `title:asc`)
- `category`: Filter by category ID

Response:

```json
[
  {
    "id": 1,
    "title": "Test Directory",
    "description": "Test directory description",
    "category_id": null,
    "slug": "test-directory",
    "published_at": null,
    "website_url": "http://example.com",
    "contact_email": null,
    "contact_phone": null,
    "address": null,
    "featured": 0,
    "rating": "4.5",
    "price_range": "Free",
    "cover_url": "https://example.com/dir1.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  },
  {
    "id": 2,
    "title": "Another Directory",
    "description": "More directory content",
    "category_id": null,
    "slug": "another-directory",
    "published_at": null,
    "website_url": "http://example.org",
    "contact_email": null,
    "contact_phone": null,
    "address": null,
    "featured": 0,
    "rating": "4.0",
    "price_range": "Premium",
    "cover_url": "https://example.com/dir2.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  }
]
```

#### Get Directory Item by ID

```
GET /directory-items/{id}
```

Response: Single directory item object as shown above

### AI Tools

#### Get All AI Tools

```
GET /ai-tools
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `title:asc`)
- `category`: Filter by category ID
- `pricing_type`: Filter by pricing type

Response:

```json
[
  {
    "id": 1,
    "title": "Test AI Tool",
    "description": "Test tool description",
    "category_id": null,
    "slug": "test-ai-tool",
    "published_at": null,
    "tool_url": "http://example.com",
    "pricing_type": "free",
    "price_info": null,
    "features": null,
    "rating": "0.0",
    "featured": 1,
    "cover_url": "https://example.com/tool1.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  },
  {
    "id": 2,
    "title": "Another AI Tool",
    "description": "More tool content",
    "category_id": null,
    "slug": "another-ai-tool",
    "published_at": null,
    "tool_url": "http://example.org",
    "pricing_type": "paid",
    "price_info": null,
    "features": null,
    "rating": "0.0",
    "featured": 0,
    "cover_url": "https://example.com/tool2.jpg",
    "is_published": 1,
    "created_at": "2025-04-26 09:17:50",
    "updated_at": "2025-04-26 09:17:50"
  }
]
```

#### Get AI Tool by ID

```
GET /ai-tools/{id}
```

Response: Single AI tool object as shown above

### Blog Posts

#### Get All Blog Posts

```
GET /blog-posts
```

Query Parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25)
- `sort`: Sort field and direction (e.g., `title:asc`, `created_at:desc`)
- `author`: Filter by author ID

Response:

```json
[
  {
    "id": 1,
    "title": "Writing Tips for Children",
    "content": "Full blog post content...",
    "excerpt": "Learn how to write for children...",
    "slug": "writing-tips-for-children",
    "is_published": 1,
    "author_id": 1,
    "cover_url": "https://example.com/blog1.jpg",
    "created_at": "2025-04-26 08:17:50",
    "updated_at": "2025-04-26 08:17:50"
  },
  {
    "id": 2,
    "title": "The Importance of Reading",
    "content": "More blog content...",
    "excerpt": "Why reading matters...",
    "slug": "importance-of-reading",
    "is_published": 1,
    "author_id": 2,
    "cover_url": "https://example.com/blog2.jpg",
    "created_at": "2025-04-26 08:17:50",
    "updated_at": "2025-04-26 08:17:50"
  }
]
```

#### Get Blog Post by ID

```
GET /blog-posts/{id}
```

Response: Single blog post object as shown above

## Authentication Endpoints

### Login

```
POST /auth/login
```

Request Body:

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@storiesfromtheweb.org",
    "role": "admin"
  },
  "expires_in": 86400
}
```

### Logout

```
POST /auth/logout
```

Headers:

```
Authorization: Bearer <token>
```

Response:

```json
{
  "message": "Successfully logged out"
}
```

### Get Current User

```
GET /auth/me
```

Headers:

```
Authorization: Bearer <token>
```

Response:

```json
{
  "id": 1,
  "name": "Admin",
  "email": "admin@storiesfromtheweb.org",
  "role": "admin",
  "active": 1
}
```

### Refresh Token

```
POST /auth/refresh
```

Headers:

```
Authorization: Bearer <token>
```

Request Body:

```json
{
  "user_id": 1,
  "force": false,
  "threshold": 3600
}
```

Response:

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshed": true,
  "expires_in": 86400
}
```

## Admin Endpoints

These endpoints require authentication with admin privileges.

### Create Story

```
POST /admin/stories
```

Headers:

```
Authorization: Bearer <token>
Content-Type: application/json
```

Request Body:

```json
{
  "title": "New Story",
  "slug": "new-story",
  "content": "Story content...",
  "excerpt": "Story excerpt...",
  "featured": false,
  "is_published": true,
  "authors": [1, 2],
  "tags": [1, 3]
}
```

Response:

```json
{
  "id": 3,
  "title": "New Story",
  "slug": "new-story",
  "content": "Story content...",
  "excerpt": "Story excerpt...",
  "featured": 0,
  "is_published": 1,
  "created_at": "2025-04-26 13:45:22",
  "updated_at": "2025-04-26 13:45:22"
}
```

### Update Story

```
PUT /admin/stories/{id}
```

Headers:

```
Authorization: Bearer <token>
Content-Type: application/json
```

Request Body:

```json
{
  "title": "Updated Story",
  "content": "Updated content...",
  "excerpt": "Updated excerpt...",
  "featured": true
}
```

Response:

```json
{
  "id": 3,
  "title": "Updated Story",
  "slug": "new-story",
  "content": "Updated content...",
  "excerpt": "Updated excerpt...",
  "featured": 1,
  "is_published": 1,
  "created_at": "2025-04-26 13:45:22",
  "updated_at": "2025-04-26 13:50:15"
}
```

### Delete Story

```
DELETE /admin/stories/{id}
```

Headers:

```
Authorization: Bearer <token>
```

Response:

```json
{
  "message": "Story deleted successfully"
}
```

### Upload Media

```
POST /admin/media
```

Headers:

```
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

Request Body:

```
file: [binary file data]
alt_text: "Image description"
```

Response:

```json
{
  "id": 1,
  "filename": "image.jpg",
  "file_path": "/uploads/2025/04/image.jpg",
  "file_type": "image/jpeg",
  "file_size": 12345,
  "alt_text": "Image description",
  "url": "https://api.storiesfromtheweb.org/uploads/2025/04/image.jpg",
  "created_at": "2025-04-26 14:02:33",
  "updated_at": "2025-04-26 14:02:33"
}
```

## Pagination

Most list endpoints support pagination using the following query parameters:

- `page`: Page number (default: 1)
- `pageSize`: Items per page (default: 25, max: 100)

Example:

```
GET /stories?page=2&pageSize=10
```

## Sorting

Most list endpoints support sorting using the `sort` query parameter:

```
GET /stories?sort=title:asc
```

The format is `field:direction` where direction is either `asc` or `desc`.

Common sortable fields:

- `id`
- `title` or `name`
- `created_at`
- `updated_at`
- `publishedAt`

## Filtering

Endpoints support various filters specific to the content type. Common filters include:

- `featured`: Filter by featured status (boolean)
- `author` or `author_id`: Filter by author
- `category` or `category_id`: Filter by category
- `tag` or `tag_id`: Filter by tag

Example:

```
GET /stories?featured=true&author=1
```

## Rate Limiting

The API implements rate limiting to prevent abuse:

- 60 requests per minute for unauthenticated requests
- 120 requests per minute for authenticated requests

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1619442000
```

## CORS

The API supports Cross-Origin Resource Sharing (CORS) with the following headers:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

This allows the API to be accessed from any origin, including the frontend hosted on Netlify.