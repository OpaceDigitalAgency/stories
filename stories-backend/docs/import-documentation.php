<?php
/**
 * Import System Documentation
 * 
 * This file provides comprehensive documentation for the import system
 * with diagrams showing the process flow, database relationships, etc.
 */

// Set appropriate content type
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories From The Web - Import System Documentation</title>
    <!-- Include Mermaid.js for diagrams -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            mermaid.initialize({
                startOnLoad: true,
                theme: 'default',
                securityLevel: 'loose',
                flowchart: { useMaxWidth: false }
            });
        });
    </script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3, h4 {
            color: #2c3e50;
            margin-top: 1.5em;
        }
        h1 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
        }
        code {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        pre code {
            background-color: transparent;
            padding: 0;
        }
        .mermaid {
            margin: 20px 0;
            text-align: center;
        }
        .note {
            background-color: #e7f5fe;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .warning {
            background-color: #fff5e6;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .toc {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .toc ul {
            list-style-type: none;
            padding-left: 20px;
        }
        .toc li {
            margin: 5px 0;
        }
        .key-point {
            background-color: #eafaf1;
            border-left: 4px solid #2ecc71;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .function-header {
            background-color: #f0f7fc;
            padding: 10px;
            border-radius: 5px;
            margin-top: 30px;
            border-left: 4px solid #3498db;
        }
        .section-nav {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .section-nav a {
            text-decoration: none;
            color: #3498db;
        }
        .section-nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Stories From The Web - Import System Documentation</h1>
    
    <div class="toc">
        <h2>Table of Contents</h2>
        <ul>
            <li><a href="#overview">1. Overview</a></li>
            <li><a href="#architecture">2. Import System Architecture</a></li>
            <li><a href="#data-sources">3. Data Sources</a>
                <ul>
                    <li><a href="#wordpress-export">3.1 WordPress Export XML</a></li>
                    <li><a href="#markdown-files">3.2 Markdown Files Structure</a></li>
                </ul>
            </li>
            <li><a href="#process-flow">4. Import Process Flow</a></li>
            <li><a href="#key-components">5. Key Components</a>
                <ul>
                    <li><a href="#content-cleaning">5.1 Content Cleaning</a></li>
                    <li><a href="#author-extraction">5.2 Author Extraction and Management</a></li>
                    <li><a href="#story-processing">5.3 Story Processing</a></li>
                    <li><a href="#tag-management">5.4 Tag Management</a></li>
                    <li><a href="#media-handling">5.5 Media Handling</a></li>
                </ul>
            </li>
            <li><a href="#database-schema">6. Database Schema</a></li>
            <li><a href="#code-structure">7. Code Structure</a></li>
            <li><a href="#key-functions">8. Key Functions</a></li>
            <li><a href="#improvements">9. Potential Improvements</a></li>
        </ul>
    </div>

    <section id="overview">
        <h2>1. Overview</h2>
        <p>
            The import system is responsible for migrating content from a WordPress export into the custom database structure 
            of the Stories From The Web platform. It handles various content types including children's stories, retail publisher 
            stories, games, and artwork.
        </p>
        <p>
            The system is designed to be selective in its data cleaning, only removing content related to the specific 
            content type being imported. It extracts author information, processes media files, generates metadata, 
            and handles tag associations.
        </p>
    </section>
<section id="architecture">
        <h2>2. Import System Architecture</h2>
        <p>
            The import system is built around a PHP-based web interface (<code>direct_import.php</code>) that provides 
            administrators with a tool to import content from WordPress exports into the Stories platform. The system 
            follows a modular approach with specialized functions for different aspects of the import process.
        </p>

        <div class="mermaid">
            graph TD
                A[Admin User] -->|Initiates Import| B[direct_import.php]
                B -->|Cleans Existing Data| C[cleanContentData]
                B -->|Processes Stories| D[processStory]
                D -->|Extracts Author Info| E[extractAuthorInfo]
                D -->|Gets/Creates Author| F[getOrCreateAuthor]
                D -->|Extracts Excerpt| G[extractExcerpt]
                D -->|Generates Slug| H[generateUniqueSlug]
                D -->|Processes Tags| I[processStoryTags]
                D -->|Extracts Tags| J[extractTags]
                D -->|Calculates Reading Time| K[getReadingTime]
                D -->|Determines Age Group| L[getAgeGroup]
                D -->|Handles Media| M[Media Processing]
                D -->|Inserts/Updates Story| N[Database Operations]
        </div>
    </section>

    <section id="data-sources">
        <h2>3. Data Sources</h2>

        <section id="wordpress-export">
            <h3>3.1 WordPress Export XML</h3>
            <p>
                The system uses a WordPress export file (<code>export.xml</code>) which contains all the content from the 
                original WordPress site. This XML file follows the WordPress eXtended RSS (WXR) format and includes:
            </p>
            <ul>
                <li>Posts and pages</li>
                <li>Custom post types (like <code>childrens-story</code>)</li>
                <li>Categories and tags</li>
                <li>Authors</li>
                <li>Comments</li>
                <li>Media attachments</li>
                <li>Custom taxonomies and metadata</li>
            </ul>
            <p>
                The export.xml file serves as a reference but is not directly used by the import script. Instead, 
                the content has been pre-processed into markdown files.
            </p>
        </section>

        <section id="markdown-files">
            <h3>3.2 Markdown Files Structure</h3>
            <p>
                The primary source for the import process is a directory structure containing markdown files and associated media:
            </p>
            <pre><code>_wp migration/
├── export.xml
├── package.json
├── package-lock.json
├── uploads/
└── wp-md/
    ├── custom/
    │   ├── childrens-story/
    │   │   ├── a-windy-day-by-dearbhla-aged-9-from-northern-ireland/
    │   │   │   ├── index.md
    │   │   │   └── images/
    │   │   │       └── whimsical-wind-playful-girl.png
    │   │   ├── autumn-poem-by-niall-aged-9-from-omagh-northern-ireland-co-tyrone/
    │   │   └── ...
    │   ├── book/
    │   ├── featured-content/
    │   └── ...
    └── pages/</code></pre>
            <p>
                Each story is contained in its own directory with:
            </p>
            <ul>
                <li>An <code>index.md</code> file containing the story content with front matter</li>
                <li>An <code>images/</code> directory containing any associated images</li>
            </ul>
            <p>
                The markdown files follow a structured format:
            </p>
            <pre><code>---
title: "A Windy Day by Dearbhla, aged 9, from Northern Ireland"
date: 2023-07-18
coverImage: "whimsical-wind-playful-girl.png"
---

## Author

**Name:** Dearbhla

**Age:** 9

**Location:** Northern Ireland

## Summary

On a windy day, a little girl experiences the swirling leaves and the howling wind, bringing the world to life around her.

## Story

It's a windy day, So I go out to play. In the garden the leaves are scattered, And the trees bow down battered...</code></pre>
        </section>
    </section>

    <section id="process-flow">
        <h2>4. Import Process Flow</h2>
        <p>
            The import process follows these steps:
        </p>
        <ol>
            <li>Initialization: The admin selects content type, source type, and whether to clean existing data</li>
            <li>Data Cleaning: If selected, existing data for the specified content type is removed</li>
            <li>Directory Identification: The system locates the appropriate directory containing markdown files</li>
            <li>Content Processing: For each story directory:
                <ul>
                    <li>Read the markdown file</li>
                    <li>Extract front matter and content</li>
                    <li>Extract author information from the title</li>
                    <li>Process or create the author</li>
                    <li>Handle cover images</li>
                    <li>Extract excerpt</li>
                    <li>Generate a unique slug</li>
                    <li>Calculate reading time</li>
                    <li>Determine age group</li>
                    <li>Extract tags</li>
                    <li>Check if the story already exists</li>
                    <li>Insert or update the story in the database</li>
                    <li>Associate the story with the author</li>
                    <li>Process tags for the story</li>
                </ul>
            </li>
            <li>Reporting: Display summary statistics of the import process</li>
        </ol>

        <div class="mermaid">
            sequenceDiagram
                participant Admin
                participant ImportUI
                participant CleanData
                participant StoryProcessor
                participant Database
                
                Admin->>ImportUI: Select content type & options
                Admin->>ImportUI: Start import
                ImportUI->>CleanData: Clean existing data (if selected)
                CleanData->>Database: Delete related content
                ImportUI->>ImportUI: Identify source directory
                loop For each story directory
                    ImportUI->>StoryProcessor: Process story
                    StoryProcessor->>StoryProcessor: Extract author info
                    StoryProcessor->>Database: Get/Create author
                    StoryProcessor->>StoryProcessor: Process media
                    StoryProcessor->>StoryProcessor: Extract metadata
                    StoryProcessor->>Database: Insert/Update story
                    StoryProcessor->>Database: Associate with author
                    StoryProcessor->>Database: Process tags
                end
                ImportUI->>Admin: Display import summary
        </div>
    </section>
<section id="key-components">
        <h2>5. Key Components</h2>

        <section id="content-cleaning">
            <h3>5.1 Content Cleaning</h3>
            <p>
                The <code>cleanContentData()</code> function is responsible for removing existing content before importing new data. 
                This ensures that there are no duplicates or orphaned records. The function:
            </p>
            <ol>
                <li>Takes parameters for content type and source type</li>
                <li>Begins a database transaction for data integrity</li>
                <li>Retrieves IDs of content to be deleted</li>
                <li>Deletes associations (tags, authors)</li>
                <li>Deletes the content items</li>
                <li>Deletes unused authors</li>
                <li>Deletes associated media files</li>
                <li>Commits the transaction</li>
            </ol>
            <p>
                The cleaning process is selective, only removing content that matches the specified content type and source type, 
                preserving other content in the database.
            </p>
        </section>

        <section id="author-extraction">
            <h3>5.2 Author Extraction and Management</h3>
            <p>
                Author information is extracted from story titles using regex patterns. The system looks for patterns like:
            </p>
            <ul>
                <li>"Story Title by Author Name aged X from Location"</li>
                <li>"Story Title - Author, aged X, from Location"</li>
            </ul>
            <p>
                The <code>extractAuthorInfo()</code> function uses multiple regex patterns to handle different title formats and extracts:
            </p>
            <ul>
                <li>Author name</li>
                <li>Author age</li>
                <li>Author location</li>
            </ul>
            <p>
                The <code>getOrCreateAuthor()</code> function then:
            </p>
            <ol>
                <li>Checks if the author already exists (by name or slug)</li>
                <li>If found, updates the author's information</li>
                <li>If not found, creates a new author record</li>
                <li>Returns the author ID for association with the story</li>
            </ol>
        </section>

        <section id="story-processing">
            <h3>5.3 Story Processing</h3>
            <p>
                The <code>processStory()</code> function is the core of the import system, handling the entire process for a single story:
            </p>
            <ol>
                <li>Reads the markdown file</li>
                <li>Extracts front matter and content</li>
                <li>Calls other specialized functions for author extraction, excerpt creation, etc.</li>
                <li>Processes cover images</li>
                <li>Checks if the story already exists</li>
                <li>Updates existing stories or inserts new ones</li>
                <li>Associates the story with its author</li>
                <li>Processes tags for the story</li>
            </ol>
            <p>
                The function uses database transactions to ensure data integrity, with rollback capabilities in case of errors.
            </p>
        </section>

        <section id="tag-management">
            <h3>5.4 Tag Management</h3>
            <p>
                Tags are extracted from:
            </p>
            <ol>
                <li>Front matter (if available)</li>
                <li>Content analysis (if no tags in front matter)</li>
            </ol>
            <p>
                The <code>extractTags()</code> function analyzes the content for common themes and keywords if no tags are provided 
                in the front matter. It normalizes tags by converting to lowercase and removing special characters.
            </p>
            <p>
                The <code>processStoryTags()</code> function:
            </p>
            <ol>
                <li>Deletes existing tag associations for the story</li>
                <li>For each tag:
                    <ul>
                        <li>Checks if the tag exists</li>
                        <li>Creates the tag if it doesn't exist</li>
                        <li>Associates the tag with the story</li>
                    </ul>
                </li>
            </ol>
        </section>

        <section id="media-handling">
            <h3>5.5 Media Handling</h3>
            <p>
                The import system handles media files by:
            </p>
            <ol>
                <li>Looking for images in the story directory</li>
                <li>Using the first image as the cover image</li>
                <li>Copying the image to the uploads directory</li>
                <li>Storing the image path in the database</li>
            </ol>
            <p>
                If no images are found, a default cover image is used.
            </p>
        </section>
    </section>

    <section id="database-schema">
        <h2>6. Database Schema</h2>
        <p>
            The import system interacts with several database tables:
        </p>

        <div class="mermaid">
            erDiagram
                stories {
                    int id PK
                    string title
                    string slug
                    text content
                    text excerpt
                    string cover_url
                    boolean is_published
                    string source_type
                    boolean allow_reviews
                    int estimated_reading_time
                    string age_group
                    int media_id FK
                }
                
                authors {
                    int id PK
                    string name
                    string slug
                    text bio
                    string author_type
                    int age
                    string location
                    boolean is_published
                }
                
                story_authors {
                    int story_id FK
                    int author_id FK
                }
                
                tags {
                    int id PK
                    string name
                    string slug
                }
                
                story_tags {
                    int story_id FK
                    int tag_id FK
                }
                
                media {
                    int id PK
                    string file_path
                    string file_type
                    int file_size
                }
                
                stories ||--o{ story_authors : has
                authors ||--o{ story_authors : belongs_to
                stories ||--o{ story_tags : has
                tags ||--o{ story_tags : belongs_to
                stories ||--o| media : has
        </div>
        <p>
            Database operations are wrapped in transactions to ensure data integrity. If an error occurs during the import of a story, 
            the transaction is rolled back to prevent partial imports.
        </p>
    </section>
<section id="code-structure">
        <h2>7. Code Structure</h2>
        <p>
            The import system is primarily contained in the <code>direct_import.php</code> file, which serves both as the user interface 
            and the processing engine for the import process. The file is structured as follows:
        </p>
        <pre><code>direct_import.php
├── PHP Configuration
├── Helper Functions
│   ├── flushOutput()
│   ├── cleanContentData()
│   ├── extractAuthorInfo()
│   ├── extractExcerpt()
│   ├── extractTags()
│   ├── processStoryTags()
│   ├── findExistingStory()
│   ├── generateUniqueSlug()
│   ├── getAgeGroup()
│   ├── getReadingTime()
│   ├── getOrCreateAuthor()
│   └── processStory()
├── HTML Header
├── HTML Form
└── Processing Logic</code></pre>
    </section>

    <section id="key-functions">
        <h2>8. Key Functions</h2>

        <div class="function-header">
            <h3><code>cleanContentData($db, $contentType, $sourceType = null)</code></h3>
        </div>
        <p>
            Responsible for removing existing content before importing new data.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Uses database transactions for data integrity</li>
                <li>Selectively removes content based on content type and source type</li>
                <li>Handles associations (tags, authors) properly</li>
                <li>Cleans up unused authors and media files</li>
                <li>Reports on the number of items deleted</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>extractAuthorInfo($title)</code></h3>
        </div>
        <p>
            Extracts author information from the story title using regex patterns.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Uses multiple regex patterns to handle different title formats</li>
                <li>Extracts name, age, and location</li>
                <li>Has fallback patterns for partial matches</li>
                <li>Returns structured author information</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>getOrCreateAuthor($db, $authorInfo, $authorType = 'child')</code></h3>
        </div>
        <p>
            Either retrieves an existing author or creates a new one.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Checks for existing authors by name or slug</li>
                <li>Updates existing authors with new information</li>
                <li>Creates new authors if they don't exist</li>
                <li>Generates a bio automatically based on available information</li>
                <li>Returns the author ID for association with stories</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>processStory($db, $storyDir)</code></h3>
        </div>
        <p>
            The main function that processes a single story directory.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Reads and parses markdown files with front matter</li>
                <li>Extracts author information from the title</li>
                <li>Processes cover images</li>
                <li>Generates metadata (excerpt, slug, reading time, age group)</li>
                <li>Extracts tags from content</li>
                <li>Checks for existing stories to update or create new ones</li>
                <li>Associates stories with authors and tags</li>
                <li>Uses database transactions for data integrity</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>extractTags($frontMatter, $markdownContent)</code></h3>
        </div>
        <p>
            Extracts tags from the front matter or content.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Tries to extract tags from front matter first</li>
                <li>Falls back to content analysis if no tags are found</li>
                <li>Uses a list of common children's story themes</li>
                <li>Normalizes tags (lowercase, remove special characters)</li>
                <li>Ensures at least some default tags are provided</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>processStoryTags($db, $storyId, $tags)</code></h3>
        </div>
        <p>
            Associates tags with a story.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Deletes existing tag associations for the story</li>
                <li>Checks if each tag exists in the database</li>
                <li>Creates new tags if they don't exist</li>
                <li>Associates tags with the story</li>
                <li>Uses prepared statements for security</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>extractExcerpt($title, $markdownContent)</code></h3>
        </div>
        <p>
            Extracts a clean, meaningful excerpt from the content.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Tries to extract from the Summary section first</li>
                <li>Falls back to the first paragraph if no summary is found</li>
                <li>Extracts just the first sentence for brevity</li>
                <li>Cleans up metadata from the excerpt</li>
                <li>Ensures a reasonable length for the excerpt</li>
            </ul>
        </div>

        <div class="function-header">
            <h3><code>generateUniqueSlug($db, $title)</code></h3>
        </div>
        <p>
            Generates a unique slug for a story.
        </p>
        <div class="key-point">
            <strong>Key Points:</strong>
            <ul>
                <li>Removes author information from the title</li>
                <li>Converts to lowercase and replaces non-alphanumeric characters with hyphens</li>
                <li>Checks if the slug already exists in the database</li>
                <li>Appends a number to ensure uniqueness</li>
                <li>Returns a clean, URL-friendly slug</li>
            </ul>
        </div>
    </section>

    <section id="improvements">
        <h2>9. Potential Improvements</h2>
        <p>
            The current import system is functional but could benefit from several improvements:
        </p>

        <h3>9.1 Modularization</h3>
        <p>
            Split the code into separate files for better organization:
        </p>
        <pre><code>import/
├── index.php
├── functions/
│   ├── cleaning.php
│   ├── authors.php
│   ├── stories.php
│   ├── tags.php
│   └── utils.php
└── templates/
    ├── form.php
    └── results.php</code></pre>

        <h3>9.2 Enhanced Error Handling</h3>
        <p>
            Add more specific error messages and logging:
        </p>
        <pre><code>try {
    // Operation
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    return ['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()];
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    return ['success' => false, 'error' => 'Processing error', 'details' => $e->getMessage()];
}</code></pre>

        <h3>9.3 Batch Processing</h3>
        <p>
            Implement batch processing for better performance:
        </p>
        <pre><code>function processBatch($db, $storyDirs, $batchSize = 10) {
    $results = ['success' => 0, 'errors' => 0];
    $batches = array_chunk($storyDirs, $batchSize);
    
    foreach ($batches as $batch) {
        foreach ($batch as $storyDir) {
            $result = processStory($db, $storyDir);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['errors']++;
            }
        }
        // Allow some time for the server to breathe
        usleep(100000);
    }
    
    return $results;
}</code></pre>

        <h3>9.4 Media Optimization</h3>
        <p>
            Add image optimization for better performance:
        </p>
        <pre><code>function optimizeImage($sourcePath, $destPath, $maxWidth = 1200, $maxHeight = 800) {
    // Create image resource based on file type
    $imageInfo = getimagesize($sourcePath);
    $mime = $imageInfo['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Get original dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate new dimensions while maintaining aspect ratio
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save resized image
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($resized, $destPath, 85); // 85% quality
                break;
            case 'image/png':
                imagepng($resized, $destPath, 8); // Compression level 8
                break;
            case 'image/gif':
                imagegif($resized, $destPath);
                break;
        }
        
        // Free memory
        imagedestroy($resized);
    } else {
        // Just copy the file if no resizing needed
        copy($sourcePath, $destPath);
    }
    
    // Free memory
    imagedestroy($image);
    
    return true;
}</code></pre>
    </section>

    <footer>
        <p>Documentation generated on <?php echo date('F j, Y'); ?></p>
    </footer>
</body>
</html>