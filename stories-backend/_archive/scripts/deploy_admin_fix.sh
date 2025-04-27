#!/bin/bash

# Deployment script for JavaScript-free admin interface
# This script ensures complete replacement of the old admin interface

echo "Starting admin interface deployment..."

# 1. Backup existing admin
echo "Creating backup of existing admin..."
BACKUP_DIR="admin_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR
cp -r admin/* $BACKUP_DIR/

# 2. Remove old admin interface completely
echo "Removing old admin interface..."
rm -rf admin/*

# 3. Create new directory structure
echo "Creating new directory structure..."
mkdir -p admin/includes
mkdir -p admin/assets/css
mkdir -p admin/content

# 4. Copy new files
echo "Copying new files..."
cp create_pure_html_admin.php admin/
cp admin/includes/auth.php admin/includes/
cp admin/assets/css/main.css admin/assets/css/
cp admin/content/*.php admin/content/

# 5. Create .htaccess with strict rules
echo "Creating .htaccess with security rules..."
cat > admin/.htaccess << 'EOL'
# Prevent direct access to includes directory
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Block access to old JavaScript files
    RewriteRule \.js$ - [F,L]
    
    # Block access to includes directory
    RewriteRule ^includes/ - [F,L]
    
    # Redirect old admin paths to new interface
    RewriteRule ^index\.php$ /admin/dashboard.php [L,R=301]
    RewriteRule ^dashboard\.php$ /admin/dashboard.php [L,R=301]
</IfModule>

# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent script execution in uploads directory
<Directory "/admin/uploads">
    Options -ExecCGI
    php_flag engine off
    <FilesMatch "\.ph(p[3457]?|t|tml)$">
        deny from all
    </FilesMatch>
</Directory>

# Security headers
<IfModule mod_headers.c>
    Header set Content-Security-Policy "default-src 'self'; script-src 'none'; style-src 'self'"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "same-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Protect sensitive files
<FilesMatch "^(config|functions|auth)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
EOL

# 6. Create config.php
echo "Creating config.php..."
cat > admin/includes/config.php << 'EOL'
<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'stories';
$db_user = 'stories_user';
$db_pass = 'your_password_here';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Site configuration
define('SITE_URL', 'https://api.storiesfromtheweb.org');
define('ADMIN_EMAIL', 'admin@storiesfromtheweb.org');
define('SESSION_LIFETIME', 7200); // 2 hours
EOL

# 7. Set permissions
echo "Setting permissions..."
find admin -type d -exec chmod 755 {} \;
find admin -type f -exec chmod 644 {} \;
chmod 755 admin/create_pure_html_admin.php

echo "Deployment complete!"
echo "Next steps:"
echo "1. Update database credentials in admin/includes/config.php"
echo "2. Test login at /admin/login.php"
echo "3. Verify all JavaScript is blocked"
echo "4. Test all CRUD operations"