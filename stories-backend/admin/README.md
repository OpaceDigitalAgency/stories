# Stories Admin UI

This is the admin interface for the Stories from the Web project. It provides a user-friendly way to manage all content types in the database.

## Features

- **Dashboard**: Overview of content statistics
- **Content Management**: CRUD operations for all content types
- **Media Upload**: Upload and manage images
- **User Authentication**: Secure login with JWT integration
- **Responsive Design**: Works on mobile and desktop
- **Input Validation**: Client and server-side validation

## Accessing the Admin UI

You can access the admin interface using:

1. **Direct Login URL**:
   ```
   https://api.storiesfromtheweb.org/direct_login.php
   ```
   This bypasses the regular login process for quick access.

2. **Regular Admin Login**:
   ```
   https://api.storiesfromtheweb.org/admin/login.php
   ```
   Use the default credentials:
   - Email: `admin@storiesfromtheweb.org`
   - Password: `admin123` (change this after first login)

## Content Types

The admin UI allows you to manage:

### Stories
- Create, edit, and delete stories
- Upload cover images
- Assign authors and tags
- Set featured status

### Authors
- Manage author profiles
- Upload author photos
- Edit author bios

### Blog Posts
- Create and manage blog content
- Format content with rich text editor
- Schedule publication

### Directory Items
- Manage directory listings
- Categorize listings
- Add contact information

### Games
- Add and edit game listings
- Upload game screenshots
- Set age ratings and categories

### AI Tools
- Manage AI tool listings
- Set tool categories
- Add pricing information

### Tags
- Create and manage tags
- Assign tags to content

## Media Management

The media section allows you to:
- Upload images
- Organize images in folders
- Reuse images across content
- Delete unused media

## Technical Details

The admin UI is built with:
- PHP for server-side logic
- JavaScript for client-side interactions
- Bootstrap for responsive design
- API integration with the backend API

## Configuration

The admin UI is configured in `includes/config.php`:

```php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'stories_db');
define('DB_USER', 'stories_user');
define('DB_PASS', 'your_secure_password');

// API configuration
define('API_URL', 'https://api.storiesfromtheweb.org/api/v1');
define('API_TOKEN', ''); // Leave empty, will be set during login

// Admin configuration
define('ADMIN_EMAIL', 'admin@storiesfromtheweb.org');
define('ADMIN_PASSWORD', 'admin123'); // Change this after first login
```

## File Structure

```
admin/
├── assets/              # CSS, JS, and images
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── img/             # Admin UI images
├── controllers/         # Controller logic
├── includes/            # Shared PHP files
│   ├── AdminPage.php    # Base admin page class
│   ├── ApiClient.php    # API client for backend communication
│   ├── Auth.php         # Authentication logic
│   ├── config.php       # Configuration
│   ├── CrudPage.php     # Base CRUD page class
│   ├── Database.php     # Database connection
│   ├── FileUpload.php   # File upload handling
│   ├── Pagination.php   # Pagination logic
│   └── Validator.php    # Input validation
├── uploads/             # Uploaded media files
├── views/               # HTML templates
│   ├── ai-tools/        # AI tools templates
│   ├── auth/            # Login/logout templates
│   ├── authors/         # Authors templates
│   ├── blog-posts/      # Blog posts templates
│   ├── dashboard/       # Dashboard templates
│   ├── directory-items/ # Directory items templates
│   ├── games/           # Games templates
│   ├── generic/         # Shared templates
│   ├── media/           # Media management templates
│   ├── stories/         # Stories templates
│   ├── tags/            # Tags templates
│   ├── footer.php       # Footer template
│   └── header.php       # Header template
├── ai-tools.php         # AI tools page
├── authors.php          # Authors page
├── blog-posts.php       # Blog posts page
├── directory-items.php  # Directory items page
├── games.php            # Games page
├── index.php            # Dashboard
├── login.php            # Login page
├── logout.php           # Logout script
├── media.php            # Media management
├── stories.php          # Stories page
├── tags.php             # Tags page
└── README.md            # This file
```

## Security

The admin UI implements several security measures:

1. **Authentication**: JWT-based authentication
2. **CSRF Protection**: CSRF tokens for form submissions
3. **Input Validation**: All input is validated and sanitized
4. **XSS Protection**: Output is sanitized to prevent cross-site scripting
5. **Password Hashing**: Passwords are hashed using bcrypt

## Troubleshooting

### Login Issues

If you can't log in:
1. Check that the API is running and accessible
2. Verify the API URL in `includes/config.php`
3. Try using the direct login script at `/direct_login.php`

### Content Not Showing

If content isn't showing:
1. Check the database connection settings
2. Verify that the API endpoints are working
3. Check for JavaScript errors in the browser console

### Upload Issues

If file uploads aren't working:
1. Check the permissions on the `uploads` directory
2. Verify that the file size is within limits
3. Check for PHP configuration issues (upload_max_filesize, post_max_size)

## Deployment

The admin UI is deployed using cPanel's Git Version Control feature. See the `GIT_DEPLOYMENT.md` file in the root directory for detailed instructions.