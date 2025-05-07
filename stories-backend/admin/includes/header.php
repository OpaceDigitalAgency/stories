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
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection error in header.php: " . $e->getMessage());
    $db = null;
}

// Determine if we're in the content directory or main admin directory
$isContentDir = strpos($_SERVER['SCRIPT_FILENAME'], '/admin/content/') !== false;

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
    $assetsPath = strpos($basePath, '/admin/content') !== false ? '../assets/css/enhanced-admin.css' : 'assets/css/enhanced-admin.css';

    // Get favicon from config or use default
    $faviconPath = get_config('site.favicon.png', '/favicon.png');

    // Make sure the favicon path is correct for the admin environment
    if (strpos($faviconPath, '/') === 0) {
        // If it starts with a slash, it's a root-relative path
        $faviconPath = $isContentDir ? '../../public' . $faviconPath : '../public' . $faviconPath;
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
    $modernDashboardCssPath = $isContentDir ? '../assets/css/modern-dashboard.css' : 'assets/css/modern-dashboard.css';
    $thumbnailsCssPath = $isContentDir ? '../assets/css/thumbnails.css' : 'assets/css/thumbnails.css';
    $premiumAdminCssPath = $isContentDir ? '../assets/css/premium-admin.css' : 'assets/css/premium-admin.css';
    ?>
    <link rel="stylesheet" href="<?php echo $modernDashboardCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $thumbnailsCssPath; ?>">
    <link rel="stylesheet" href="<?php echo $premiumAdminCssPath; ?>">

    <!-- JavaScript Libraries -->
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- Add Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

    <!-- Admin JavaScript Files -->
    <?php
    $enhancedAdminJsPath = $isContentDir ? '../assets/js/enhanced-admin.js' : 'assets/js/enhanced-admin.js';
    $liveSearchJsPath = $isContentDir ? '../assets/js/live-search.js' : 'assets/js/live-search.js';
    $inlineEditingJsPath = $isContentDir ? '../assets/js/inline-editing.js' : 'assets/js/inline-editing.js';
    ?>
    <script src="<?php echo $enhancedAdminJsPath; ?>"></script>
    <script src="<?php echo $liveSearchJsPath; ?>"></script>
    <script src="<?php echo $inlineEditingJsPath; ?>"></script>

    <!-- Custom CSS for header navigation -->
    <style>
        /* Header and navigation styles */
        .admin-header {
            padding: 0;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-right: 1.5rem;
        }

        .main-nav {
            flex-grow: 1;
        }

        .main-nav .nav-link {
            padding: 0.5rem 0.75rem;
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

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .main-nav, .user-info {
                width: 100%;
                margin-top: 0.5rem;
            }

            #nav-form {
                flex-wrap: wrap;
            }

            .main-nav .nav-link, .dropdown {
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .user-info {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .user-info .btn {
                width: 100%;
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
        });
    </script>

    <?php if (isset($extraHeadContent)) echo $extraHeadContent; ?>
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to content</a>

    <?php
    // Set paths for navigation
    $dashboardPath = $isContentDir ? '../dashboard.php' : 'dashboard.php';
    $contentPrefix = $isContentDir ? '' : 'content/';
    ?>
    <header class="admin-header">
        <div class="header-container">
            <div class="d-flex align-items-center">
                <!-- Logo (clickable to dashboard) -->
                <a href="<?php echo $dashboardPath; ?>" class="logo-container" style="text-decoration: none;">
                    <div class="logo">S</div>
                    <div class="logo-text">Stories Admin</div>
                </a>

                <!-- Main Navigation Menu -->
                <nav class="main-nav" role="navigation" aria-label="Main Navigation">
                    <form method="GET" id="nav-form" class="d-flex align-items-center">
                        <!-- Content Management Dropdown -->
                        <div class="dropdown mx-2">
                            <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-file-alt" aria-hidden="true"></i> Content
                            </button>
                            <div class="dropdown-menu">
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>stories.php" class="dropdown-item <?php echo $currentPage === 'stories' ? 'active' : ''; ?>">
                                    <i class="fas fa-book" aria-hidden="true"></i> Stories
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>blog-posts.php" class="dropdown-item <?php echo $currentPage === 'blog-posts' ? 'active' : ''; ?>">
                                    <i class="fas fa-newspaper" aria-hidden="true"></i> Blog Posts
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>authors.php" class="dropdown-item <?php echo $currentPage === 'authors' ? 'active' : ''; ?>">
                                    <i class="fas fa-user-edit" aria-hidden="true"></i> Authors
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>tags.php" class="dropdown-item <?php echo $currentPage === 'tags' ? 'active' : ''; ?>">
                                    <i class="fas fa-tags" aria-hidden="true"></i> Tags
                                </button>
                            </div>
                        </div>

                        <!-- Features Dropdown -->
                        <div class="dropdown mx-2">
                            <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-puzzle-piece" aria-hidden="true"></i> Features
                            </button>
                            <div class="dropdown-menu">
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>games.php" class="dropdown-item <?php echo $currentPage === 'games' ? 'active' : ''; ?>">
                                    <i class="fas fa-gamepad" aria-hidden="true"></i> Games
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>directory-items.php" class="dropdown-item <?php echo $currentPage === 'directory' ? 'active' : ''; ?>">
                                    <i class="fas fa-folder" aria-hidden="true"></i> Directory
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>ai-tools.php" class="dropdown-item <?php echo $currentPage === 'ai-tools' ? 'active' : ''; ?>">
                                    <i class="fas fa-robot" aria-hidden="true"></i> AI Tools
                                </button>
                            </div>
                        </div>

                        <!-- Direct Links -->
                        <button type="submit" formaction="<?php echo $dashboardPath; ?>" class="nav-link mx-2 <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt" aria-hidden="true"></i> Dashboard
                        </button>

                        <button type="submit" formaction="<?php echo $contentPrefix; ?>media.php" class="nav-link mx-2 <?php echo $currentPage === 'media' ? 'active' : ''; ?>">
                            <i class="fas fa-images" aria-hidden="true"></i> Media
                        </button>

                        <!-- Users Dropdown -->
                        <div class="dropdown mx-2">
                            <button type="button" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-users" aria-hidden="true"></i> Users
                            </button>
                            <div class="dropdown-menu">
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>subscribers.php" class="dropdown-item <?php echo $currentPage === 'subscribers' ? 'active' : ''; ?>">
                                    <i class="fas fa-bell" aria-hidden="true"></i> Subscribers
                                </button>
                                <button type="submit" formaction="<?php echo $contentPrefix; ?>contacts.php" class="dropdown-item <?php echo $currentPage === 'contacts' ? 'active' : ''; ?>">
                                    <i class="fas fa-envelope" aria-hidden="true"></i> Contacts
                                </button>
                            </div>
                        </div>

                        <!-- Settings -->
                        <button type="submit" formaction="<?php echo $contentPrefix; ?>ai-settings.php" class="nav-link mx-2 <?php echo $currentPage === 'ai-settings' ? 'active' : ''; ?>">
                            <i class="fas fa-cog" aria-hidden="true"></i> Settings
                        </button>
                    </form>
                </nav>
            </div>

            <!-- User Info and Actions -->
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                <a href="<?php echo $isContentDir ? '../clear_session.php' : 'clear_session.php'; ?>" class="btn btn-warning btn-sm" title="Clear session data if you experience login issues">
                    <i class="fas fa-broom"></i> Clear Session
                </a>
                <form method="POST" action="<?php echo $isContentDir ? '../logout.php' : 'logout.php'; ?>" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container" id="main-content">

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <?php if (!empty($pageDescription)): ?>
                    <p class="page-description"><?php echo $pageDescription; ?></p>
                <?php endif; ?>
            </div>
            <?php if (isset($pageActions)): ?>
                <div class="page-actions">
                    <?php echo $pageActions; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($success)): ?>
            <div class="success" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
