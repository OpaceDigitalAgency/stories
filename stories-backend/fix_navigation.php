<?php
/**
 * Fix Navigation
 * 
 * This script fixes the navigation menus while keeping the form submission fix in place.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Fix Navigation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
        .button { display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Navigation</h1>
', true);
}

output("Fix Navigation");
output("=============");
output("");

// Create a navigation-only JavaScript file
$navJsContent = '/**
 * Navigation-only JavaScript
 * 
 * This script only handles navigation functionality (dropdowns, tabs, etc.)
 * without interfering with form submissions.
 */

// Wait for the DOM to be loaded
document.addEventListener("DOMContentLoaded", function() {
    console.log("[NAV JS] Navigation script loaded");
    
    // Function to toggle dropdown menus
    function setupDropdowns() {
        var dropdownToggles = document.querySelectorAll(".dropdown-toggle");
        
        dropdownToggles.forEach(function(toggle) {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Find the dropdown menu
                var dropdownMenu = this.nextElementSibling;
                if (!dropdownMenu) return;
                
                // Toggle the dropdown menu
                var isExpanded = this.getAttribute("aria-expanded") === "true";
                this.setAttribute("aria-expanded", !isExpanded);
                
                // Toggle the show class
                if (isExpanded) {
                    dropdownMenu.classList.remove("show");
                } else {
                    dropdownMenu.classList.add("show");
                }
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener("click", function(e) {
            var dropdownMenus = document.querySelectorAll(".dropdown-menu.show");
            dropdownMenus.forEach(function(menu) {
                menu.classList.remove("show");
                var toggle = menu.previousElementSibling;
                if (toggle) {
                    toggle.setAttribute("aria-expanded", "false");
                }
            });
        });
    }
    
    // Function to handle tabs
    function setupTabs() {
        var tabLinks = document.querySelectorAll("[data-bs-toggle=\'tab\']");
        
        tabLinks.forEach(function(link) {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Get the target tab
                var target = this.getAttribute("data-bs-target") || this.getAttribute("href");
                if (!target) return;
                
                // Hide all tab panes
                var tabPanes = document.querySelectorAll(".tab-pane");
                tabPanes.forEach(function(pane) {
                    pane.classList.remove("active", "show");
                });
                
                // Show the target tab pane
                var targetPane = document.querySelector(target);
                if (targetPane) {
                    targetPane.classList.add("active", "show");
                }
                
                // Update active state on tab links
                tabLinks.forEach(function(link) {
                    link.classList.remove("active");
                    link.setAttribute("aria-selected", "false");
                });
                
                // Set this tab as active
                this.classList.add("active");
                this.setAttribute("aria-selected", "true");
            });
        });
    }
    
    // Setup navigation components
    setupDropdowns();
    setupTabs();
    
    // Add simple navigation links
    function addSimpleNavigation() {
        // Find the navbar
        var navbar = document.querySelector(".navbar-nav");
        if (!navbar) return;
        
        // Create a simple navigation div
        var simpleNav = document.createElement("div");
        simpleNav.className = "simple-nav";
        simpleNav.style.marginTop = "20px";
        simpleNav.style.padding = "10px";
        simpleNav.style.backgroundColor = "#f8f9fa";
        simpleNav.style.borderRadius = "4px";
        
        // Add a heading
        var heading = document.createElement("h5");
        heading.textContent = "Direct Navigation:";
        heading.style.marginBottom = "10px";
        simpleNav.appendChild(heading);
        
        // Add links to content types
        var contentTypes = [
            { name: "Stories", url: "/admin/stories.php" },
            { name: "Authors", url: "/admin/authors.php" },
            { name: "Tags", url: "/admin/tags.php" },
            { name: "Blog Posts", url: "/admin/blog-posts.php" },
            { name: "Games", url: "/admin/games.php" },
            { name: "Directory Items", url: "/admin/directory-items.php" },
            { name: "AI Tools", url: "/admin/ai-tools.php" }
        ];
        
        // Create a list for the links
        var list = document.createElement("ul");
        list.style.listStyle = "none";
        list.style.padding = "0";
        list.style.margin = "0";
        
        // Add each link
        contentTypes.forEach(function(type) {
            var item = document.createElement("li");
            item.style.marginBottom = "5px";
            
            var link = document.createElement("a");
            link.href = type.url;
            link.textContent = type.name;
            link.style.color = "#007bff";
            link.style.textDecoration = "none";
            link.style.fontWeight = "bold";
            
            item.appendChild(link);
            list.appendChild(item);
        });
        
        simpleNav.appendChild(list);
        
        // Add the simple navigation after the navbar
        navbar.parentNode.insertBefore(simpleNav, navbar.nextSibling);
    }
    
    // Add simple navigation links
    addSimpleNavigation();
});
';

