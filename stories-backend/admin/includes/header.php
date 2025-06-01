<?php
/**
 * Common Header Include
 *
 * This file contains the common header elements for all admin pages.
 * It should be included at the top of each admin page.
 *
 * Usage:
 * $pageTitle = 'Page Title';
 * $currentPage = 'page-id'; // e.g., 'dashboard', 'stories', 'authors', etc.
 * include '../includes/header.php';
 */

// Guard against multiple inclusions
if (defined('HEADER_INCLUDED')) {
    return;
}
define('HEADER_INCLUDED', true);

// Include the configuration
$configPath = dirname(dirname(dirname(__FILE__))) . '/includes/config.php';
if (file_exists($configPath)) {
    include_once $configPath;
}

// Default values if not set
$pageTitle = $pageTitle ?? 'Admin';
$currentPage = $currentPage ?? '';
$pageDescription = $pageDescription ?? '';

// Database connection
try {
    // Use config values if available, otherwise use defaults
    $dbHost = $config['host'] ?? 'localhost';
    $dbName = $config['name'] ?? 'stories_db';
    $dbUser = $config['user'] ?? 'stories_user';
    $dbPass = $config['password'] ?? '$tw1cac3*sOt';
    $dbCharset = $config['charset'] ?? 'utf8mb4';

    $db = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Log successful connection for debugging
    error_log("Database connection successful in header.php");
} catch (PDOException $e) {
    error_log("Database connection error in header.php: " . $e->getMessage());
    $db = null;
}

// Determine if we're in the content directory, public directory, or main admin directory
$isContentDir = strpos($_SERVER['SCRIPT_FILENAME'], '/admin/content/') !== false;
$isPublicDir = strpos($_SERVER['SCRIPT_FILENAME'], '/public/') !== false;

