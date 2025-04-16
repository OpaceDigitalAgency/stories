<?php
// Direct access to admin dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Direct Access to Admin Dashboard</h1>";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo "<p>❌ Not logged in. Redirecting to direct login...</p>";
    echo "<script>setTimeout(function() { window.location.href = 'direct_login.php'; }, 2000);</script>";
    exit;
}

echo "<p>✅ Logged in as: " . $_SESSION['user']['email'] . "</p>";
echo "<p>Role: " . $_SESSION['user']['role'] . "</p>";

// Create links to all admin pages
echo "<h2>Admin Pages</h2>";
echo "<ul>";
echo "<li><a href='admin/index.php'>Dashboard</a></li>";
echo "<li><a href='admin/stories.php'>Stories</a></li>";
echo "<li><a href='admin/authors.php'>Authors</a></li>";
echo "<li><a href='admin/blog-posts.php'>Blog Posts</a></li>";
echo "<li><a href='admin/directory-items.php'>Directory Items</a></li>";
echo "<li><a href='admin/games.php'>Games</a></li>";
echo "<li><a href='admin/ai-tools.php'>AI Tools</a></li>";
echo "<li><a href='admin/tags.php'>Tags</a></li>";
echo "<li><a href='admin/media.php'>Media</a></li>";
echo "</ul>";