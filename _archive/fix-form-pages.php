<?php
/**
 * Script to fix form pages to use the modular header/footer
 */

// List of form pages to fix
$formPages = [
    'stories-backend/admin/content/author-form.php',
    'stories-backend/admin/content/directory-item-form.php',
    'stories-backend/admin/content/game-form.php',
    'stories-backend/admin/content/post-form.php',
    'stories-backend/admin/content/tag-form.php',
    'stories-backend/admin/content/view-ai-tool.php',
    'stories-backend/admin/content/view-directory-item.php',
    'stories-backend/admin/content/view-game.php',
    'stories-backend/admin/content/view-media.php',
    'stories-backend/admin/content/view-post.php',
    'stories-backend/admin/content/view-tag.php'
];

foreach ($formPages as $filePath) {
    echo "Fixing $filePath...\n";
    
    // Read the file
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Error: Could not read $filePath\n";
        continue;
    }
    
    // Fix header includes
    $content = preg_replace(
        '/\/\/ Include header\s+include \'\.\.\/includes\/header\.php\';(\s+)\/\/ Page variables/s',
        '// Page variables',
        $content
    );
    
    // Fix auth and database connection
    $content = preg_replace(
        '/require_once \'\.\.\/\.\.\/simple_auth\.php\';(\s+)\/\/ Database configuration.*?\/\/ Check if user is logged in.*?exit;\s+}/s',
        "// Include auth check\nrequire_once '../includes/auth-check.php';\n\n// Include database connection\nrequire_once '../includes/db-connect.php';\n\n// Include header\nrequire_once '../includes/header.php';",
        $content
    );
    
    // Fix database connection
    $content = preg_replace(
        '/try \{\s+\/\/ Connect to database\s+\$db = new PDO\(\s+"mysql:host=\{\$config\[\'host\'\]\};dbname=\{\$config\[\'name\'\]\};charset=\{\$config\[\'charset\'\]\}",\s+\$config\[\'user\'\],\s+\$config\[\'password\'\],\s+\[\s+PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\s+PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\s+PDO::ATTR_EMULATE_PREPARES => false\s+\]\s+\);/s',
        "try {\n    // Ensure we have a database connection\n    if (!isset(\$db) || !\$db) {\n        // Try to connect to the database directly\n        try {\n            \$db = new PDO(\n                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',\n                'stories_user',\n                '\$tw1cac3*sOt',\n                [\n                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n                ]\n            );\n        } catch (PDOException \$e) {\n            \$errorMessage = \"Database connection error: \" . \$e->getMessage();\n            error_log(\"Database connection error in " . basename($filePath) . ": \" . \$e->getMessage());\n        }\n    }",
        $content
    );
    
    // Fix body and header
    $content = preg_replace(
        '/<body>\s+<header class="admin-header">.*?<div class="page-header d-flex justify-content-between align-items-center mb-4">/s',
        '<div class="content-wrapper">' . "\n" . '    <div class="container-fluid">' . "\n" . '        <div class="page-header d-flex justify-content-between align-items-center mb-4">',
        $content
    );
    
    // Fix footer
    $content = preg_replace(
        '/\s+<\/div>\s+(<style>.*?<\/style>\s+)?(<script>.*?<\/script>\s+)?\/\/ Include footer\s+include \'\.\.\/includes\/footer\.php\';(\s+)?$/s',
        "\n    </div>\n</div>\n\n$1$2<?php require_once '../includes/footer.php'; ?>\n",
        $content
    );
    
    // Write the file
    if (file_put_contents($filePath, $content) === false) {
        echo "Error: Could not write to $filePath\n";
    } else {
        echo "Successfully fixed $filePath\n";
    }
}

echo "All form pages fixed!\n";
