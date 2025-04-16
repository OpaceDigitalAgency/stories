# Stories Admin UI

This is the admin UI for managing content in the Stories API database. It provides a user-friendly interface for performing CRUD operations on all content types.

## Features

- User authentication with JWT integration
- Dashboard with content statistics
- CRUD operations for all content types:
  - Stories
  - Authors
  - Blog Posts
  - Directory Items
  - Games
  - AI Tools
  - Tags
- Media upload and management
- Responsive design for mobile and desktop
- Input validation and error handling

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- Apache web server with mod_rewrite enabled
- cPanel hosting environment

## Installation Guide for cPanel

Follow these steps to install the Stories Admin UI on your cPanel hosting:

### 1. Upload Files

1. Log in to your cPanel account.
2. Navigate to the File Manager.
3. Go to the directory where you want to install the admin UI (e.g., `public_html`).
4. Create a new directory called `admin` (or any name you prefer).
5. Upload all the admin UI files to this directory.

### 2. Configure Database Connection

1. Open the file `includes/config.php`.
2. Update the database configuration with your database credentials:
   ```php
   $config['db'] = [
       'host'     => 'localhost',      // Database host
       'name'     => 'your_db_name',   // Database name
       'user'     => 'your_db_user',   // Database username
       'password' => 'your_db_password', // Database password
       'charset'  => 'utf8mb4',        // Character set
       'port'     => 3306              // Database port
   ];
   ```
3. Update the JWT secret key to match the one used in your API:
   ```php
   $config['security'] = [
       'jwt_secret'   => 'your_jwt_secret_key', // JWT secret key
       'token_expiry' => 86400,                 // Token expiry time in seconds (24 hours)
       // ...
   ];
   ```

### 3. Configure Base URLs

1. In the same `includes/config.php` file, update the base URLs:
   ```php
   // Define base paths
   define('BASE_PATH', dirname(__DIR__));
   define('ADMIN_URL', '/admin'); // Update if you installed in a different directory
   define('API_URL', '/api/v1');  // Update to match your API URL
   ```

### 4. Set Up Upload Directory

1. Create a directory called `uploads` in the admin directory.
2. Set the appropriate permissions:
   ```
   chmod 755 uploads
   ```
3. Make sure the web server has write permissions to this directory:
   ```
   chown www-data:www-data uploads
   ```

### 5. Configure .htaccess

1. Make sure the `.htaccess` file is present in the admin directory.
2. If your admin UI is installed in a different directory than `/admin/`, update the RewriteBase in the `.htaccess` file:
   ```
   RewriteBase /your-admin-path/
   ```

### 6. Set Up Database (if not already done)

If you haven't already set up the database for the API, you need to import the database schema:

1. Go to phpMyAdmin in your cPanel.
2. Select your database.
3. Click on the "Import" tab.
4. Upload and import the `database.sql` file from the API directory.

### 7. Create Admin User (if not already done)

If you haven't already created an admin user, you can do so by running the following SQL query:

```sql
INSERT INTO users (name, email, password, role, active, created_at, updated_at)
VALUES ('Admin', 'your-email@example.com', '$2y$12$1234567890123456789012uQFWnzxixQx.8RqMZ8hQXyGkTjrsZBW', 'admin', 1, NOW(), NOW());
```

Replace `'your-email@example.com'` with your email address. The default password is `password`. You should change this after your first login.

### 8. Test the Installation

1. Open your web browser and navigate to your admin UI (e.g., `https://yourdomain.com/admin/`).
2. You should see the login page.
3. Log in with the admin credentials you created.
4. You should be redirected to the dashboard.

## Security Considerations

1. Always use HTTPS for your admin UI.
2. Change the default admin password immediately after installation.
3. Keep your PHP and database software up to date.
4. Consider implementing IP restrictions for the admin area in your `.htaccess` file.
5. Regularly backup your database.

## Troubleshooting

### Login Issues

- Make sure the JWT secret key in the admin UI matches the one in your API.
- Check that the user has the correct role (admin or editor).
- Verify that the user is marked as active in the database.

### File Upload Issues

- Check that the `uploads` directory has the correct permissions.
- Verify that the PHP settings for file uploads are correct in your `.htaccess` file.
- Make sure the file size is within the allowed limit.

### API Connection Issues

- Verify that the API URL is correct in the config file.
- Check that the API is running and accessible.
- Look for CORS issues if the API is on a different domain.

## Support

If you encounter any issues or have questions, please contact the development team.