// Get site name from config
$siteName = get_config('site.name', 'Stories From The Web');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($siteName); ?> Admin</title>
    <?php
    // Determine the correct path to assets based on the current file location
    $basePath = dirname($_SERVER['SCRIPT_FILENAME']);

    // Adjust paths based on directory
    if ($isPublicDir) {
        $assetsPath = '../admin/assets/css/enhanced-admin.css';
    } else {
        $assetsPath = strpos($basePath, '/admin/content') !== false ? '../assets/css/enhanced-admin.css' : 'assets/css/enhanced-admin.css';
    }

    // Get favicon from config or use default
    $faviconPath = get_config('site.favicon.png', '/favicon.png');

    // Make sure the favicon path is correct for the admin environment
    if (strpos($faviconPath, '/') === 0) {
        // If it starts with a slash, it's a root-relative path
        if ($isPublicDir) {
            $faviconPath = '../public' . $faviconPath;
        } else {
            $faviconPath = $isContentDir ? '../../public' . $faviconPath : '../public' . $faviconPath;
        }
    }
    ?>
    <link rel="icon" type="image/png" href="<?php echo $faviconPath; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo $faviconPath; ?>">

    <!-- Meta tags for better accessibility -->
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?: $pageTitle . ' - ' . $siteName . ' Admin'); ?>">
    <meta name="theme-color" content="#4361ee">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <!-- CSS Files -->
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Add Font Awesome for better icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Admin CSS Files -->
    <?php
    if ($isPublicDir) {
        $modernDashboardCssPath = '../admin/assets/css/modern-dashboard.css';
        $thumbnailsCssPath = '../admin/assets/css/thumbnails.css';
        $premiumAdminCssPath = '../admin/assets/css/premium-admin.css';
        $adminStylesCssPath = '../admin/assets/css/admin-styles.css';
        $previewModalCssPath = '../admin/assets/css/preview-modal.css';
    } else {
        $modernDashboardCssPath = $isContentDir ? '../assets/css/modern-dashboard.css' : 'assets/css/modern-dashboard.css';
        $thumbnailsCssPath = $isContentDir ? '../assets/css/thumbnails.css' : 'assets/css/thumbnails.css';
        $premiumAdminCssPath = $isContentDir ? '../assets/css/premium-admin.css' : 'assets/css/premium-admin.css';
        $adminStylesCssPath = $isContentDir ? '../assets/css/admin-styles.css' : 'assets/css/admin-styles.css';
        $previewModalCssPath = $isContentDir ? '../assets/css/preview-modal.css' : 'assets/css/preview-modal.css';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $modernDashboardCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $thumbnailsCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $premiumAdminCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $adminStylesCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $previewModalCssPath; ?>">

    <!-- Admin UI Fixes CSS -->
    <?php
    if ($isPublicDir) {
        $adminFixesCssPath = '../admin/assets/css/admin-fixes.css';
    } else {
        $adminFixesCssPath = $isContentDir ? '../assets/css/admin-fixes.css' : 'assets/css/admin-fixes.css';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $adminFixesCssPath; ?>">

    <!-- JavaScript Libraries -->
    <?php
    // Load core scripts only once per session using static variables instead of session
    static $coreScriptsLoaded = false;
    if (!$coreScriptsLoaded) {
        $coreScriptsLoaded = true;
    ?>
    <!-- Core JavaScript Libraries (loaded once per page) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <?php } ?>

    <!-- Admin JavaScript Files -->
    <?php
    static $adminScriptsLoaded = false;
    if (!$adminScriptsLoaded) {
        $adminScriptsLoaded = true;

        if ($isPublicDir) {
            $enhancedAdminJsPath = '../admin/assets/js/enhanced-admin.js';
            $liveSearchJsPath = '../admin/assets/js/live-search.js';
            $inlineEditingJsPath = '../admin/assets/js/inline-editing.js';

            // Preview JS files
            $storyPreviewJsPath = '../admin/assets/js/story-preview.js';
            $authorPreviewJsPath = '../admin/assets/js/author-preview.js';
            $contactPreviewJsPath = '../admin/assets/js/contact-preview.js';
            $gamePreviewJsPath = '../admin/assets/js/game-preview.js';
            $directoryItemPreviewJsPath = '../admin/assets/js/directory-item-preview.js';
            $aiToolPreviewJsPath = '../admin/assets/js/ai-tool-preview.js';
            $postPreviewJsPath = '../admin/assets/js/post-preview.js';
        } else {
            $enhancedAdminJsPath = $isContentDir ? '../assets/js/enhanced-admin.js' : 'assets/js/enhanced-admin.js';
            $liveSearchJsPath = $isContentDir ? '../assets/js/live-search.js' : 'assets/js/live-search.js';
            $inlineEditingJsPath = $isContentDir ? '../assets/js/inline-editing.js' : 'assets/js/inline-editing.js';

            // Preview JS files
            $storyPreviewJsPath = $isContentDir ? '../assets/js/story-preview.js' : 'assets/js/story-preview.js';
            $authorPreviewJsPath = $isContentDir ? '../assets/js/author-preview.js' : 'assets/js/author-preview.js';
            $contactPreviewJsPath = $isContentDir ? '../assets/js/contact-preview.js' : 'assets/js/contact-preview.js';
            $gamePreviewJsPath = $isContentDir ? '../assets/js/game-preview.js' : 'assets/js/game-preview.js';
            $directoryItemPreviewJsPath = $isContentDir ? '../assets/js/directory-item-preview.js' : 'assets/js/directory-item-preview.js';
            $aiToolPreviewJsPath = $isContentDir ? '../assets/js/ai-tool-preview.js' : 'assets/js/ai-tool-preview.js';
            $postPreviewJsPath = $isContentDir ? '../assets/js/post-preview.js' : 'assets/js/post-preview.js';
        }
    ?>
    <script src="<?php echo $enhancedAdminJsPath; ?>"></script>
    <script src="<?php echo $liveSearchJsPath; ?>"></script>
    <script src="<?php echo $inlineEditingJsPath; ?>"></script>

    <?php
        // Tab state handler path
        $tabStateHandlerJsPath = $isContentDir ? '../js/tab-state-handler.js' : 'js/tab-state-handler.js';
    ?>
    <!-- Include tab state handler for pagination -->
    <script src="<?php echo $tabStateHandlerJsPath; ?>"></script>

    <?php
        // Scrape reviews script path
        $scrapeReviewsJsPath = $isContentDir ? '../assets/js/scrape-reviews.js' : 'assets/js/scrape-reviews.js';
    ?>
    <!-- Include scrape reviews handler -->
    <script src="<?php echo $scrapeReviewsJsPath; ?>"></script>
    <?php } ?>

    <!-- Load preview scripts based on current page -->
    <?php if ($currentPage === 'stories'): ?>
    <script src="<?php echo $storyPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'authors'): ?>
    <script src="<?php echo $authorPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'contacts'): ?>
    <script src="<?php echo $contactPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'games'): ?>
    <script src="<?php echo $gamePreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'directory'): ?>
    <script src="<?php echo $directoryItemPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'ai-tools'): ?>
    <script src="<?php echo $aiToolPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <?php if ($currentPage === 'blog-posts'): ?>
    <script src="<?php echo $postPreviewJsPath; ?>"></script>
    <?php endif; ?>

    <!-- Custom CSS for header navigation -->
    <style>
        /* Header and navigation styles */
        .admin-header {
            padding: 0;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 60px; /* Fixed height to match diagnostics page */
        }

        .header-container {
            padding: 0 1rem; /* Remove vertical padding completely */
            max-width: 1200px; /* Match content width */
            margin: 0 auto; /* Center the header content */
            height: 100%; /* Full height of header */
        }

        .main-nav {
            flex-grow: 1;
            align-items: center; /* Ensure vertical centering */
            height: 100%; /* Full height of container */
        }

        .dashboard-link {
            font-weight: 600;
            margin-right: 1.5rem;
            padding: 0.3rem 0.6rem; /* Further reduced padding */
            border-radius: var(--radius-md);
            height: 36px; /* Fixed height for consistency */
            display: flex;
            align-items: center;
        }

        .nav-items {
            flex-grow: 1;
            display: flex;
            align-items: center; /* Ensure vertical centering */
            height: 100%; /* Full height of container */
        }

        .main-nav .nav-link {
            padding: 0.3rem 0.6rem; /* Further reduced padding */
            border-radius: var(--radius-md);
            color: var(--gray-700);
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            height: 36px; /* Fixed height for consistency */
        }

        .main-nav .nav-link:hover {
            background-color: var(--gray-100);
            color: var(--primary);
        }

        .main-nav .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .dropdown-menu {
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            padding: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-item:hover {
            background-color: var(--gray-100);
            color: var(--primary);
        }

        .dropdown-item.active {
            background-color: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center; /* Ensure vertical centering */
            gap: 0.5rem; /* Reduced gap */
            height: 100%; /* Full height of container */
        }

        /* Ensure buttons are vertically centered */
        .user-info .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.5rem; /* Smaller padding */
            height: 36px; /* Fixed height for consistency */
            margin: 0; /* Remove any margin */
        }

        /* Fix for the welcome text */
        .user-name {
            margin-right: 0.5rem;
            white-space: nowrap;
            align-self: center; /* Ensure vertical centering */
        }

        /* Fix for form inside user-info */
        .user-info form {
            margin: 0;
            height: 36px; /* Fixed height for consistency */
            display: flex; /* Ensure proper alignment */
            align-items: center; /* Vertical centering */
        }

        /* Specific fix for logout button */
        .user-info .btn-danger {
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 0;
            padding-bottom: 0;
            line-height: 1;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .main-nav {
                flex-wrap: wrap;
            }

            .nav-items {
                order: 3;
                width: 100%;
                margin-top: 0.5rem;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .user-info {
                order: 2;
                margin-left: auto;
            }
        }

        @media (max-width: 768px) {
            .user-info {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.5rem;
            }

            .user-info .btn {
                width: 100%;
            }

            .nav-items {
                justify-content: flex-start;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 0.5rem;
            }
        }
    </style>

    <!-- Fix for dropdown issues -->
    <script>
        $(document).ready(function() {
            // Ensure all dropdowns are properly initialized
            $('.dropdown-toggle').dropdown();

            // Fix for Bootstrap select elements
            $('select.form-control').each(function() {
                $(this).addClass('custom-select');
            });

            // Prevent errors from non-existent selectors
            $.fn.oldSelect = $.fn.select;
            $.fn.select = function() {
                if (this.length === 0) {
                    console.warn('Attempted to select an element that does not exist');
                    return this;
                }
                return $.fn.oldSelect.apply(this, arguments);
            };

            // Fix for modals not showing
            $(document).on('click', '[data-toggle="modal"]', function() {
                var target = $(this).data('target');
                $(target).modal('show');
            });

            // Fix for dollar sign appearing in text and adjust dropdown widths
            function fixDropdownIssues() {
                // Fix for "Bulk Actions" button
                $('button:contains("Bulk Action")').each(function() {
                    var text = $(this).text();
                    if (text.includes('$')) {
                        $(this).text(text.replace('$', ''));
                    }
                    $(this).css({
                        'min-width': '160px',
                        'padding-right': '30px',
                        'text-align': 'left'
                    });
                });

                // Fix for "All Fields" button
                $('button:contains("All Field")').each(function() {
                    var text = $(this).text();
                    if (text.includes('$')) {
                        $(this).text(text.replace('$', ''));
                    }
                    $(this).css({
                        'min-width': '120px',
                        'padding-right': '30px',
                        'text-align': 'left'
                    });
                });

                // Fix for any dropdown buttons
                $('.dropdown-toggle').each(function() {
                    $(this).css({
                        'min-width': '120px',
                        'padding-right': '30px',
                        'text-align': 'left'
                    });
                });

                // Fix for any select elements
                $('select, .custom-select').each(function() {
                    $(this).css({
                        'min-width': '120px',
                        'padding-right': '30px'
                    });
                });

                // Fix for any other buttons with dollar sign
                $('button, select, .dropdown-toggle').each(function() {
                    var text = $(this).text();
                    if (text.includes('$')) {
                        $(this).text(text.replace('$', ''));
                    }
                });
            }

            // Run the fix on page load
            fixDropdownIssues();

            // Run the fix again after a short delay to catch dynamically loaded content
            setTimeout(fixDropdownIssues, 500);
        });
    </script>

    <?php if (isset($extraHeadContent)) echo $extraHeadContent; ?>
</head>
<body>
    <?php
    // Set paths for navigation
    if ($isPublicDir) {
        // For files in the public directory
        $dashboardPath = '../admin/dashboard.php';
        $contentPrefix = '../admin/content/';
    } else if (strpos($_SERVER['SCRIPT_FILENAME'], 'diagnostic-dashboard.php') !== false) {
        // For the diagnostic dashboard page
        $dashboardPath = '/admin/dashboard.php';
        $contentPrefix = '/admin/content/';
    } else {
        // For regular admin pages
        $dashboardPath = $isContentDir ? '../dashboard.php' : 'dashboard.php';
        $contentPrefix = $isContentDir ? '' : 'content/';
    }
    ?>
    <header class="admin-header">
        <div class="header-container">
            <!-- Single-line navigation bar -->
            <nav class="main-nav d-flex align-items-center w-100" role="navigation" aria-label="Main Navigation">
                <!-- Dashboard link (replaces logo) -->
                <a href="<?php echo $dashboardPath; ?>" class="nav-link dashboard-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i> Dashboard
                </a>

                <!-- Main navigation items -->
                <div class="nav-items d-flex">
                    <!-- Content Management Dropdown -->
                    <div class="dropdown mx-1">
                        <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-alt" aria-hidden="true"></i> Content
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo $isContentDir ? 'stories.php' : $contentPrefix . 'stories.php'; ?>" class="dropdown-item <?php echo $currentPage === 'stories' ? 'active' : ''; ?>">
                                <i class="fas fa-book" aria-hidden="true"></i> Stories
                            </a>
                            <a href="<?php echo $isContentDir ? 'blog-posts.php' : $contentPrefix . 'blog-posts.php'; ?>" class="dropdown-item <?php echo $currentPage === 'blog-posts' ? 'active' : ''; ?>">
                                <i class="fas fa-newspaper" aria-hidden="true"></i> Blog Posts
                            </a>
                            <a href="<?php echo $isContentDir ? 'authors.php' : $contentPrefix . 'authors.php'; ?>" class="dropdown-item <?php echo $currentPage === 'authors' ? 'active' : ''; ?>">
                                <i class="fas fa-user-edit" aria-hidden="true"></i> Authors
                            </a>
                            <a href="<?php echo $isContentDir ? 'tags.php' : $contentPrefix . 'tags.php'; ?>" class="dropdown-item <?php echo $currentPage === 'tags' ? 'active' : ''; ?>">
                                <i class="fas fa-tags" aria-hidden="true"></i> Tags
                            </a>
                        </div>
                    </div>

                    <!-- Features Dropdown -->
                    <div class="dropdown mx-1">
                        <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-puzzle-piece" aria-hidden="true"></i> Features
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo $isContentDir ? 'games.php' : $contentPrefix . 'games.php'; ?>" class="dropdown-item <?php echo $currentPage === 'games' ? 'active' : ''; ?>">
                                <i class="fas fa-gamepad" aria-hidden="true"></i> Games
                            </a>
                            <a href="<?php echo $isContentDir ? 'directory-items.php' : $contentPrefix . 'directory-items.php'; ?>" class="dropdown-item <?php echo $currentPage === 'directory' ? 'active' : ''; ?>">
                                <i class="fas fa-folder" aria-hidden="true"></i> Directory
                            </a>
                            <a href="<?php echo $isContentDir ? 'ai-tools.php' : $contentPrefix . 'ai-tools.php'; ?>" class="dropdown-item <?php echo $currentPage === 'ai-tools' ? 'active' : ''; ?>">
                                <i class="fas fa-robot" aria-hidden="true"></i> AI Tools
                            </a>
                        </div>
                    </div>

                    <a href="<?php echo $isContentDir ? 'media.php' : $contentPrefix . 'media.php'; ?>" class="nav-link mx-1 <?php echo $currentPage === 'media' ? 'active' : ''; ?>">
                        <i class="fas fa-images" aria-hidden="true"></i> Media
                    </a>

                    <!-- Users Dropdown -->
                    <div class="dropdown mx-1">
                        <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users" aria-hidden="true"></i> Users
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo $isContentDir ? 'subscribers.php' : $contentPrefix . 'subscribers.php'; ?>" class="dropdown-item <?php echo $currentPage === 'subscribers' ? 'active' : ''; ?>">
                                <i class="fas fa-bell" aria-hidden="true"></i> Subscribers
                            </a>
                            <a href="<?php echo $isContentDir ? 'contacts.php' : $contentPrefix . 'contacts.php'; ?>" class="dropdown-item <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>">
                                <i class="fas fa-envelope" aria-hidden="true"></i> Contacts
                            </a>
                        </div>
                    </div>

                    <!-- Debug Dropdown -->
                    <div class="dropdown mx-1">
                        <button type="button" class="nav-link dropdown-toggle <?php echo in_array($currentPage, ['debug-logs', 'check-fetchers', 'book-check-compare']) ? 'active' : ''; ?>" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bug" aria-hidden="true"></i> Debug
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo $isContentDir ? 'debug-logs.php' : $contentPrefix . 'debug-logs.php'; ?>" class="dropdown-item <?php echo $currentPage === 'debug-logs' ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt" aria-hidden="true"></i> View All Debug Logs
                            </a>

                            <a href="<?php echo $isContentDir ? 'check-fetchers.php' : $contentPrefix . 'check-fetchers.php'; ?>" class="dropdown-item <?php echo $currentPage === 'check-fetchers' ? 'active' : ''; ?>">
                                <i class="fas fa-check-circle" aria-hidden="true"></i> Check Fetchers
                            </a>

                            <a href="<?php echo $isContentDir ? 'book-check-compare.php' : $contentPrefix . 'book-check-compare.php'; ?>" class="dropdown-item <?php echo $currentPage === 'book-check-compare' ? 'active' : ''; ?>">
                                <i class="fas fa-search" aria-hidden="true"></i> Book Check & Compare
                            </a>
                        </div>
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="dropdown mx-1">
                        <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog" aria-hidden="true"></i> Settings
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo $isContentDir ? 'ai-settings.php' : $contentPrefix . 'ai-settings.php'; ?>" class="dropdown-item <?php echo $currentPage === 'ai-settings' ? 'active' : ''; ?>">
                                <i class="fas fa-robot" aria-hidden="true"></i> AI Settings
                            </a>
                            <a href="<?php echo $isContentDir ? 'book-import-tool.php' : $contentPrefix . 'book-import-tool.php'; ?>" class="dropdown-item <?php echo $currentPage === 'book-import-tool' ? 'active' : ''; ?>">
                                <i class="fas fa-book-reader" aria-hidden="true"></i> Book Import Tool
                            </a>
                            <a href="<?php echo $isContentDir ? 'comprehensive-cleanup.php?stage=update' : $contentPrefix . 'comprehensive-cleanup.php?stage=update'; ?>" class="dropdown-item <?php echo $currentPage === 'comprehensive-cleanup' ? 'active' : ''; ?>">
                                <i class="fas fa-broom" aria-hidden="true"></i> Duplicate Cleanup
                            </a>

                            <?php if ($isPublicDir): ?>
                            <a href="../public/direct_import.php" class="dropdown-item <?php echo $currentPage === 'import' ? 'active' : ''; ?>">
                                <i class="fas fa-file-import" aria-hidden="true"></i> Imports
                            </a>
                            <a href="../diagnostic-dashboard.php" class="dropdown-item">
                                <i class="fas fa-chart-line" aria-hidden="true"></i> Diagnostics
                            </a>
                            <a href="../public/optimize_image.php" class="dropdown-item">
                                <i class="fas fa-image" aria-hidden="true"></i> Image Optimization
                            </a>
                            <?php else: ?>
                            <a href="/public/direct_import.php" class="dropdown-item <?php echo $currentPage === 'import' ? 'active' : ''; ?>">
                                <i class="fas fa-file-import" aria-hidden="true"></i> Imports
                            </a>
                            <a href="/diagnostic-dashboard.php" class="dropdown-item">
                                <i class="fas fa-chart-line" aria-hidden="true"></i> Diagnostics
                            </a>
                            <a href="/public/optimize_image.php" class="dropdown-item">
                                <i class="fas fa-image" aria-hidden="true"></i> Image Optimization
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Info and Actions (pushed to the right) -->
                <div class="user-info ml-auto">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                    <?php if ($isPublicDir): ?>
                    <a href="../admin/clear_session.php" class="btn btn-warning btn-sm" title="Clear session data if you experience login issues">
                        <i class="fas fa-broom"></i> Clear Session
                    </a>
                    <form method="POST" action="../admin/logout.php" style="display: inline;">
                        <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                    </form>
                    <?php else: ?>
                    <a href="<?php echo $isContentDir ? '../clear_session.php' : 'clear_session.php'; ?>" class="btn btn-warning btn-sm" title="Clear session data if you experience login issues">
                        <i class="fas fa-broom"></i> Clear Session
                    </a>
                    <form method="POST" action="<?php echo $isContentDir ? '../logout.php' : 'logout.php'; ?>" style="display: inline;">
                        <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                    </form>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <div class="container" id="main-content">

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <?php if (isset($pageActions)) echo $pageActions; ?>
        </div>
