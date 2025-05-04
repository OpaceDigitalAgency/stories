<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories from the Web - Comprehensive System Architecture</title>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>
    <script>
        mermaid.initialize({ startOnLoad: true, theme: 'default', securityLevel: 'loose' });
    </script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        h2 { margin-top: 40px; border-bottom: 1px solid #ddd; padding-bottom: 8px; }
        ul { margin: 10px 0 20px 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        code { background: #eef; padding: 2px 4px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f9f9f9; }
        .note { background: #ffffe0; padding: 10px; border-left: 5px solid #ff0; margin: 20px 0; }
        .mermaid { margin: 20px 0; background: #fff; padding: 20px; border-radius: 4px; }
    </style>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .endpoint { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0066cc; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px; }
        .response { margin-top: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>

</head>
<body>
    <h1>Stories from the Web - Comprehensive System Architecture</h1>

    <h2>Contents</h2>
    <ul>
        <li><a href="#overview">Overview</a></li>
        <li><a href="#tech-stack">Tech Stack</a></li>
        <li><a href="#environments">Environments</a></li>
        <li><a href="#architecture-components">Architecture Components</a></li>
        <li><a href="#frontend-architecture">Frontend Architecture</a></li>
        <li><a href="#backend-architecture">Backend Architecture</a></li>
        <li><a href="#database-schema">Database Schema</a></li>
        <li><a href="#api-documentation">API Documentation</a></li>
        <li><a href="#review-system">Review System</a></li>
        <li><a href="#authentication-system">Authentication System</a></li>
        <li><a href="#data-flow-processes">Data Flow Processes</a></li>
        <li><a href="#security">Security</a></li>
        <li><a href="#caching-performance">Caching & Performance</a></li>
        <li><a href="#key-files">Key Files</a></li>
        <li><a href="#documentation">Documentation</a></li>
        <li><a href="#deployment">Deployment</a></li>
        <li><a href="#monitoring-troubleshooting">Monitoring & Troubleshooting</a></li>
        <li><a href="#development-workflow">Development Workflow</a></li>
        <li><a href="#getting-started">Getting Started</a></li>
        <li><a href="#known-issues">Known Issues</a></li>
    </ul>

    <h2 id="overview">Overview</h2>
    <p>Stories from the Web is a platform for sharing and discovering user‑generated stories, complemented by games, directory listings, and AI‑powered tools. It follows a clear <em>separation of concerns</em>: a static frontend served via CDN and a RESTful PHP backend for data and content management.</p>

    <h2 id="tech-stack">Tech Stack</h2>
    <h3>Frontend</h3>
    <ul>
        <li><strong>Framework:</strong> Astro.js (v2.x) with TypeScript</li>
        <li><strong>Styling:</strong> Tailwind CSS</li>
        <li><strong>Build & Serve:</strong> Vite, deployed on Netlify</li>
        <li><strong>Package Manager:</strong> npm</li>
    </ul>
    <h3>Backend</h3>
    <ul>
        <li><strong>Language:</strong> PHP (v8.3.x)</li>
        <li><strong>Database:</strong> MySQL (v8.0.x)</li>
        <li><strong>Server:</strong> Apache on cPanel shared hosting</li>
        <li><strong>Authentication:</strong> Custom JWT</li>
        <li><strong>Deployment:</strong> cPanel Git Version Control</li>
    </ul>

    <h2 id="environments">Environments</h2>
    <h3>Production</h3>
    <ul>
        <li><strong>Frontend URL:</strong> https://storiesfromtheweb.netlify.app/</li>
        <li><strong>Backend API:</strong> https://api.storiesfromtheweb.org/api/v1/</li>
        <li><strong>Admin Interface:</strong> https://api.storiesfromtheweb.org/admin/</li>
    </ul>
    <h3>Development</h3>
    <ul>
        <li>Local server: http://localhost:3000 (frontend), http://localhost/stories-backend/api/v1 (API), http://localhost/stories-backend/admin (admin)</li>
        <li>Environment variables in <code>.env</code> and <code>config.php</code></li>
    </ul>

<p>Stories from the Web is a two‑tier publishing platform. Public Frontend – Static Astro TS/TW site served via Netlify CDN. Admin Backend – PHP CMS on cPanel shared hosting (JavaScript‑free UI).</p>
      <h3>Architecture Diagram</h3>
      <pre><code>┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│  Astro Frontend │────▶│   PHP REST API  │────▶│  MySQL Database │
│ (Netlify CDN)   │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘</code></pre>

      <h3>Key Benefits</h3>
      <ul>
        <li>Separation of Concerns – Frontend and backend can be developed independently</li>
        <li>Scalability – Each component can be scaled separately</li>
        <li>Security – Admin interface is isolated from public frontend</li>
        <li>Performance – Static frontend with dynamic data loading</li>
      </ul>

    <h2 id="architecture-components">Architecture Components</h2>
    <div class="mermaid">
graph TD
    A[Users] -->|View Content| F[Astro Frontend]
    F -->|API Requests| B[PHP API]
    B -->|Reads/Writes| C[MySQL DB]
    G[Content Creators] -->|Manage Content| H[Admin Panel]
    H -->|CRUD| B
    B -->|Direct Queries| C
    style A fill:#f9f,stroke:#333,stroke-width:1px
    style G fill:#ff9,stroke:#333,stroke-width:1px
    style H fill:#9ff,stroke:#333,stroke-width:1px
    style C fill:#cfc,stroke:#333,stroke-width:1px
    style F fill:#ccf,stroke:#333,stroke-width:1px
    style B fill:#ffc,stroke:#333,stroke-width:1px

    </div>
    <div class="note">No magic wizards—just clear, maintainable components working in harmony.</div>

    <h2 id="frontend-architecture">Frontend Architecture</h2>

    <p>A pure-PHP CMS on cPanel, optimised for reliability and security:</p>
      <ul>
        <li><strong>JavaScript-free UI</strong>: All interactions via CSS and HTML forms.</li>
        <li><strong>CSP enforcement</strong>: Blocks any rogue scripts.</li>
        <li><strong>User Experience</strong>: Direct form submissions and server redirects.</li>
        <li><strong>Session Management</strong>: PHP sessions for admin login state.</li>
      </ul>

        <p>Astro powers the front end, combining static generation with selective interactivity:</p>
      <ul>
        <li><strong>Static Site Generation</strong> at build time using API-fetched data.</li>
        <li><strong>Hydration Islands</strong> for dynamic components like rating widgets.</li>
      </ul>

      <h3>Image Optimization</h3>
      <p>The system includes comprehensive image optimization for both the frontend and backend:</p>
      <ul>
        <li><strong>Backend Processing:</strong> Images uploaded through the admin interface are automatically resized and optimized</li>
        <li><strong>WebP Conversion:</strong> JPEG and PNG images are converted to WebP format when supported by the browser</li>
        <li><strong>Responsive Images:</strong> Multiple sizes are generated for different viewport sizes</li>
        <li><strong>Lazy Loading:</strong> Images use the loading="lazy" attribute for improved performance</li>
        <li><strong>CDN Integration:</strong> Optimized images are served through a CDN for faster delivery</li>
      </ul>

      <h3>TypeScript Interfaces</h3>
      <p>The frontend uses TypeScript interfaces to ensure type safety when working with API data:</p>
      <pre><code>// src/types/Story.ts
export interface Story {
  id: number;
  title: string;
  content: string;
  excerpt: string;
  slug: string;
  is_published: boolean;
  featured: boolean;
  average_rating: number;
  allow_reviews: boolean;
  review_count: number;
  estimated_reading_time: string;
  age_group: string;
  cover_url: string;
  created_at: string;
  updated_at: string;
  authors: Author[];
  tags: Tag[];
}

// src/types/Author.ts
export interface Author {
  id: number;
  name: string;
  slug: string;
  bio: string | null;
  avatar_url: string | null;
  author_type: 'retail' | 'parent' | 'child' | 'educator';
  age: number | null;
  location: string | null;
}
</code></pre>

      <h3>TypeScript API Wrapper</h3>
      <pre><code>// src/lib/api.ts
export async function fetchStories() {
  const resp = await fetch(`${import.meta.env.PUBLIC_API_URL}/stories`);
  return resp.json();
}
</code></pre>
      <p><strong>Styling:</strong> Tailwind CSS for utility-first approach, keeping bundle sizes minimal.</p>

    <p>The static site generator Astro builds pages at deploy time, with optional client scripts for interactivity.</p>
    <pre><code>src/
├── components/         # Reusable UI blocks (NavHeader, CardStory, etc.)
├── lib/                # API client (src/lib/api.ts), mock data
├── pages/              # Astro pages (index.astro, stories/[slug].astro, etc.)
├── styles/             # Global styles (Tailwind imports)
└── types/              # TypeScript definitions
</code></pre>

    <h2 id="backend-architecture">Backend Architecture</h2>
    <p>A custom MVC‑style PHP backend exposes a versioned REST API and an admin interface.</p>
    <pre><code>stories-backend/
├── admin/              # Admin UI assets, templates, includes
│   ├── _archive/       # Archived unused implementations
│   ├── assets/         # CSS, JS, and other assets
│   ├── content/        # Active admin implementation (all admin pages)
│   ├── js/             # JavaScript files
│   ├── dashboard.php   # Main dashboard
│   ├── index.php       # Redirect to dashboard
│   ├── login.php       # Authentication
│   └── logout.php      # Session termination
├── api/                # v1 API router (api/v1/api.php, Middleware, Endpoints)
├── database/           # SQL dumps (stories_db_26.04.25_1337.sql)
├── .htaccess           # Apache config
└── .cpanel.yml         # Deployment tasks
</code></pre>

    <h3 id="admin-implementation">Admin Implementation</h3>
    <p>The admin interface is implemented as a set of standalone PHP files in the <code>stories-backend/admin/content/</code> directory. Each content type (stories, authors, tags, etc.) has its own set of files for listing, viewing, editing, and deleting items.</p>

    <div class="note">
        <strong>Important:</strong> There is an archived, unused CRUD-based implementation in <code>stories-backend/admin/_archive/unused_crud_implementation/</code>. This implementation uses a more structured approach with templates and classes, but it is not currently in use. All active admin pages are in the <code>stories-backend/admin/content/</code> directory.
    </div>

    <h4>Archive Directory Structure</h4>
    <p>The <code>_archive</code> directory contains unused or deprecated code that has been preserved for reference:</p>
    <pre><code>stories-backend/admin/_archive/
├── controllers/                # MVC controllers (unused)
├── unused_crud_implementation/ # Template-based CRUD system
│   ├── includes/               # Shared code and utilities
│   │   ├── Auth.php            # Authentication library
│   │   ├── Database.php        # Database connection
│   │   └── ...                 # Other utilities
│   ├── views/                  # Template files
│   │   ├── authors/            # Author templates
│   │   ├── stories/            # Story templates
│   │   └── ...                 # Other content type templates
│   ├── ai-tools.php            # AI tools listing
│   ├── authors.php             # Authors listing
│   ├── delete-author.php       # Author deletion
│   └── ...                     # Other content management files
└── uploads/                    # Legacy file upload directory
</code></pre>

    <p>When archiving new files, they should be placed in the appropriate subdirectory within the <code>_archive</code> directory to maintain consistent organization.</p>

    <h4>Admin File Structure</h4>
    <pre><code>stories-backend/admin/content/
├── authors.php         # List all authors
├── author-form.php     # Create/edit author form
├── author-delete.php   # Confirm author deletion
├── delete-author.php   # Process author deletion
├── stories.php         # List all stories
├── story-form.php      # Create/edit story form
└── ... (similar files for other content types)
</code></pre>

    <h4>Admin Authentication</h4>
    <p>The admin interface uses PHP sessions for authentication, managed by the <code>simple_auth.php</code> library. All admin pages check for an active session before allowing access.</p>

    <h4>Admin UI Design</h4>
    <p>The admin interface uses a simple, JavaScript-free design with direct form submissions and server redirects. This approach prioritizes reliability and security over complex client-side interactions.</p>

    <h4>Author Deletion Flow</h4>
    <p>The author deletion process follows a careful workflow to prevent accidental data loss:</p>
    <ol>
        <li>Admin clicks "Delete" on the authors listing page</li>
        <li>System displays a confirmation modal using Bootstrap</li>
        <li>If confirmed, the system checks for associated stories</li>
        <li>If stories exist, the admin must choose to either:
            <ul>
                <li>Delete the author and all associated stories</li>
                <li>Delete only the author and keep orphaned stories</li>
                <li>Cancel the deletion process</li>
            </ul>
        </li>
        <li>After confirmation, the system executes the deletion and redirects back to the authors listing</li>
    </ol>

    <h4>Bootstrap Modals</h4>
    <p>The admin interface uses Bootstrap modals for confirmation dialogs and alerts. These modals are triggered by server-side code and do not require JavaScript for basic functionality. The modals are styled using Bootstrap CSS and provide a consistent user experience across the admin interface.</p>

    <h4>Story Form with Age-Group Fields</h4>
    <p>The story creation and editing form includes specialized fields for managing age-appropriate content:</p>
    <ul>
        <li><strong>Age Group Selection:</strong> Dropdown menu with options for different age ranges (0-3, 4-6, 7-12, 13+)</li>
        <li><strong>Automatic Age Group Suggestion:</strong> When an author with the "child" type is selected, the system automatically suggests an age group based on the author's age</li>
        <li><strong>Content Warnings:</strong> Optional fields for content that may require parental guidance</li>
        <li><strong>Reading Level Indicators:</strong> Fields to specify reading difficulty and estimated reading time</li>
    </ul>

    <h4>Tag Extraction in Direct Import</h4>
    <p>The system includes a direct import feature (<code>direct_import.php</code>) that can analyze story content to automatically extract and suggest relevant tags:</p>
    <ul>
        <li><strong>Keyword Analysis:</strong> Scans content for common themes and subjects</li>
        <li><strong>Tag Matching:</strong> Compares extracted keywords against existing tags in the database</li>
        <li><strong>Tag Suggestions:</strong> Presents the admin with a list of suggested tags that can be selected with checkboxes</li>
        <li><strong>Custom Tag Creation:</strong> Allows adding new tags if the suggested ones are insufficient</li>
    </ul>

<h2 id="database-schema">Database Schema</h2>
 <p>Stories from the Web uses a relational model with both core tables and join tables to handle many-to-many relationships.</p>
      <h3>Core Tables</h3>
      <ul>
        <li><strong>stories</strong>: Holds each story’s title, slug, content, publication status, rating, timestamps.</li>
        <li><strong>authors</strong>: Author metadata (name, slug).</li>
        <li><strong>tags</strong>: Content categories.</li>
        <li><strong>users</strong>: Accounts for admin users.</li>
        <li><strong>auth_tokens</strong>: Session and JWT token records.</li>
        <li><strong>blog_posts</strong>, <strong>games</strong>, <strong>directory_items</strong>, <strong>ai_tools</strong>: Additional content types.</li>
      </ul>
      <h3>Join Tables</h3>
      <ul>
        <li><strong>story_authors</strong>: Links stories ↔ authors.</li>
        <li><strong>story_tags</strong>: Links stories ↔ tags.</li>
        <li><strong>author_stories</strong>: Alternate alias for author↔story relationships.</li>
      </ul>

      <h3>Content Management Features</h3>
      <ul>
        <li><strong>Automatic Tag Suggestion</strong>: Content analysis system that suggests relevant tags based on story content using keyword matching and natural language processing.</li>
        <li><strong>Age Group Management</strong>: Stories are categorized by age groups (0-3, 4-6, 7-12, 13+) with automatic suggestions based on child author ages.</li>
        <li><strong>Reading Time Estimation</strong>: Stories include estimated reading times to help readers choose appropriate content.</li>
      </ul>

      <h3>Review System</h3>
      <ul>
        <li><strong>Visibility Rules</strong>:
          <ul>
            <li>Child authors: Reviews disabled to maintain a supportive environment</li>
            <li>Parent authors: Optional review system</li>
            <li>Classic/retail authors: Reviews always enabled</li>
          </ul>
        </li>
        <li><strong>Rating System</strong>: 5-star rating system with review counts and average calculations</li>
      </ul>

      <h3>Moderation System</h3>
      <ul>
        <li><strong>Admin Controls</strong>: Moderation interface visible only to admin users for unpublished or flagged content</li>
        <li><strong>Publication Flow</strong>: Stories require admin approval before publication</li>
        <li><strong>Content Flags</strong>: System tracks needs_moderation flag for content requiring review</li>
      </ul>
    <div class="mermaid">
erDiagram
    USERS ||--o{ AUTH_TOKENS : has
    AUTHORS ||--o{ STORY_AUTHORS : has
    STORIES ||--o{ STORY_AUTHORS : has
    TAGS ||--o{ STORY_TAGS : has
    STORIES ||--o{ STORY_TAGS : has
    TAGS ||--o{ POST_TAGS : has
    BLOG_POSTS ||--o{ POST_TAGS : has
    AI_TOOL_CATEGORIES ||--o{ AI_TOOLS : has
    DIRECTORY_CATEGORIES ||--o{ DIRECTORY_ITEMS : has
</div>

    <h3>Author Types and Story Source Types</h3>
    <p>The system uses author types to categorize content creators:</p>
    <ul>
      <li><strong>retail</strong>: Professional book authors</li>
      <li><strong>parent</strong>: Parents who write stories</li>
      <li><strong>child</strong>: Children who write stories (includes age field, 1-21)</li>
      <li><strong>educator</strong>: Teachers and educational content creators</li>
    </ul>
    <p>Author types directly influence story source types and review capabilities:</p>
    <ul>
      <li>Child authors → child source type (reviews disabled)</li>
      <li>Parent authors → parent source type (reviews configurable)</li>
      <li>Retail/educator authors → classic source type (reviews always enabled)</li>
    </ul>
    <p>All authors have a location field that stores their city, county, or country, enabling filtering by location.</p>

    <pre><code>-- Full DB Schema (DDL)
CREATE TABLE `ai_tools` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `tool_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_type` enum('free','freemium','paid','subscription') COLLATE utf8mb4_unicode_ci DEFAULT 'free',
  `price_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` text COLLATE utf8mb4_unicode_ci,
  `rating` decimal(3,1) DEFAULT '0.0',
  `featured` tinyint(1) DEFAULT '0',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ai_tool_categories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `authors` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `author_type` enum('retail','parent','child','educator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'parent',
  `age` tinyint unsigned DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_tokens` (
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`token`)
) ENGINE=InnoDB;

CREATE TABLE `blog_posts` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text,
  `slug` varchar(255) NOT NULL,
  `is_published` tinyint(1) DEFAULT '0',
  `author_id` int DEFAULT NULL,
  `cover_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `directory_categories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `directory_items` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,1) DEFAULT '0.0',
  `price_range` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `story_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `games` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `featured` tinyint(1) DEFAULT '0',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `developer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT '0.0',
  `price` decimal(10,2) DEFAULT '0.00',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media` (
  `id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `post_tags` (
  `post_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `story_authors` (
  `story_id` int NOT NULL,
  `author_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `story_tags` (
  `story_id` int NOT NULL,
  `tag_id` int NOT NULL
) ENGINE=InnoDB;

CREATE TABLE `stories` (
  `id` int NOT NULL,
  `source_type` enum('child','parent','classic') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'child',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `featured` tinyint(1) DEFAULT '0',
  `average_rating` decimal(3,1) DEFAULT '4.5',
  `allow_reviews` tinyint(1) NOT NULL DEFAULT '0',
  `review_count` int DEFAULT '10',
  `estimated_reading_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '5 minutes',
  `is_sponsored` tinyint(1) DEFAULT '0',
  `age_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '12+',
  `needs_moderation` tinyint(1) DEFAULT '0',
  `is_self_published` tinyint(1) DEFAULT '1',
  `is_ai_enhanced` tinyint(1) DEFAULT '0',
  `cover_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'https://example.com/cover.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM;
</code></pre>


    <h2 id="api-documentation">API Documentation</h2>
    <div class="note">All endpoints are under <code>/api/v1</code>. Responses use a flat JSON format.</div>
    <h3>Content Endpoints</h3>
    <table>
      <tr><th>Endpoint</th><th>Method</th><th>Description</th><th>Auth</th></tr>
      <tr><td><code>/stories</code></td><td>GET</td><td>List stories (pagination, sorting, filters)</td><td>No</td></tr>
      <tr><td><code>/stories/{id}</code></td><td>GET</td><td>Get single story</td><td>No</td></tr>
      <tr><td><code>/authors</code></td><td>GET</td><td>List authors</td><td>No</td></tr>
      <tr><td><code>/authors/{id}</code></td><td>GET</td><td>Get single author</td><td>No</td></tr>
      <tr><td><code>/tags</code></td><td>GET</td><td>List tags</td><td>No</td></tr>
      <tr><td><code>/games</code></td><td>GET</td><td>List games</td><td>No</td></tr>
      <tr><td><code>/directory-items</code></td><td>GET</td><td>List directory items</td><td>No</td></tr>
      <tr><td><code>/ai-tools</code></td><td>GET</td><td>List AI tools</td><td>No</td></tr>
      <tr><td><code>/blog-posts</code></td><td>GET</td><td>List blog posts</td><td>No</td></tr>
      <tr><td><code>/submit-review</code></td><td>POST</td><td>Submit a review for a story</td><td>No</td></tr>
    </table>

    <h3>Admin Endpoints</h3>
    <table>
      <tr><th>Endpoint</th><th>Method</th><th>Action</th></tr>
      <tr><td><code>/admin/stories</code></td><td>POST</td><td>Create story</td></tr>
      <tr><td><code>/admin/stories/{id}</code></td><td>PUT</td><td>Update story</td></tr>
      <tr><td><code>/admin/stories/{id}</code></td><td>DELETE</td><td>Delete story</td></tr>
      <tr><td><code>/admin/media</code></td><td>POST</td><td>Upload media</td></tr>
    </table>
    <h3>Query Options</h3>
    <ul>
      <li><strong>Pagination:</strong> <code>?page=1&pageSize=25</code></li>
      <li><strong>Sorting:</strong> <code>?sort=title:asc</code></li>
      <li><strong>Filtering:</strong> <code>?featured=true&author=1</code></li>
    </ul>
    <h3>Rate Limiting & CORS</h3>
    <ul>
      <li>60 req/min (unauthenticated), 120 req/min (authenticated).</li>
      <li>CORS: <code>Access-Control-Allow-Origin: *</code>, methods <code>GET, POST, PUT, DELETE, OPTIONS</code>.</li>
    </ul>

    <?php
/**
 * Test API Format
 *
 * This script tests the API endpoints and checks if the admin interface can handle the response format.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to output text
function output($text, $isHtml = false) {
    echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
}

// Set content type
header('Content-Type: text/html; charset=utf-8');
output('


    <div class="container">
        <h2>Test API Format</h2>
', true);

output("<strong>API Endpoints</strong>", true);

// Test endpoints
$endpoints = [
    'stories' => '/api/v1/stories',
    'authors' => '/api/v1/authors',
    'games' => '/api/v1/games',
    'directory-items' => '/api/v1/directory-items',
    'ai-tools' => '/api/v1/ai-tools'
];

// Create a table for results
output("<table>", true);
output("<tr><th>Endpoint</th><th>Status</th><th>Format</th><th>Details</th></tr>", true);

// Function to test an endpoint
function testEndpoint($name, $path) {
    // Build the full URL
    $baseUrl = "https://api.storiesfromtheweb.org";
    $url = $baseUrl . $path;

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // Parse response
    $isJson = false;
    $jsonError = '';
    $decodedResponse = null;

    try {
        $decodedResponse = json_decode($response, true);
        $jsonError = json_last_error_msg();
        $isJson = json_last_error() === JSON_ERROR_NONE;
    } catch (Exception $e) {
        $jsonError = $e->getMessage();
    }

    // Check response format
    $format = "Invalid";
    $details = "";

    if ($isJson) {
        if (isset($decodedResponse['data'])) {
            $format = "Nested";
            $details = "Response has 'data' key";
        } else if (is_array($decodedResponse) && !empty($decodedResponse) && isset($decodedResponse[0])) {
            $format = "Flat";
            $details = "Response is a flat array";
        } else {
            $format = "Unknown";
            $details = "Response format is not recognized";
        }
    } else {
        $details = "Response is not valid JSON: $jsonError";
    }

    // Output results
    output("<tr>", true);
    output("<td>$name</td>", true);
    output("<td>" . ($httpCode >= 200 && $httpCode < 300 ? "<span class='success'>$httpCode</span>" : "<span class='error'>$httpCode</span>") . "</td>", true);
    output("<td>" . ($format === "Invalid" ? "<span class='error'>$format</span>" : "<span class='success'>$format</span>") . "</td>", true);
    output("<td><button onclick=\"toggleResponse('$name')\">View Response</button><div id='$name-details' style='display:none;'>$details<pre class='response'>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? "..." : "") . "</pre></div></td>", true);
    output("</tr>", true);

    return [
        'status' => $httpCode,
        'format' => $format,
        'details' => $details,
        'response' => $decodedResponse
    ];
}

// Test each endpoint
$results = [];
foreach ($endpoints as $name => $path) {
    $results[$name] = testEndpoint($name, $path);
}

output("</table>", true);

// Check for inconsistencies
output("<strong>Analysis</strong>", true);

$formats = array_unique(array_column($results, 'format'));
if (count($formats) > 1) {
    output("<div class='warning'>Inconsistent response formats detected!</div>", true);
    output("<p>The API endpoints are returning different response formats:</p>", true);

    foreach ($formats as $format) {
        $endpointsWithFormat = array_keys(array_filter($results, function($result) use ($format) {
            return $result['format'] === $format;
        }));

        output("<p><strong>$format format:</strong> " . implode(', ', $endpointsWithFormat) . "</p>", true);
    }

    output("<p>This can cause issues with the admin interface, which may expect a consistent format.</p>", true);
} else {
    output("<div class='success'>All endpoints are using the same response format: " . reset($formats) . "</div>", true);
}

// Add JavaScript for toggling response details
output("<script>
function toggleResponse(name) {
    var details = document.getElementById(name + '-details');
    if (details.style.display === 'none') {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
}
</script>", true);

// Close HTML
output('
    </div>
', true);
?>

    <h2 id="authentication-system">Authentication System</h2>
    <p>We employ both JWT and a SimpleAuth fallback for reliability.</p>
      <h3>Login Flow</h3>
      <ol>
        <li>Client posts credentials to <code>/auth/login</code>.</li>
        <li>On success, server returns a JWT (or sets a session cookie).</li>
        <li>Client includes <code>Authorization: Bearer &lt;token&gt;</code> on protected requests.</li>
      </ol>
      <h3>Token Handling</h3>
      <ul>
        <li>Tokens carry user ID and role claims.</li>
        <li>Expiry is configurable; refresh via <code>/auth/refresh</code>.</li>
        <li>SimpleAuth (added April 2025) catches occasional DB write locks on shared hosting.</li>
      </ul>
      <h3>Middleware</h3>
      <ul>
        <li><strong>AuthMiddleware</strong>: Validates JWT on API routes.</li>
        <li><strong>SimpleAuthMiddleware</strong>: Session-based fallback for admin pages.</li>
      </ul>
    <div class="mermaid">
sequenceDiagram
    participant U as User
    participant A as Admin UI
    participant API
    participant DB
    U->>A: Submit credentials
    A->>API: POST /auth/login
    API->>DB: Verify credentials
    DB-->>API: Valid
    API->>API: Generate JWT
    API->>DB: Store token
    API-->>A: Return token
    A-->>U: Redirect to dashboard
</div>

    <h3>Token Refresh</h3>
    <div class="mermaid">
sequenceDiagram
    participant F as Frontend
    participant API
    participant DB
    F->>API: Request with JWT
    API->>API: Validate
    alt Valid
      API->>DB: Serve request
      DB-->>API: Data
      API-->>F: Response
    else Expired
      API-->>F: 401 Unauthorized
      F->>API: POST /auth/refresh
      API->>API: New JWT
      API-->>F: New token
      F->>API: Retry original
    end
</div>

    <h2 id="data-flow-processes">Data Flow Processes</h2>
    <h3>Content Viewing</h3>
    <div class="mermaid">
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant API
    participant DB
    U->>F: Visit stories list
    F->>API: GET /stories
    API->>DB: Query stories
    DB-->>API: Return list
    API-->>F: JSON
    F-->>U: Render list
    U->>F: Click story
    F->>API: GET /stories/{id}
    API->>DB: Query story
    DB-->>API: Return story
    API-->>F: JSON
    F-->>U: Render story
</div>

    <h3>Content Creation</h3>
    <div class="mermaid">
sequenceDiagram
    participant Auth as Author
    participant UI as Admin UI
    participant API
    participant DB
    Auth->>UI: Login
    UI->>API: Authenticate
    API-->>UI: JWT
    Auth->>UI: Create story
    UI->>API: POST /admin/stories
    API->>DB: Insert record
    DB-->>API: OK
    API-->>UI: Success
    UI-->>Auth: Confirmation
</div>

    <h3>Error Handling</h3>
    <div class="mermaid">
sequenceDiagram
    participant F as Frontend
    participant API
    F->>API: Invalid request
    API-->>F: 400 Bad Request
    F-->>U: Show error
    F->>API: Expired token
    API-->>F: 401 Unauthorized
    F->>API: Refresh token
    API-->>F: New token
    F->>API: Retry
    API-->>F: Success
</div>

    <h2 id="security">Security</h2>
    <ul>
        <li><strong>JWT Tokens:</strong> Signed with secret, 24h lifetime, revocable.</li>
        <li><strong>Passwords:</strong> Bcrypt hashing, rate‑limited login attempts.</li>
        <li><strong>HTTPS:</strong> All traffic via TLS, cookies <code>Secure</code> & <code>HttpOnly</code>.</li>
        <li><strong>CSP & Sanitisation:</strong> Prevent XSS, validate inputs server‑side.</li>
        <li><strong>Anti-Bot Protection:</strong> Robust form protection without CAPTCHA.</li>
    </ul>

    <h3>Anti-Bot Protection System</h3>
    <p>The system includes comprehensive anti-bot protection for forms without requiring CAPTCHA:</p>
    <ul>
        <li><strong>Honeypot Fields:</strong> Hidden fields that only bots would fill out</li>
        <li><strong>Token-based Protection:</strong> Each form submission requires a valid token</li>
        <li><strong>Submission Timing:</strong> Checks for abnormally fast form submissions</li>
        <li><strong>User Agent Analysis:</strong> Detects common bot signatures in user agents</li>
        <li><strong>IP-based Rate Limiting:</strong> Prevents too many submissions from the same IP address</li>
        <li><strong>Silent Rejection:</strong> Bot submissions are silently rejected without alerting the bot</li>
    </ul>
    <pre><code>// Anti-Bot Protection Implementation
function isLikelyBot($data = []) {
    // Check for common bot signatures
    // 1. Check user agent
    // 2. Check if request has no user agent or referer
    // 3. Check for abnormally fast form submission
    // 4. Check for hidden honeypot field
    // 5. Check for missing or invalid token
    // 6. Check for too many submissions from the same IP
    // ...
}</code></pre>

    <h2 id="caching-performance">Caching & Performance</h2>
    <ul>
        <li>Frontend assets served from Netlify CDN with aggressive caching headers.</li>
        <li>No server‑side cache layer (Redis/​Memcached); any future optimisation would plug in here.</li>
        <li>Image assets on object storage/CDN as configured.</li>
    </ul>

    <h2 id="key-files">Key Files</h2>
    <h3>Frontend</h3>
    <ul>
        <li><code>src/lib/api.ts</code> – API client, error handling.</li>
        <li><code>src/types/components.ts</code> – TypeScript interfaces.</li>
        <li><code>src/components/NavHeader.astro</code>, <code>CardStory.astro</code>, etc.</li>
    </ul>
    <h3>Backend</h3>
    <ul>
        <li><code>api/v1/api.php</code> – Main router.</li>
        <li><code>admin/includes/Auth.php</code>, <code>Database.php</code>.</li>
        <li><code>admin/stories.php</code>, <code>admin/authors.php</code>.</li>
    </ul>

    <h2 id="documentation">Documentation</h2>
    <ul>
        <li><a href="../README.md">README.md</a></li>
        <li><a href="../PLANNING.md">PLANNING.md</a></li>
        <li><a href="../PROGRESS.md">PROGRESS.md</a></li>
        <li><a href="../API_CONNECTIVITY_FIX.md">API_CONNECTIVITY_FIX.md</a></li>
        <li><a href="project-cleanup-summary.md">Project Cleanup Summary</a></li>
        <li><a href="stories-cleanup-plan.md">Stories Cleanup Plan</a></li>
        <li><a href="database-schema.md">Database Schema</a></li>
        <li><a href="api-documentation.md">API Documentation</a></li>
        <li><a href="consolidated-deployment-guide.md">Consolidated Deployment Guide</a></li>
        <li><a href="documentation-index.md">Documentation Index</a></li>
    </ul>

    <h2 id="deployment">Deployment</h2>
    <h3>Frontend (Netlify)</h3>
    <div class="mermaid">
graph TD
    GitHub -->|push| Netlify{CI/CD}
    Netlify -->|build| Build[npm run build]
    Build -->|publish| CDN[Netlify CDN]
    CDN -->|serve| Users
    </div>
    <pre><code>netlify.toml
[build]
  command = "npm run build"
  publish = "dist"

[build.environment]
  PUBLIC_API_URL = "https://api.storiesfromtheweb.org/api/v1"
</code></pre>

    <h3>Backend (cPanel Git Version Control)</h3>
    <div class="mermaid">
graph TD
    GH[GitHub repo] -->|manual| CPANEL[Log into cPanel]
    CPANEL -->|Git Version Control| Repo[Select repo]
    Repo -->|Update from Remote| Pull[Pull latest]
    Pull -->|Deploy HEAD| Deploy[Run .cpanel.yml tasks]
    Deploy -->|serve| API[PHP API & Admin]
    </div>
    <pre><code>.cpanel.yml
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/stories/api.storiesfromtheweb.org/
    - /bin/cp -R stories-backend/*.php $DEPLOYPATH
    - /bin/cp -R stories-backend/admin $DEPLOYPATH
    - /bin/cp -R stories-backend/api $DEPLOYPATH
</code></pre>

    <h2 id="monitoring-troubleshooting">Monitoring & Troubleshooting</h2>
    <ul>
        <li><strong>Netlify:</strong> Build logs, deploy previews, rollback on failure.</li>
        <li><strong>cPanel Git:</strong> Check deployment logs, file permissions, .cpanel.yml formatting.</li>
        <li><strong>FTP Issues:</strong> Passive mode, credentials, cert verification.</li>
        <li><strong>HTTP 403:</strong> Review <code>.htaccess</code> file matches allowed scripts.</li>
    </ul>

    <h2 id="development-workflow">Development Workflow</h2>
    <h3>Git Workflow</h3>
    <p>The project follows a simplified git workflow:</p>
    <ul>
        <li><strong>Main Branch:</strong> The primary branch for development and deployment</li>
        <li><strong>Feature Branches:</strong> Created for specific features or bug fixes</li>
        <li><strong>Commit Conventions:</strong> Descriptive commit messages with prefixes (feat:, fix:, docs:, etc.)</li>
        <li><strong>Combined Operations:</strong> For efficiency, git add/commit/push operations are often combined into a single step</li>
    </ul>

    <h3>Code Organization</h3>
    <p>When working with the codebase, follow these guidelines:</p>
    <ul>
        <li><strong>Active Admin Pages:</strong> All active admin pages should be placed in the <code>stories-backend/admin/content/</code> directory</li>
        <li><strong>Archiving Code:</strong> Unused or deprecated code should be moved to the appropriate subdirectory within <code>stories-backend/admin/_archive/</code></li>
        <li><strong>Frontend Components:</strong> New frontend components should follow the existing TypeScript interfaces and Astro component structure</li>
        <li><strong>API Endpoints:</strong> New API endpoints should follow the existing pattern in <code>stories-backend/api/v1/</code></li>
    </ul>

    <h3>Development Environment</h3>
    <p>The local development environment consists of:</p>
    <ul>
        <li><strong>Frontend:</strong> Astro development server (npm run dev)</li>
        <li><strong>Backend:</strong> Local PHP server (XAMPP, MAMP, etc.)</li>
        <li><strong>Database:</strong> Local MySQL instance</li>
        <li><strong>API Configuration:</strong> Local API URL configured in <code>.env</code> file</li>
    </ul>

    <h3>Testing</h3>
    <p>The project includes several testing approaches:</p>
    <ul>
        <li><strong>Manual Testing:</strong> Primary method for testing admin functionality</li>
        <li><strong>API Testing:</strong> Using tools like Postman or the built-in test scripts</li>
        <li><strong>Frontend Testing:</strong> Visual inspection and browser testing</li>
    </ul>

    <h2 id="getting-started">Getting Started</h2>
    <h3>Clone & Install</h3>
    <pre><code>git clone https://github.com/OpaceDigitalAgency/stories.git
cd stories
npm install
npm run dev
</code></pre>
    <h3>Backend Setup</h3>
    <pre><code>mysql -u root -p stories_db < stories-backend/database/stories_db_26.04.25_1337.sql
# Edit stories-backend/api/v1/config/config.php with DB credentials
</code></pre>
    <h3>Credentials</h3>
    <ul>
        <li>Admin (dev only): <code>admin@storiesfromtheweb.org / password123</code></li>
        <li>API: use JWT from <code>/auth/login</code> endpoint</li>
    </ul>


      <h2 id="known-issues">Known Issues and Solutions</h2>
      <table>
        <thead>
          <tr><th>Issue</th><th>Root Cause</th><th>Solution / Tool</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>Case-sensitivity file errors</td><td>Inconsistent directory name capitalisation</td><td>Run <code>permanent_case_fix.php</code>; enforce PSR-4</td><td>Resolved (Apr 21)</td></tr>
          <tr><td>Frontend not rendering API content</td><td>Incorrect CORS and API URL settings</td><td>Update CORS headers; fix <code>PUBLIC_API_URL</code></td><td>In progress</td></tr>
          <tr><td>JWT validation failures</td><td>Secret mismatch across environments</td><td>Standardise JWT secret env-vars</td><td>Pending rollout</td></tr>
          <tr><td>Admin form “stuck on processing”</td><td>Legacy JS handler in PHP CMS</td><td>Remove JS; rely on server redirects</td><td>Fixed (Apr 19)</td></tr>
          <tr><td>Database write lock issues</td><td>Shared hosting lock contention</td><td>Added SimpleAuth fallback</td><td>Mitigated (Apr 26)</td></tr>
          <tr><td>Unexpected API response formats</td><td>Inconsistent controller error handling</td><td>Normalise JSON in middleware</td><td>Under review</td></tr>
          <tr><td>Missing favicon in admin</td><td>Incorrect path to favicon</td><td>Use absolute URL for favicon</td><td>Fixed (May 4)</td></tr>
          <tr><td>Duplicate headings on contact page</td><td>Header included twice</td><td>Remove duplicate header</td><td>Fixed (May 4)</td></tr>
          <tr><td>Bulk actions showing blank screen</td><td>Missing authentication check</td><td>Use auth-check.php include</td><td>Fixed (May 4)</td></tr>
          <tr><td>Bot submissions to forms</td><td>Lack of anti-bot protection</td><td>Implement anti-bot.php library</td><td>Fixed (May 4)</td></tr>
          <tr><td>Admin save/edit issues</td><td>Adding JS breaks the admin and prevents saving to the DB</td><td>Until understood, remove JS and rely on a pure HTML and CSS solutoon apart from auto slug generation</td><td>Under review</td></tr>
          <tr><td>Missing homepage content with prerender enabled</td><td>New stories don't sow until a fresh deploy is triggered</td><td>Remove prerender for now but consider webhooks to auto deploy when stories are added/removed</td><td>Under review</td></tr>
        </tbody>
      </table>



      <h2 id="image-optimization-system">Image Optimization System</h2>
      <p>The system includes a modular image optimization framework that ensures consistent handling of images across all parts of the application.</p>

      <h3>Core Components</h3>
      <ul>
        <li><code>includes/image_config.php</code>: Defines standard image sizes and formats</li>
        <li><code>includes/image_optimizer.php</code>: Contains modular functions for image processing</li>
        <li><code>public/update_media_schema.php</code>: Updates the database schema to support multiple image sizes</li>
        <li><code>public/fix_media_sizes.php</code>: Uses the image optimizer library to optimize all media files</li>
      </ul>

      <h3>Database Schema</h3>
      <p>The media table includes additional columns for different image sizes:</p>
      <pre><code>ALTER TABLE media
ADD COLUMN thumbnail_url VARCHAR(255) AFTER file_path,
ADD COLUMN small_url VARCHAR(255) AFTER thumbnail_url,
ADD COLUMN medium_url VARCHAR(255) AFTER small_url,
ADD COLUMN large_url VARCHAR(255) AFTER large_url;</code></pre>

      <h3>Standard Image Sizes</h3>
      <table>
        <thead>
          <tr><th>Size</th><th>Dimensions</th><th>Use Case</th></tr>
        </thead>
        <tbody>
          <tr><td>thumbnail</td><td>150x150</td><td>Avatar thumbnails, tiny previews</td></tr>
          <tr><td>small</td><td>300x300</td><td>Card thumbnails, small previews</td></tr>
          <tr><td>medium</td><td>640x640</td><td>Medium-sized previews, list views</td></tr>
          <tr><td>large</td><td>1200x800</td><td>Detail pages, full-width images</td></tr>
          <tr><td>original</td><td>(unchanged)</td><td>Original image (preserved if needed)</td></tr>
        </tbody>
      </table>

      <h3>Integration Points</h3>
      <ul>
        <li><strong>API Responses</strong>: Include all image URLs for different sizes</li>
        <li><strong>Frontend Components</strong>: Use appropriate image sizes for different contexts</li>
        <li><strong>Import Scripts</strong>: All import methods use the same image optimization library</li>
        <li><strong>Media Uploads</strong>: Manual uploads are processed through the same system</li>
      </ul>

      <p>For detailed documentation, see <a href="image_optimization_system.md">Image Optimization System</a>.</p>

</body>
</html>
