<?php
// Include database connection and other required files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to process a book/story
function processBook($title, $content, $author_name, $author_age = null, $author_location = null, $tags = [], $cover_image = null, $excerpt = '') {
    global $pdo;
    
    try {
        // Start a transaction
        $pdo->beginTransaction();
        
        // Debug output
        echo "Importing: $title<br>";
        echo "TITLE FOR EXTRACTION:<br>";
        echo "\"$title\"<br><br>";
        
        // Extract author information from title if not provided
        if (empty($author_name) && strpos($title, 'by ') !== false) {
            echo "PATTERN 1 MATCHED:<br>";
            echo "Found author in 'by Author' format<br><br>";
            
            $parts = explode('by ', $title);
            if (count($parts) > 1) {
                $author_info = trim($parts[1]);
                
                // Extract age if present
                if (preg_match('/aged (\d+)/', $author_info, $age_matches)) {
                    $author_age = $age_matches[1];
                    $author_info = str_replace($age_matches[0], '', $author_info);
                }
                
                // Extract location if present
                if (preg_match('/from (.+)$/', $author_info, $location_matches)) {
                    $author_location = trim($location_matches[1]);
                    $author_info = str_replace($location_matches[0], '', $author_info);
                }
                
                $author_name = trim($author_info, ", ");
                
                echo "Extracted author: $author_name, age: $author_age, location: $author_location<br><br>";
            }
        }
        
        echo "Author extraction result:<br>";
        echo "Name=\"$author_name\", Age=$author_age, Location=\"$author_location\"<br><br>";
        
        // Process author
        echo "AUTHOR PROCESSING:<br>";
        echo "Starting author lookup/creation<br><br>";
        
        // Create author slug
        $author_slug = createSlug($author_name);
        echo "AUTHOR SLUG:<br>";
        echo "\"$author_slug\"<br><br>";
        
        // Check if author exists
        $stmt = $pdo->prepare("SELECT id FROM authors WHERE slug = ?");
        $stmt->execute([$author_slug]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $author_id = null;
        
        if ($author) {
            $author_id = $author['id'];
            echo "AUTHOR FOUND:<br>";
            echo "Using existing author \"$author_name\" with ID: $author_id<br><br>";
        } else {
            echo "AUTHOR NOT FOUND:<br>";
            echo "Creating new author \"$author_name\"<br><br>";
            
            // Set a default avatar URL if none provided
            $avatar_url = '../uploads/default-avatar.png';
            
            // Insert new author
            $stmt = $pdo->prepare("INSERT INTO authors (name, slug, age, location, avatar_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$author_name, $author_slug, $author_age, $author_location, $avatar_url]);
            $author_id = $pdo->lastInsertId();
            
            echo "AUTHOR CREATED:<br>";
            echo "\"$author_name\" with ID: $author_id<br><br>";
        }
        
        echo "Author ID:<br>";
        echo "$author_id<br><br>";
        
        // Process cover image
        $cover_url = null;
        if ($cover_image) {
            // Check if the image already exists in the media table
            $image_filename = basename($cover_image);
            $stmt = $pdo->prepare("SELECT id, file_path FROM media WHERE file_name = ?");
            $stmt->execute([$image_filename]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($media) {
                $cover_url = $media['file_path'];
                echo "Using existing media record (ID: {$media['id']}) for image: $image_filename<br><br>";
            } else {
                // Insert new media record
                $file_path = '../uploads/' . $image_filename;
                $stmt = $pdo->prepare("INSERT INTO media (file_name, file_path, file_type, upload_date) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$image_filename, $file_path, 'image/png']);
                $media_id = $pdo->lastInsertId();
                $cover_url = $file_path;
                echo "Created new media record (ID: $media_id) for image: $image_filename<br><br>";
            }
        }
        
        // Process excerpt
        if (!empty($excerpt)) {
            echo "Excerpt: $excerpt<br><br>";
        } else {
            // Generate excerpt from content
            $excerpt = substr(strip_tags($content), 0, 150) . '...';
            echo "Generated excerpt: $excerpt<br><br>";
        }
        
        // Process tags
        if (!empty($tags)) {
            echo "Tags: " . implode(', ', $tags) . "<br><br>";
        }
        
        // Create story slug
        $slug = createSlug($title);
        
        // Check if story exists
        $stmt = $pdo->prepare("SELECT id FROM stories WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing_story = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_story) {
            // Update existing story
            // FIXED: Removed allow_reviews from the SQL query
            $stmt = $pdo->prepare("UPDATE stories SET 
                title = ?, 
                content = ?, 
                author_id = ?, 
                excerpt = ?, 
                cover_url = ?, 
                updated_at = NOW() 
                WHERE id = ?");
            $stmt->execute([
                $title, 
                $content, 
                $author_id, 
                $excerpt, 
                $cover_url, 
                $existing_story['id']
            ]);
            
            // Process tags for existing story
            // First remove existing tags
            $stmt = $pdo->prepare("DELETE FROM story_tags WHERE story_id = ?");
            $stmt->execute([$existing_story['id']]);
            
            // Add new tags
            foreach ($tags as $tag_name) {
                $tag_slug = createSlug($tag_name);
                
                // Check if tag exists
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                $stmt->execute([$tag_slug]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tag) {
                    // Create new tag
                    $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$tag_name, $tag_slug]);
                    $tag_id = $pdo->lastInsertId();
                } else {
                    $tag_id = $tag['id'];
                }
                
                // Link tag to story
                $stmt = $pdo->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$existing_story['id'], $tag_id]);
            }
            
            echo "Updated existing story: $title (ID: {$existing_story['id']})<br><br>";
            $pdo->commit();
            return ['status' => 'updated', 'id' => $existing_story['id']];
        } else {
            // Insert new story
            // FIXED: Removed allow_reviews from the SQL query
            $stmt = $pdo->prepare("INSERT INTO stories (
                title, 
                slug, 
                content, 
                author_id, 
                excerpt, 
                cover_url, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $title, 
                $slug, 
                $content, 
                $author_id, 
                $excerpt, 
                $cover_url
            ]);
            $story_id = $pdo->lastInsertId();
            
            // Process tags for new story
            foreach ($tags as $tag_name) {
                $tag_slug = createSlug($tag_name);
                
                // Check if tag exists
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                $stmt->execute([$tag_slug]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$tag) {
                    // Create new tag
                    $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$tag_name, $tag_slug]);
                    $tag_id = $pdo->lastInsertId();
                } else {
                    $tag_id = $tag['id'];
                }
                
                // Link tag to story
                $stmt = $pdo->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$story_id, $tag_id]);
            }
            
            echo "Created new story: $title (ID: $story_id)<br><br>";
            $pdo->commit();
            return ['status' => 'created', 'id' => $story_id];
        }
    } catch (Exception $e) {
        // Roll back the transaction
        $pdo->rollBack();
        echo "Transaction rolled back<br><br>";
        echo "Error processing story: " . $e->getMessage() . "<br><br>";
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Main import logic
$created_count = 0;
$updated_count = 0;
$skipped_count = 0;
$error_count = 0;

// Sample story data for testing
$sample_stories = [
    [
        'title' => 'Vampire by Jane, aged 9, from East Dunbartonshire',
        'content' => '<p>Once upon a time there was a boy called Tom. Tom was walking home from school when he saw something strange in the bushes. It was a vampire!</p><p>The vampire had sharp teeth and a black cape. Tom was very scared but he remembered what his teacher had told him about being brave.</p><p>"Hello," said Tom. "Who are you?"</p><p>The vampire looked surprised. "I am Count Dracula," he said. "And I am very hungry."</p><p>Tom thought quickly. "I have a sandwich in my lunchbox," he said. "Would you like that instead of drinking my blood?"</p><p>The vampire thought for a moment. "What kind of sandwich is it?" he asked.</p><p>"Peanut butter and jelly," said Tom.</p><p>"My favorite!" said the vampire. And he ate the sandwich instead of Tom.</p><p>They became friends and the vampire walked Tom home safely. Tom\'s mum didn\'t believe him when he said he had met a vampire, but Tom knew it was true.</p><p>The End.</p>',
        'tags' => ['children story', 'kids literature'],
        'cover_image' => 'boy-meets-vampire-storybook-illustration.png',
        'excerpt' => 'A young boy finds himself facing a fearsome vampire, read as the story unfolds....'
    ]
    // Add more sample stories as needed
];

// Process each sample story
foreach ($sample_stories as $story) {
    $result = processBook(
        $story['title'],
        $story['content'],
        '', // Author name will be extracted from title
        null, // Author age will be extracted from title
        null, // Author location will be extracted from title
        $story['tags'],
        $story['cover_image'],
        $story['excerpt']
    );
    
    if ($result['status'] === 'created') {
        $created_count++;
    } elseif ($result['status'] === 'updated') {
        $updated_count++;
    } elseif ($result['status'] === 'skipped') {
        $skipped_count++;
    } else {
        $error_count++;
    }
}

// Display import summary
echo "Import Complete!<br>";
echo "Summary:<br><br>";
echo "Created: $created_count stories<br>";
echo "Updated: $updated_count stories<br>";
echo "Skipped: $skipped_count stories<br>";
echo "Errors: $error_count stories<br>";
echo "Check the Stories Admin to verify the imported content.<br>";
?>
