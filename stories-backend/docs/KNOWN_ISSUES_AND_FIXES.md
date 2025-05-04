# Known Issues and Fixes

This document outlines known issues in the Stories from the Web platform and their solutions. It serves as a reference for developers and AI assistants working on the project.

## Table of Contents

- [Admin Interface Issues](#admin-interface-issues)
- [Frontend Display Issues](#frontend-display-issues)
- [API Issues](#api-issues)
- [Database Issues](#database-issues)
- [Deployment Issues](#deployment-issues)
- [Media Issues](#media-issues)

## Admin Interface Issues

### Missing Favicon in Admin Dashboard

**Issue**: The favicon was not displaying in the admin dashboard pages.

**Cause**: The favicon path was incorrect and not using an absolute URL.

**Fix**:
1. Updated the favicon path in the header.php file to use an absolute URL:
```php
// Use absolute path for favicon to ensure it works in all environments
$faviconPath = 'https://api.storiesfromtheweb.org/public/favicon.png';
?>
<link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">
<link rel="shortcut icon" type="image/png" href="<?php echo $faviconPath; ?>">
```

### Duplicate Headings on Contact Page

**Issue**: The contacts page was showing duplicate headings.

**Cause**: The page header was included both in the header.php file and again in the contacts.php file.

**Fix**:
1. Removed the duplicate page header in contacts.php:
```php
<!-- Page header is already included in header.php, so we don't need to repeat it here -->
```

### Bulk Actions Showing Blank Screen

**Issue**: Bulk actions on the contacts page were showing a blank "Admin" screen.

**Cause**: The bulk-contacts.php file wasn't properly including the database connection and authentication files.

**Fix**:
1. Updated the bulk-contacts.php file to include the auth-check.php file:
```php
// Include auth check
include_once '../includes/auth-check.php';
```
2. Updated the response handling to use session-based messages like other admin pages:
```php
$_SESSION['success'] = "$count contact(s) deleted successfully";
```

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

## Security Issues

### Bot Submissions to Contact and Subscriber Forms

**Issue**: The contact and subscriber forms were receiving spam submissions from bots.

**Cause**: The forms lacked proper bot protection mechanisms.

**Fix**:
1. Created a comprehensive anti-bot.php library with multiple protection methods:
```php
// includes/anti-bot.php
function isLikelyBot($data = []) {
    // Check for common bot signatures
    // 1. Check user agent
    // 2. Check if request has no user agent or referer
    // 3. Check for abnormally fast form submission
    // 4. Check for hidden honeypot field
    // 5. Check for missing or invalid token
    // 6. Check for too many submissions from the same IP
    // ...
}
```
2. Added honeypot fields to forms (hidden fields that only bots would fill out):
```html
<!-- Honeypot field to catch bots -->
<input type="text" name="website" style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1;" tabindex="-1" autocomplete="off">
```
3. Added token-based protection to prevent automated submissions:
```php
// Generate a form token
function generateToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['form_token'] = $token;
    $_SESSION['form_start_time'] = time();
    return $token;
}
```
4. Updated the form processing scripts to check for bot submissions:
```php
// Check for bot submissions
if (isLikelyBot($data)) {
    // Pretend success but don't actually save the data
    error_log("Bot submission detected and blocked");
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We\'ll get back to you as soon as possible.'
    ]);
    exit;
}
```

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
1. Updated the blog page to use the correct fields and handle tags properly.
2. Added proper error handling for missing data.

### Tag Pages Returning 404

**Issue**: Clicking on story tags was leading to 404 errors.

**Cause**: Missing tag results page and improper tag URL handling.

**Fix**:
1. Added a new `[tag].astro` page to handle tag results
2. Enhanced tag URL handling with proper encoding/decoding:
```typescript
// Format tag for URL
const decodedTag = decodeURIComponent(tag.replace(/-/g, ' '));

// Filter stories by tag
const taggedStories = stories.filter(story =>
  story.tags?.some(t => t.toLowerCase() === decodedTag.toLowerCase())
);
```

### Review Section Showing for Child Authors

**Issue**: Review sections were being displayed for stories by child authors.

**Cause**: Missing author type check in review visibility logic.

**Fix**:
1. Added author type check in story detail page:
```typescript
{story.is_published && (!story.author?.author_type || story.author?.author_type !== 'child') && (
  <ReviewSection
    itemType="story"
    itemId={slug}
    itemName={story.title}
    rating={story.rating || 0}
    reviewCount={story.reviewCount || 0}
  />
)}
```

### Moderation Box Always Visible

**Issue**: Moderation CTA was visible to all users regardless of admin status.

**Cause**: Missing admin and story status checks.

**Fix**:
1. Added proper visibility conditions:
```typescript
{(Astro.locals?.user?.isAdmin && (!story.publishedAt || story.needs_moderation)) && (
  <ModerationCTA
    contentType="story"
    contentId={slug}
    reason={story.needs_moderation ? "Story requires moderation" : "Story needs to be published"}
  />
)}
```

### Age Group and Reading Time Not Set

**Issue**: Stories were missing age group and reading time information.

**Cause**: Missing fields in story form and database.

**Fix**:
1. Added fields to story form:
```php
<div class="form-group">
  <label class="form-label" for="age_group">Age Group</label>
  <select id="age_group" name="age_group" class="form-input" required>
    <option value="0-3">0-3 years</option>
    <option value="4-6">4-6 years</option>
    <option value="7-12" selected>7-12 years</option>
    <option value="13+">13+ years</option>
  </select>
</div>
```
2. Added automatic age group setting based on child author age:
```javascript
if (authorType === 'child' && data.age) {
  const age = parseInt(data.age);
  let ageGroup = '7-12'; // Default
  if (age <= 3) ageGroup = '0-3';
  else if (age <= 6) ageGroup = '4-6';
  else if (age <= 12) ageGroup = '7-12';
  else ageGroup = '13+';
  ageGroupSelect.value = ageGroup;
}
```
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

### Subscribers Functionality 500 Error

**Issue**: The subscribers admin page and API endpoint were returning 500 errors.

**Cause**: The subscribers table was missing from the database, and there were issues with the database connection in the subscribers.php files.

**Fix**:
1. Created a browser-accessible fix script that:
   - Creates the subscribers table if it doesn't exist
   - Updates the admin subscribers page with proper error handling
   - Updates the API subscribers endpoint with proper error handling
   - Fixes the premium page width issue

2. Added the subscribers table to the database:
```php
$db->exec("CREATE TABLE subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    feature VARCHAR(100) NOT NULL,
    message TEXT,
    is_contacted TINYINT(1) DEFAULT 0,
    admin_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
```

3. Updated the admin subscribers page to handle database connection issues and properly display subscribers.

4. Updated the API subscribers endpoint to handle both POST requests (for new subscribers) and GET requests (for admin listing).

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

## Media Issues

### Slow-Loading Images in Admin Interface

**Issue**: Images in the admin interface were loading very slowly, particularly in the media library. The fix_media_sizes.php script was not optimizing images as expected.

**Cause**: The server lacks the required PHP image processing extensions (ImageMagick and GD). When the optimization script runs, it reports "No image libraries available, copied without optimization" and simply copies the original large files (3MB+) without reducing their size.

**Fix**:

1. Created a new script (fix_media_direct.php) that doesn't rely on image processing libraries:
```php
// Function to find the best sized version of an image
function findBestSizedVersion($originalFilename, $maxWidth = 640) {
    $baseName = getBaseName($originalFilename);

    // Define size preferences in order (smaller to larger)
    $preferredSizes = [
        '50x50', '110x110', '150x150', '180x77', '240x240', '300x300',
        '440x330', '640x640'
    ];

    // Define directories to search
    $searchDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2023/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/2024/'
    ];

    // Search for existing sized versions
    foreach ($preferredSizes as $size) {
        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;

            // Look for files with this size in the name
            $files = glob($dir . '*' . $baseName . '*' . $size . '*');

            if (!empty($files)) {
                // Use this sized version
                $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $files[0]);
                return 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
            }
        }
    }

    return null;
}
```

2. The script searches for existing smaller versions of each image in the uploads directories (which already contain multiple sized versions of each image).

3. It updates the database to use these smaller versions instead of the original large files:
```php
// Update database record
$updateStmt = $db->prepare("UPDATE media SET file_path = ?, file_size = ? WHERE id = ?");
$updateStmt->execute([$result['path'], $result['size'], $item['id']]);
```

4. For a permanent solution, install the required PHP extensions on the server:
```bash
# For ImageMagick (preferred for better quality)
sudo apt-get install php-imagick

# For GD (alternative)
sudo apt-get install php-gd

# Then restart PHP
sudo systemctl restart php-fpm  # or apache2, depending on your setup
```