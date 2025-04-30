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

// Default values if not set
$pageTitle = $pageTitle ?? 'Admin';
$currentPage = $currentPage ?? '';
$pageDescription = $pageDescription ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Stories Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/enhanced-admin.css">
    <!-- Add Font Awesome for better icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Meta tags for better accessibility -->
    <meta name="description" content="<?php echo htmlspecialchars($pageTitle); ?> - Stories Admin">
    <meta name="theme-color" content="#4361ee">
    <?php if (isset($extraHeadContent)) echo $extraHeadContent; ?>
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to content</a>
    
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                <form method="POST" action="/admin/logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container" id="main-content">
        <nav class="nav-menu" role="navigation" aria-label="Main Navigation">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="/admin/dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i> Dashboard
                </button>
                <button type="submit" formaction="/admin/content/stories.php" class="nav-link <?php echo $currentPage === 'stories' ? 'active' : ''; ?>">
                    <i class="fas fa-book" aria-hidden="true"></i> Stories
                </button>
                <button type="submit" formaction="/admin/content/blog-posts.php" class="nav-link <?php echo $currentPage === 'blog-posts' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper" aria-hidden="true"></i> Blog Posts
                </button>
                <button type="submit" formaction="/admin/content/authors.php" class="nav-link <?php echo $currentPage === 'authors' ? 'active' : ''; ?>">
                    <i class="fas fa-user-edit" aria-hidden="true"></i> Authors
                </button>
                <button type="submit" formaction="/admin/content/tags.php" class="nav-link <?php echo $currentPage === 'tags' ? 'active' : ''; ?>">
                    <i class="fas fa-tags" aria-hidden="true"></i> Tags
                </button>
                <button type="submit" formaction="/admin/content/games.php" class="nav-link <?php echo $currentPage === 'games' ? 'active' : ''; ?>">
                    <i class="fas fa-gamepad" aria-hidden="true"></i> Games
                </button>
                <button type="submit" formaction="/admin/content/directory-items.php" class="nav-link <?php echo $currentPage === 'directory' ? 'active' : ''; ?>">
                    <i class="fas fa-folder" aria-hidden="true"></i> Directory
                </button>
                <button type="submit" formaction="/admin/content/ai-tools.php" class="nav-link <?php echo $currentPage === 'ai-tools' ? 'active' : ''; ?>">
                    <i class="fas fa-robot" aria-hidden="true"></i> AI Tools
                </button>
                <button type="submit" formaction="/admin/content/media.php" class="nav-link <?php echo $currentPage === 'media' ? 'active' : ''; ?>">
                    <i class="fas fa-images" aria-hidden="true"></i> Media
                </button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <?php if (!empty($pageDescription)): ?>
                    <p class="page-description"><?php echo htmlspecialchars($pageDescription); ?></p>
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
