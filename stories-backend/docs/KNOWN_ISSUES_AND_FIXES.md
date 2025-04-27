# Known Issues and Fixes

This document outlines known issues in the Stories from the Web platform and their solutions. It serves as a reference for developers and AI assistants working on the project.

## Table of Contents

- [Admin Interface Issues](#admin-interface-issues)
- [Frontend Display Issues](#frontend-display-issues)
- [API Issues](#api-issues)
- [Database Issues](#database-issues)
- [Deployment Issues](#deployment-issues)

## Admin Interface Issues

### Duplicate Stories in Admin List

**Issue**: The admin interface sometimes shows duplicate entries for the same story.

**Cause**: This was caused by a reference issue in the PHP foreach loop. When using `foreach ($stories as &$story)`, the reference to `$story` persists after the loop, causing issues in subsequent loops.

**Fix**: 
1. Changed the loop to use array indices instead of references:
```php
foreach ($stories as $index => $storyItem) {
    // Use $stories[$index] instead of directly modifying $storyItem
}
```
2. Alternatively, unset the reference after the loop:
```php
foreach ($stories as &$story) {
    // Loop code
}
unset($story); // Break the reference
```

### Author Avatar URL Not Saving

**Issue**: The avatar_url field for authors was not being saved properly.

**Cause**: The avatar_url column was missing from the database or not included in the save query.

**Fix**:
1. Added the avatar_url column to the authors table if it didn't exist:
```php
$db->exec("ALTER TABLE authors ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
```
2. Updated the save-author.php file to include the avatar_url field in both the update and insert queries.

## Frontend Display Issues

### [object Object] Displayed in Tags

**Issue**: Tags were sometimes displayed as "[object Object]" instead of the tag name.

**Cause**: The TagBadge component was not properly handling tag objects.

**Fix**: Enhanced the TagBadge component to properly handle different tag formats:
```javascript
// Format tag for URL (lowercase, replace spaces with hyphens)
// Ensure tag is a string and handle objects properly
let tagText = '';
let tagSlug = '';

if (typeof tag === 'string') {
  tagText = tag;
  tagSlug = tag.toLowerCase().replace(/\s+/g, '-');
} else if (tag && typeof tag === 'object') {
  // If tag is an object, try to get the name property
  tagText = tag.name || tag.title || JSON.stringify(tag);
  tagSlug = (tag.slug || tagText).toLowerCase().replace(/\s+/g, '-');
} else {
  // Fallback for any other type
  tagText = String(tag || 'Tag');
  tagSlug = tagText.toLowerCase().replace(/\s+/g, '-');
}
```

### "0" Displayed Next to Story Titles

**Issue**: A "0" was sometimes displayed next to story titles in the CardStory component.

**Cause**: This was caused by the rating being displayed even when it was 0.

**Fix**: Updated the CardStory component to only show ratings greater than 0:
```javascript
{story.rating && typeof story.rating === 'number' && story.rating > 0 && (
  <div class="ml-2 flex-shrink-0">
    <RatingStars rating={story.rating} size="sm" />
  </div>
)}
```

### Blog Page 500 Error

**Issue**: The blog page was returning a 500 error.

**Cause**: The blog page was trying to filter stories by a non-existent `type` field and had issues handling tags.

**Fix**:
1. Updated the blog page to fetch from the correct API endpoint:
```javascript
import { fetchBlogPosts } from '../../lib/api';
// ...
blogPosts = await fetchBlogPosts();
```
2. Added proper error handling and type checking for tags:
```javascript
if (post.tags && Array.isArray(post.tags) && post.tags.length > 0) {
  // Handle tags
}
```

## API Issues

### Missing Blog Posts API Function

**Issue**: The API client was missing a function to fetch blog posts.

**Cause**: The function was not implemented in the API client.

**Fix**: Added a fetchBlogPosts function to the API client:
```typescript
export async function fetchBlogPosts(page = 1, limit = 10, filters: Record<string, any> = {}): Promise<any[]> {
  // Default parameters
  const params: Record<string, string | number | boolean> = {
    'pagination[limit]': limit,
    'pagination[page]': page
  };
  
  // Set default sort if not specified in filters
  if (!filters.sort) {
    params['sort'] = 'created_at:desc';
  } else {
    params['sort'] = filters.sort;
  }
  
  // Add any additional filters
  Object.entries(filters).forEach(([key, value]) => {
    if (key !== 'sort') {
      params[key] = value;
    }
  });
  
  try {
    const raw = await fetchApi<any[]>('/blog-posts', params);
    return raw.map(item => ({
      id: item.id,
      title: item.title,
      excerpt: item.excerpt || '',
      content: item.content || '',
      coverImage: item.cover_url || '',
      slug: item.slug,
      publishDate: item.created_at || '',
      author: item.author_id ? {
        id: item.author_id,
        name: 'Author ' + item.author_id,
        slug: 'author-' + item.author_id,
        avatar: '/images/default-avatar.svg'
      } : undefined
    }));
  } catch (error) {
    console.error("Error fetching blog posts:", error);
    return [];
  }
}
```

### Incorrect Filter Parameter Names

**Issue**: The frontend was using different parameter names than the API expected.

**Cause**: Inconsistency between frontend and backend naming conventions.

**Fix**: Updated the API client to map frontend parameter names to backend parameter names:
```typescript
if (filters.isAiEnhanced === true) {
  params['is_ai_enhanced'] = 1;
  console.log("Setting is_ai_enhanced=1 filter");
}

if (filters.isSelfPublished === true) {
  params['is_self_published'] = 1;
  console.log("Setting is_self_published=1 filter");
}
```

## Database Issues

### Missing story_authors Table

**Issue**: The story_authors table was missing or not properly set up.

**Cause**: The table was not created during initial database setup.

**Fix**: Created a script to add the story_authors table if it didn't exist:
```php
$db->exec("CREATE TABLE IF NOT EXISTS story_authors (
    story_id INT NOT NULL,
    author_id INT NOT NULL,
    PRIMARY KEY (story_id, author_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
```

## Deployment Issues

### Static Generation vs. Server-Side Rendering

**Issue**: Changes made in the admin interface were not immediately reflected on the frontend.

**Cause**: The frontend was using static generation (`export const prerender = true;`) instead of server-side rendering.

**Fix**: Removed the prerender directive from index.astro to enable server-side rendering:
```javascript
// Remove prerender directive to enable server-side rendering
// export const prerender = true;
```

This allows the frontend to fetch the latest data from the API on each request, ensuring that changes made in the admin interface are immediately reflected on the frontend.