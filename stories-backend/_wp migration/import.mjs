// import.mjs – run with: node import.mjs
import fs from 'fs/promises';
import path from 'path';
import matter from 'gray-matter';
import { marked } from 'marked';
import fetch from 'node-fetch';
import dotenv from 'dotenv';
dotenv.config();

// Polyfill Array.prototype.at for Node versions <16
if (!Array.prototype.at) {
  Array.prototype.at = function(n) {
    return n >= 0 ? this[n] : this[this.length + n];
  };
}

// API configuration
const API = 'https://api.storiesfromtheweb.org/api/v1';

// Authentication token
// To get this token:
// 1. Log in to https://api.storiesfromtheweb.org/admin/
// 2. Open browser developer tools (F12)
// 3. Go to Application tab > Local Storage
// 4. Find the 'token' key and copy its value
// 5. Replace the placeholder below with that value
const TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwiaWF0IjoxNjgyNDM2NzgwLCJleHAiOjE2ODI1MjMxODB9.YOUR_ACTUAL_TOKEN_HERE';

// Track created media to avoid duplicates
const mediaCache = new Map();

async function main() {
  console.log("WORDPRESS MIGRATION - CHILDREN'S STORIES ONLY");
  console.log("==============================================");
  
  // First pass: Create child authors
  console.log("\nPASS 1: Creating child authors...");
  const childrenStoriesDir = path.join('./wp-md', 'custom', 'childrens-story');
  
  try {
    const entries = await fs.readdir(childrenStoriesDir, { withFileTypes: true });
    console.log(`Found ${entries.filter(e => e.isDirectory()).length} children's stories to process`);
    
    for (const entry of entries) {
      if (entry.isDirectory()) {
        const mdFile = path.join(childrenStoriesDir, entry.name, 'index.md');
        try {
          await fs.access(mdFile);
          await createAuthorFromStory(mdFile);
        } catch (err) {
          console.error(`Error accessing ${mdFile}: ${err.message}`);
        }
      }
    }
  } catch (err) {
    console.error(`Error reading children's stories directory: ${err.message}`);
    process.exit(1);
  }
  
  // Second pass: Import children's stories
  console.log("\nPASS 2: Importing children's stories...");
  try {
    const entries = await fs.readdir(childrenStoriesDir, { withFileTypes: true });
    
    for (const entry of entries) {
      if (entry.isDirectory()) {
        const mdFile = path.join(childrenStoriesDir, entry.name, 'index.md');
        try {
          await fs.access(mdFile);
          await importStory(mdFile);
        } catch (err) {
          console.error(`Error accessing ${mdFile}: ${err.message}`);
        }
      }
    }
  } catch (err) {
    console.error(`Error reading children's stories directory: ${err.message}`);
    process.exit(1);
  }
  
  console.log("\nMigration complete!");
}

