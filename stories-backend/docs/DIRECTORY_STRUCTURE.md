# Directory Structure

This document outlines the standard directory structure for the Stories From The Web application.

## Overview

The application follows a modular structure with clear separation of concerns:

```
stories-backend/
├── admin/                  # Admin interface
│   ├── assets/             # Static assets (CSS, JS, images)
│   ├── content/            # Content management pages
│   │   └── includes/       # Content-specific includes
│   ├── includes/           # Shared includes
│   └── js/                 # JavaScript files
├── api/                    # API endpoints
├── docs/                   # Documentation
├── includes/               # Shared includes for the entire application
├── logs/                   # Log files
├── public/                 # Public-facing files
└── scripts/                # Utility scripts
```

## Admin Directory Structure

The admin directory follows a consistent structure:

```
admin/
├── assets/                 # Static assets
│   ├── css/                # CSS files
│   ├── img/                # Image files
│   ├── js/                 # JavaScript files
│   └── webfonts/           # Web fonts
├── content/                # Content management pages
│   ├── ai-tools.php        # AI tools management
│   ├── author-delete.php   # Author deletion
│   ├── author-form.php     # Author form
│   ├── authors.php         # Authors list
│   ├── blog-post-form.php  # Blog post form
│   ├── blog-posts.php      # Blog posts list
│   ├── contacts.php        # Contacts management
│   ├── directory-items.php # Directory items
│   ├── games.php           # Games management
│   ├── includes/           # Content-specific includes
│   ├── media.php           # Media management
│   ├── stories.php         # Stories list
│   ├── story-form.php      # Story form
│   ├── subscribers.php     # Subscribers management
│   └── tags.php            # Tags management
├── dashboard.php           # Admin dashboard
├── includes/               # Shared includes
│   ├── auth-check.php      # Authentication check
│   ├── bulk-actions-component.php # Bulk actions component
│   ├── db-connect.php      # Database connection
│   ├── email-functions.php # Email functions
│   ├── footer.php          # Footer include
│   ├── header.php          # Header include
│   └── table-component.php # Table component
├── index.php               # Admin index/login
├── js/                     # JavaScript files
└── logout.php              # Logout script
```

## Includes Directory

The includes directory contains shared components that are used across the application:

```
includes/
├── config.php              # Configuration settings
├── db-connect.php          # Database connection
├── email-functions.php     # Email functions
├── footer.php              # Footer include for public pages
├── header.php              # Header include for public pages
└── utils.php               # Utility functions
```

## Public Directory

The public directory contains files that are directly accessible to users:

```
public/
├── assets/                 # Static assets
│   ├── css/                # CSS files
│   ├── img/                # Image files
│   └── js/                 # JavaScript files
├── favicon.ico             # Favicon
├── favicon.png             # Favicon (PNG version)
├── index.php               # Main index file
└── robots.txt              # Robots.txt file
```

## Best Practices

1. **Modular Components**: Use modular, reusable components that can be included across pages.
2. **Separation of Concerns**: Keep business logic separate from presentation.
3. **Consistent Naming**: Use consistent naming conventions for files and directories.
4. **Centralized Configuration**: Keep configuration settings in a central location.
5. **Shared Components**: Use shared components for common functionality.
