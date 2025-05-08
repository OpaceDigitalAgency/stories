<?php
/**
 * Authors Admin Page
 *
 * This page displays a list of all authors and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

try {

    // Check if authors table exists
    $stmt = $db->query("SHOW TABLES LIKE 'authors'");
    if ($stmt->rowCount() === 0) {
        // Create authors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            bio TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if stories table has author_id column
    $hasStoriesAuthorId = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
        $hasStoriesAuthorId = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Check if blog_posts table has author_id column
    $hasBlogPostsAuthorId = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM blog_posts LIKE 'author_id'");
        $hasBlogPostsAuthorId = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Check if story_authors junction table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Default story count query
    $storyCountQuery = "0";

    if ($hasStoryAuthorsTable) {
        // Use the junction table if it exists
        $storyCountQuery = "(SELECT COUNT(*) FROM story_authors sa WHERE sa.author_id = a.id)";
    } else if ($hasStoriesAuthorId) {
        // Fall back to direct column if junction table doesn't exist
        $storyCountQuery = "(SELECT COUNT(*) FROM stories WHERE author_id = a.id)";
    }

    $postCountQuery = $hasBlogPostsAuthorId
        ? "(SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id)"
        : "0";

    // Get all authors with content counts
    $query = "SELECT a.*,
              $storyCountQuery as story_count,
              $postCountQuery as post_count
              FROM authors a
              ORDER BY a.name ASC";
    $authors = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Authors page error: " . $e->getMessage());
    $error = "Error loading authors. Please try again.";
    $authors = [];
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Set page variables for header
$pageTitle = 'Authors';
$currentPage = 'authors';
$pageDescription = 'Manage all your authors from here.';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="author-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Author
    </a>
    <button onclick="window.location.reload()" class="premium-btn premium-btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
</div>
';

// Add preview modal CSS and author preview script
$extraHeadContent .= '
<!-- Add Preview Modal CSS -->
<link rel="stylesheet" href="../assets/css/preview-modal.css">
<!-- Add Author Preview JS -->
<script src="../assets/js/author-preview.js"></script>
';

// Include header
require_once '../includes/header.php';

// Include live search component
include_once '../includes/live-search-component.php';
if (function_exists('renderLiveSearchComponent')) {
    renderLiveSearchComponent('authors', ['name', 'email', 'bio'], 'authors-table');
} else {
    // Fallback to regular search component
    include_once '../includes/search-component.php';
    if (function_exists('renderSearchComponent')) {
        renderSearchComponent('authors', ['name', 'email', 'bio']);
    }
}

// Include enhanced table component
include_once '../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($authors as $author) {
        // Get avatar image if available
        $avatarImage = isset($author['avatar_url']) && !empty($author['avatar_url']) ? $author['avatar_url'] :
                     (isset($author['avatar']) && !empty($author['avatar']) ? $author['avatar'] : '../assets/images/default-avatar.svg');

        // Log the avatar URL for debugging
        error_log("Author ID: " . $author['id'] . " - Avatar URL: " . $avatarImage);

        // Format the bio
        $bio = isset($author['bio']) ? substr($author['bio'], 0, 100) . (strlen($author['bio']) > 100 ? '...' : '') : '';

        // Add the item to the table data
        $tableData[] = [
            'id' => $author['id'],
            'image' => $avatarImage,
            'name' => $author['name'],
            'email' => $author['email'] ?? '',
            'type' => ucfirst($author['author_type'] ?? 'retail'),
            'bio' => $bio,
            'stories' => $author['story_count'] ?? 0,
            'posts' => $author['post_count'] ?? 0
        ];
    }

    // Define columns for the table
    $columns = [
        'name' => 'Name',
        'email' => 'Email',
        'type' => 'Type',
        'bio' => 'Bio',
        'stories' => 'Stories',
        'posts' => 'Blog Posts'
    ];

    // Define which fields are editable inline
    $editableFields = ['name', 'email', 'bio'];

    // Render the enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'author', // This must match a key in the $tableMap array in update-field.php
        'authors-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'thumbnailField' => 'image',
            'thumbnailAltField' => 'name',
            'editableFields' => $editableFields,
            'bulkActions' => ['delete'],
            'itemsPerPage' => 10,
            'currentPage' => 1
        ]
    );
} else {
    // Fallback to the original table component
    include_once '../includes/enhanced-table-component.php';
    if (function_exists('renderEnhancedTable')) {
        // Define columns
        $columns = [
            'name' => 'Name',
            'email' => 'Email',
            'author_type' => 'Type',
            'bio' => 'Bio',
            'story_count' => 'Stories',
            'post_count' => 'Blog Posts'
        ];

        // Prepare table data
        $tableData = [];
        foreach ($authors as $author) {
            // Format the bio
            $bio = isset($author['bio']) ? substr($author['bio'], 0, 100) . (strlen($author['bio']) > 100 ? '...' : '') : '';

            // Format the author type
            $authorType = isset($author['author_type']) ? ucfirst($author['author_type']) : 'Retail';

            // Get avatar image
            $avatarImage = isset($author['avatar_url']) && !empty($author['avatar_url']) ? $author['avatar_url'] :
                         (isset($author['avatar']) && !empty($author['avatar']) ? $author['avatar'] : '');

            // Add to table data
            $tableData[] = [
                'id' => $author['id'],
                'name' => $author['name'] ?? '',
                'email' => $author['email'] ?? '',
                'author_type' => $authorType,
                'bio' => $bio,
                'story_count' => $author['story_count'] ?? 0,
                'post_count' => $author['post_count'] ?? 0,
                'avatar' => $avatarImage
            ];
        }

        // Define editable fields
        $editableFields = ['name', 'email'];

        // Render the enhanced table
        renderEnhancedTable(
            $tableData,
            $columns,
            'author', // This must match a key in the $tableMap array in update-field.php
            'authors-table',
            [
                'showCheckboxes' => true,
                'showActions' => true,
                'actions' => ['view', 'edit', 'delete'],
                'thumbnailField' => 'avatar',
                'thumbnailAltField' => 'name',
                'editableFields' => $editableFields,
                'bulkActions' => ['delete', 'notify'],
                'itemsPerPage' => 10,
                'currentPage' => 1
            ]
        );
    } else {
        // Manual fallback if no table component is available
        echo '<div class="table-container">';
        echo '<table id="data-table" class="table">';
        echo '<thead>';
        echo '<tr>';
        foreach ($columns as $label) {
            echo '<th>' . htmlspecialchars($label) . '</th>';
        }
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if ($authors) {
            foreach ($authors as $author) {
                echo '<tr>';
                foreach ($columns as $key => $label) {
                    echo '<td>';
                    if (isset($customFormatters[$key])) {
                        echo $customFormatters[$key]($author, $key);
                    } else {
                        echo isset($author[$key]) ? htmlspecialchars($author[$key]) : '';
                    }
                    echo '</td>';
                }
                echo '<td>';
                echo '<a href="view-author.php?id=' . $author['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a> ';
                echo '<a href="author-form.php?id=' . $author['id'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a> ';
                echo '<a href="author-delete-process.php?id=' . $author['id'] . '" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

// Include author preview script
echo '<link rel="stylesheet" href="../assets/css/preview-modal.css">';
echo '<script src="../assets/js/author-preview.js"></script>';

// Add direct initialization script for author preview
echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Initializing author preview buttons");

        // Initialize preview buttons in the enhanced table
        const authorPreviewButtons = document.querySelectorAll(".author-preview-btn");
        authorPreviewButtons.forEach(button => {
            console.log("Found author preview button:", button);
            button.addEventListener("click", function(e) {
                e.preventDefault();
                console.log("Author preview button clicked");
                const authorId = this.getAttribute("data-author-id");
                console.log("Author ID:", authorId);

                // Create modal container
                const modal = document.createElement("div");
                modal.className = "preview-modal";
                modal.style.display = "flex";
                modal.style.position = "fixed";
                modal.style.top = "0";
                modal.style.left = "0";
                modal.style.width = "100%";
                modal.style.height = "100%";
                modal.style.backgroundColor = "rgba(0, 0, 0, 0.7)";
                modal.style.zIndex = "9999";
                modal.style.justifyContent = "center";
                modal.style.alignItems = "center";

                modal.innerHTML = `
                    <div class="preview-modal-content" style="background-color: white; border-radius: 5px; width: 90%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);">
                        <div class="preview-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ddd;">
                            <h2 style="margin: 0; font-size: 1.5rem;">Author Preview</h2>
                            <button class="preview-modal-close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                        </div>
                        <div class="preview-modal-body" style="padding: 20px; overflow-y: auto; flex-grow: 1;">
                            <div class="preview-loading" style="text-align: center; padding: 20px; font-style: italic; color: #666;">Loading author details...</div>
                            <iframe id="author-preview-frame" style="display:none; width:100%; height:600px; border:none;"></iframe>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Add event listener to close button
                modal.querySelector(".preview-modal-close").addEventListener("click", function() {
                    modal.remove();
                });

                // Load author details
                fetch(`../handlers/get-author.php?id=${authorId}`)
                    .then(response => {
                        console.log("Response status:", response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log("Response data:", data);
                        if (data.success) {
                            const iframe = modal.querySelector("#author-preview-frame");
                            const loading = modal.querySelector(".preview-loading");

                            // Create HTML content for the iframe
                            const html = `
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <meta charset="UTF-8">
                                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                    <title>${data.author.name}</title>
                                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
                                    <style>
                                        body { padding: 20px; font-family: Arial, sans-serif; }
                                        .author-header { display: flex; align-items: center; margin-bottom: 20px; }
                                        .author-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-right: 20px; }
                                        .author-name { margin: 0; }
                                        .author-meta { color: #666; margin-top: 5px; }
                                        .author-bio { line-height: 1.6; }
                                        .author-stories { margin-top: 30px; }
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <div class="author-header">
                                            <img src="${data.author.avatar_url || "../assets/images/default-avatar.svg"}" alt="${data.author.name}" class="author-avatar">
                                            <div>
                                                <h1 class="author-name">${data.author.name}</h1>
                                                <div class="author-meta">
                                                    ${data.author.author_type ? `<div>Type: ${data.author.author_type}</div>` : ""}
                                                    ${data.author.age ? `<div>Age: ${data.author.age}</div>` : ""}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="author-bio">
                                            ${data.author.bio || "<p>No biography available.</p>"}
                                        </div>

                                        <div class="author-stories">
                                            <h3>Stories by this author</h3>
                                            ${data.stories && data.stories.length > 0 ?
                                                `<ul>${data.stories.map(story => `<li>${story.title}</li>`).join("")}</ul>` :
                                                "<p>No stories found.</p>"}
                                        </div>
                                    </div>
                                </body>
                                </html>
                            `;

                            // Set iframe content
                            iframe.onload = function() {
                                loading.style.display = "none";
                                iframe.style.display = "block";
                            };

                            iframe.srcdoc = html;
                        } else {
                            modal.querySelector(".preview-loading").innerHTML = "Error loading author details: " + (data.message || "Unknown error");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        modal.querySelector(".preview-loading").innerHTML = "Error loading author details: " + error.message;
                    });
            });
        });
    });
</script>';

// Include footer
require_once '../includes/footer.php';