async function uploadMedia(filePath) {
  // Check if we've already uploaded this file
  if (mediaCache.has(filePath)) {
    console.log(`  - Using cached media for ${path.basename(filePath)}`);
    return mediaCache.get(filePath);
  }
  
  try {
    // First, create a media record in the database
    const fileName = path.basename(filePath);
    const fileBuffer = await fs.readFile(filePath);
    const fileSize = fileBuffer.length;
    const fileType = getFileType(fileName);
    
    console.log(`  - Uploading media: ${fileName} (${fileSize} bytes)`);
    
    // For local development, we'll use a simpler approach
    // Just upload the file directly as binary data
    const res = await fetch(`${API}/media`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${TOKEN}`,
        'Content-Type': 'application/octet-stream',
        'X-Filename': fileName,
        'X-Filetype': fileType
      },
      body: fileBuffer
    });
    
    // Log the full response for debugging
    const responseText = await res.text();
    console.log(`  - Media upload response: ${res.status} ${res.statusText}`);
    console.log(`  - Response body: ${responseText.substring(0, 200)}...`);
    
    try {
      const json = JSON.parse(responseText);
      if (json.url) {
        // Cache the result
        mediaCache.set(filePath, json.url);
        return json.url;
      } else {
        console.error(`  - Media upload response did not include URL: ${JSON.stringify(json)}`);
      }
    } catch (parseErr) {
      console.error(`  - Failed to parse JSON response: ${parseErr.message}`);
    }
    
    // If we get here, something went wrong
    return '/images/default-cover.svg';
  } catch (err) {
    console.error(`  - Error uploading media ${filePath}: ${err.message}`);
    // Return a default image URL if upload fails
    return '/images/default-cover.svg';
  }
}

function getFileType(fileName) {
  const ext = path.extname(fileName).toLowerCase();
  switch (ext) {
    case '.jpg':
    case '.jpeg':
      return 'image/jpeg';
    case '.png':
      return 'image/png';
    case '.gif':
      return 'image/gif';
    case '.svg':
      return 'image/svg+xml';
    case '.webp':
      return 'image/webp';
    default:
      return 'application/octet-stream';
  }
}

async function ensureTag(name) {
  if (!name) return null;
  
  try {
    const r = await fetch(`${API}/tags?name=${encodeURIComponent(name)}`, {
      headers: { 'Authorization': `Bearer ${TOKEN}` }
    });
    
    if (!r.ok) {
      throw new Error(`Failed to fetch tag: ${r.status} ${r.statusText}`);
    }
    
    const tags = await r.json();
    const found = tags[0];
    
    if (found) {
      return found.id;
    }
    
    // Create the tag if it doesn't exist
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    const c = await fetch(`${API}/tags`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${TOKEN}`
      },
      body: JSON.stringify({ name, slug })
    });
    
    if (!c.ok) {
      throw new Error(`Failed to create tag: ${c.status} ${c.statusText}`);
    }
    
    const newTag = await c.json();
    console.log(`  - Created tag: ${name}`);
    return newTag.id;
  } catch (err) {
    console.error(`  - Error ensuring tag ${name}: ${err.message}`);
    return null;
  }
}