$navJsPath = __DIR__ . '/admin/assets/js/navigation.js';
if (file_put_contents($navJsPath, $navJsContent)) {
    if ($isWeb) output("<div class='success'>Created navigation JavaScript file: $navJsPath</div>", true);
    else output("Created navigation JavaScript file: $navJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create navigation JavaScript file</div>", true);
    else output("Error: Failed to create navigation JavaScript file");
}

// Update the .htaccess file to allow the navigation.js file
$htaccessPath = __DIR__ . '/admin/.htaccess';
if (file_exists($htaccessPath)) {
    // Backup the existing .htaccess file
    $backupFile = $htaccessPath . '.bak.' . date('YmdHis');
    if (!copy($htaccessPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of .htaccess file</div>", true);
        else output("Warning: Failed to create backup of .htaccess file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the current .htaccess file
    $htaccessContent = file_get_contents($htaccessPath);
    
    // Modify the .htaccess file to allow the navigation.js file
    $newHtaccessContent = str_replace(
        '# Disable JavaScript
<FilesMatch "\.js$">
    Header set Content-Type "text/plain"
</FilesMatch>',
        '# Disable JavaScript except for navigation.js
<FilesMatch "\.js$">
    Header set Content-Type "text/plain"
</FilesMatch>

# Allow navigation.js
<Files "navigation.js">
    Header set Content-Type "application/javascript"
</Files>',
        $htaccessContent
    );
    
    if (file_put_contents($htaccessPath, $newHtaccessContent)) {
        if ($isWeb) output("<div class='success'>Updated .htaccess file to allow navigation.js</div>", true);
        else output("Updated .htaccess file to allow navigation.js");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
        else output("Error: Failed to update .htaccess file");
    }
}

// Find the header file
$headerFile = __DIR__ . '/admin/views/header.php';
if (!file_exists($headerFile)) {
    // Try to find the header file
    $possibleHeaderFiles = [
        __DIR__ . '/admin/views/header.php',
        __DIR__ . '/admin/includes/header.php',
        __DIR__ . '/admin/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/views/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/includes/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/header.php'
    ];
    
    foreach ($possibleHeaderFiles as $file) {
        if (file_exists($file)) {
            $headerFile = $file;
            output("Found header file: $headerFile");
            break;
        }
    }
    
    if (!file_exists($headerFile)) {
        if ($isWeb) output("<div class='error'>Header file not found</div>", true);
        else output("Error: Header file not found");
    }
}

if (file_exists($headerFile)) {
    // Backup the header file
    $backupFile = $headerFile . '.bak.' . date('YmdHis');
    if (!copy($headerFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of header file</div>", true);
        else output("Warning: Failed to create backup of header file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the header file
    $headerContent = file_get_contents($headerFile);
    
    // Add the navigation.js script
    $headerContent = str_replace('</head>', '
    <!-- Navigation JavaScript -->
    <script src="/admin/assets/js/navigation.js"></script>
</head>', $headerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($headerFile, $headerContent)) {
        if ($isWeb) output("<div class='success'>Added navigation.js to header file</div>", true);
        else output("Added navigation.js to header file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
        else output("Error: Failed to update header file");
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. The dropdown menus and tabs should now work");
output("3. You should also see a 'Direct Navigation' section with links to all content types");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}