async function createAuthorFromStory(file) {
  try {
    const raw = await fs.readFile(file, 'utf8');
    const { data } = matter(raw);
    const title = data.title || '';
    
    // Extract author info from title using regex
    // Pattern: "Story Title by Author Name aged X from Location"
    // Also handle variations like "by Author Name, aged X, from Location"
    const authorMatch = title.match(/by\s+([^,]+?)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,]+))?/i);
    
    if (!authorMatch) {
      console.log(`  - No author info found in title: ${title}`);
      console.log(`  - Full title: "${title}"`);
      return null;
    }
    
    const authorName = authorMatch[1]?.trim();
    const authorAge = authorMatch[2] || '';
    const authorLocation = authorMatch[3] || '';
    
    console.log(`  - Extracted author info: Name="${authorName}", Age=${authorAge || 'unknown'}, Location="${authorLocation || 'unknown'}"`);
    
    if (!authorName) {
      console.log(`  - Could not extract author name from title: ${title}`);
      return null;
    }
    
    const authorSlug = authorName.toLowerCase().replace(/\s+/g, '-');
    
    // Check if author already exists
    const checkRes = await fetch(`${API}/authors?slug=${authorSlug}`, {
      headers: { 'Authorization': `Bearer ${TOKEN}` }
    });
    
    if (!checkRes.ok) {
      throw new Error(`Failed to check for existing author: ${checkRes.status} ${checkRes.statusText}`);
    }
    
    const existingAuthors = await checkRes.json();
    
    if (existingAuthors && existingAuthors.length > 0) {
      console.log(`  - Author already exists: ${authorName}`);
      return existingAuthors[0].id;
    }
    
    // Create new author
    const createRes = await fetch(`${API}/authors`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${TOKEN}`
      },
      body: JSON.stringify({
        name: authorName,
        slug: authorSlug,
        bio: `${authorName} is a child author${authorAge ? ' aged ' + authorAge : ''}${authorLocation ? ' from ' + authorLocation : ''}.`,
        author_type: 'child',
        age: authorAge || '',
        location: authorLocation || '',
        is_published: true
      })
    });
    
    if (!createRes.ok) {
      throw new Error(`Failed to create author: ${createRes.status} ${createRes.statusText}`);
    }
    
    const newAuthor = await createRes.json();
    console.log(`  - Created child author: ${authorName}${authorAge ? ', age: ' + authorAge : ''}${authorLocation ? ', location: ' + authorLocation : ''}`);
    return newAuthor.id;
  } catch (err) {
    console.error(`  - Error creating author from ${file}: ${err.message}`);
    return null;
  }
}

async function getAuthorIdByName(name) {
  if (!name) return null;
  
  try {
    // First try exact slug match
    const slug = name.toLowerCase().replace(/\s+/g, '-');
    console.log(`  - Looking up author by slug: "${slug}"`);
    
    const res = await fetch(`${API}/authors?slug=${slug}`, {
      headers: { 'Authorization': `Bearer ${TOKEN}` }
    });
    
    if (!res.ok) {
      throw new Error(`Failed to fetch author by slug: ${res.status} ${res.statusText}`);
    }
    
    const authors = await res.json();
    
    if (authors && authors.length > 0) {
      console.log(`  - Found author by slug: ${authors[0].name} (ID: ${authors[0].id})`);
      return authors[0].id;
    }
    
    // If slug match fails, try name match
    console.log(`  - No author found by slug, trying name match: "${name}"`);
    const nameRes = await fetch(`${API}/authors?name=${encodeURIComponent(name)}`, {
      headers: { 'Authorization': `Bearer ${TOKEN}` }
    });
    
    if (!nameRes.ok) {
      throw new Error(`Failed to fetch author by name: ${nameRes.status} ${nameRes.statusText}`);
    }
    
    const nameAuthors = await nameRes.json();
    
    if (nameAuthors && nameAuthors.length > 0) {
      console.log(`  - Found author by name: ${nameAuthors[0].name} (ID: ${nameAuthors[0].id})`);
      return nameAuthors[0].id;
    }
    
    console.log(`  - No author found for "${name}"`);
    return null;
  } catch (err) {
    console.error(`  - Error getting author ID for ${name}: ${err.message}`);
    return null;
  }
}

async function importStory(file) {
  try {
    const raw = await fs.readFile(file, 'utf8');
    const { data, content } = matter(raw);
    const html = marked.parse(content);
    const slug = data.slug || path.basename(path.dirname(file));
    
    console.log(`\nImporting story: ${data.title}`);
    
    // Process images in the story directory
    const storyDir = path.dirname(file);
    const imagesDir = path.join(storyDir, 'images');
    let coverUrl = null;
    
    try {
      // Check if images directory exists
      await fs.access(imagesDir);
      
      // Get all image files
      const imageFiles = await fs.readdir(imagesDir);
      
      if (imageFiles.length > 0) {
        // Use the first image as cover
        const coverPath = path.join(imagesDir, imageFiles[0]);
        coverUrl = await uploadMedia(coverPath);
        console.log(`  - Set cover image: ${imageFiles[0]}`);
        
        // Upload any additional images
        for (let i = 1; i < imageFiles.length; i++) {
          const imagePath = path.join(imagesDir, imageFiles[i]);
          await uploadMedia(imagePath);
          console.log(`  - Uploaded additional image: ${imageFiles[i]}`);
        }
      }
    } catch (err) {
      // Images directory might not exist, which is fine
      console.log(`  - No images directory found or error accessing images: ${err.message}`);
    }
    
    // Process tags
    const tagIds = [];
    if (data.tags && Array.isArray(data.tags)) {
      for (const tag of data.tags) {
        const tagId = await ensureTag(tag);
        if (tagId) tagIds.push(tagId);
      }
    }
    
    // Extract author info from title
    let authorId = null;
    let authorAge = null;
    let authorLocation = null;
    const authorMatch = data.title.match(/by\s+([^,]+?)(?:,?\s+aged\s+(\d+))?(?:,?\s+from\s+([^,]+))?/i);
    
    if (authorMatch && authorMatch[1]) {
      const authorName = authorMatch[1].trim();
      authorAge = authorMatch[2] || '';
      authorLocation = authorMatch[3] || '';
      
      console.log(`  - Extracted author from title: Name="${authorName}", Age=${authorAge || 'unknown'}, Location="${authorLocation || 'unknown'}"`);
      
      // First try to find existing author
      authorId = await getAuthorIdByName(authorName);
      
      // If author not found, create a new one
      if (!authorId) {
        console.log(`  - Author not found, creating new author: ${authorName}`);
        
        // Create author using the same function as in the first pass
        authorId = await createAuthorFromStory(file);
        
        if (authorId) {
          console.log(`  - Created new author with ID: ${authorId}`);
        } else {
          console.log(`  - Failed to create author for: ${authorName}`);
        }
      } else {
        console.log(`  - Found existing author ID: ${authorId}`);
      }
    } else {
      console.log(`  - Could not extract author info from title: "${data.title}"`);
    }
    
    // Calculate estimated reading time (average reading speed: 200 words per minute)
    const wordCount = content.split(/\s+/).length;
    const readingTimeMinutes = Math.max(1, Math.ceil(wordCount / 200));
    const estimatedReadingTime = `${readingTimeMinutes} minute${readingTimeMinutes !== 1 ? 's' : ''}`;
    
    // Determine age group based on author age or content
    let ageGroup = '7-12';  // Default for children's stories
    if (authorAge) {
      const age = parseInt(authorAge, 10);
      if (age <= 6) ageGroup = '0-6';
      else if (age <= 9) ageGroup = '7-9';
      else if (age <= 12) ageGroup = '10-12';
      else ageGroup = '13+';
    }
    
    // Generate tags based on content if none provided
    let generatedTags = [];
    if (!data.tags || !Array.isArray(data.tags) || data.tags.length === 0) {
      // Simple keyword extraction
      const keywords = [
        'adventure', 'animals', 'fantasy', 'friendship', 'magic',
        'school', 'family', 'nature', 'space', 'dinosaurs',
        'robots', 'monsters', 'fairy tale', 'mystery'
      ];
      
      const contentLower = content.toLowerCase();
      generatedTags = keywords.filter(keyword =>
        contentLower.includes(keyword.toLowerCase())
      ).slice(0, 3);  // Take up to 3 matching tags
      
      // Always add 'children's story' tag
      if (!generatedTags.includes('children\'s story')) {
        generatedTags.push('children\'s story');
      }
      
      console.log(`  - Generated tags: ${generatedTags.join(', ')}`);
    }
    
    // Create the story
    const storyData = {
      title: data.title,
      slug,
      content: html,
      excerpt: data.excerpt || content.substring(0, 150) + '...',
      is_published: true,
      featured: data.sticky ? 1 : 0,
      source_type: 'child',
      allow_reviews: 0,
      cover_url: coverUrl,
      estimated_reading_time: estimatedReadingTime,
      age_group: ageGroup,
      tags: generatedTags.join(',')
    };
    
    const storyRes = await fetch(`${API}/stories`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${TOKEN}`
      },
      body: JSON.stringify(storyData)
    });
    
    if (!storyRes.ok) {
      throw new Error(`Failed to create story: ${storyRes.status} ${storyRes.statusText}`);
    }
    
    const storyResult = await storyRes.json();
    console.log(`  - Created story with ID: ${storyResult.id}`);
    
    // If we have an author ID and story was created successfully, associate them
    if (authorId && storyResult && storyResult.id) {
      try {
        console.log(`  - Attempting to associate story ID ${storyResult.id} with author ID ${authorId}`);
        
        const associateRes = await fetch(`${API}/story-authors`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${TOKEN}`
          },
          body: JSON.stringify({
            story_id: storyResult.id,
            author_id: authorId
          })
        });
        
        // Log the full response for debugging
        const responseText = await associateRes.text();
        console.log(`  - Association response: ${associateRes.status} ${associateRes.statusText}`);
        console.log(`  - Response body: ${responseText.substring(0, 200)}...`);
        
        if (!associateRes.ok) {
          throw new Error(`Failed to associate story with author: ${associateRes.status} ${associateRes.statusText}`);
        }
        
        console.log(`  - Successfully associated story with author ID: ${authorId}`);
        
        // Verify the association was created
        const verifyRes = await fetch(`${API}/story-authors?story_id=${storyResult.id}`, {
          headers: { 'Authorization': `Bearer ${TOKEN}` }
        });
        
        if (verifyRes.ok) {
          const associations = await verifyRes.json();
          if (associations && associations.length > 0) {
            console.log(`  - Verified association exists in database`);
          } else {
            console.log(`  - Warning: Association not found in database after creation`);
          }
        }
      } catch (err) {
        console.error(`  - Error associating story with author: ${err.message}`);
      }
    } else {
      console.log(`  - Cannot associate story with author: ${!authorId ? 'Missing author ID' : 'Missing story ID'}`);
    }
    
    return storyResult.id;
  } catch (err) {
    console.error(`Error importing story ${file}: ${err.message}`);
    return null;
  }
}

// Start the migration process
main().catch(err => {
  console.error(`Migration failed: ${err.message}`);
  process.exit(1);
});