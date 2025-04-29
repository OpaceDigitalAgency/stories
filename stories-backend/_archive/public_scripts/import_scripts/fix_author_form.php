<?php
/**
 * Fix author form to include author_type field
 */

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "Connected to database successfully.<br>";
    
    // Check if author_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'author_type'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add author_type column if it doesn't exist
        $db->exec("ALTER TABLE authors ADD COLUMN author_type ENUM('retail', 'parent', 'child', 'educator') DEFAULT 'retail' AFTER bio");
        echo "Added author_type column to authors table.<br>";
    } else {
        echo "author_type column already exists in authors table.<br>";
    }
    
    // Update author types
    $updates = [
        ['id' => 1, 'type' => 'retail'],
        ['id' => 2, 'type' => 'child'],
        ['id' => 3, 'type' => 'parent']
    ];
    
    $updateStmt = $db->prepare("UPDATE authors SET author_type = ? WHERE id = ?");
    
    foreach ($updates as $update) {
        $updateStmt->execute([$update['type'], $update['id']]);
        echo "Updated author ID {$update['id']} to type '{$update['type']}'.<br>";
    }
    
    // Verify the updates
    $authors = $db->query("SELECT id, name, author_type FROM authors")->fetchAll();
    
    echo "<h3>Current Author Types:</h3>";
    echo "<ul>";
    foreach ($authors as $author) {
        echo "<li>ID: {$author['id']}, Name: {$author['name']}, Type: " . 
             ($author['author_type'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
    
    // Fix the author form
    $authorFormPath = __DIR__ . '/../admin/content/author-form.php';
    if (file_exists($authorFormPath)) {
        $authorFormContent = file_get_contents($authorFormPath);
        
        // Check if the author_type field is already in the form
        if (strpos($authorFormContent, 'author_type') !== false) {
            echo "author_type field is already in the form.<br>";
        } else {
            // Add the author_type field after the bio field
            $newField = <<<HTML
                    <div class="form-group">
                        <label class="form-label" for="author_type">Author Type</label>
                        <select id="author_type" name="author_type" class="form-control">
                            <option value="retail" <?php echo (isset(\$author['author_type']) && \$author['author_type'] === 'retail') ? 'selected' : ''; ?>>Retail (Book Author)</option>
                            <option value="parent" <?php echo (isset(\$author['author_type']) && \$author['author_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="child" <?php echo (isset(\$author['author_type']) && \$author['author_type'] === 'child') ? 'selected' : ''; ?>>Child</option>
                            <option value="educator" <?php echo (isset(\$author['author_type']) && \$author['author_type'] === 'educator') ? 'selected' : ''; ?>>Educator</option>
                        </select>
                    </div>
HTML;
            
            // Insert the new field after the bio field
            $authorFormContent = str_replace(
                '</textarea>
                        </div>',
                '</textarea>
                        </div>

                    ' . $newField,
                $authorFormContent
            );
            
            // Write the updated content back to the file
            file_put_contents($authorFormPath, $authorFormContent);
            echo "Added author_type field to the author form.<br>";
        }
    } else {
        echo "Author form not found at $authorFormPath.<br>";
    }
    
    // Fix the save-author.php file
    $saveAuthorPath = __DIR__ . '/../admin/content/save-author.php';
    if (file_exists($saveAuthorPath)) {
        $saveAuthorContent = file_get_contents($saveAuthorPath);
        
        // Check if the author_type field is already being processed
        if (strpos($saveAuthorContent, 'author_type') !== false) {
            echo "author_type field is already being processed in save-author.php.<br>";
        } else {
            // Add author_type to the form data
            $saveAuthorContent = str_replace(
                '$avatar_url = trim($_POST[\'avatar_url\'] ?? \'\');',
                '$avatar_url = trim($_POST[\'avatar_url\'] ?? \'\');
    $author_type = trim($_POST[\'author_type\'] ?? \'retail\');',
                $saveAuthorContent
            );
            
            // Add author_type to the column check
            $saveAuthorContent = str_replace(
                '// Check if avatar_url column exists
    $hasAvatarColumn = in_array(\'avatar_url\', $columns);',
                '// Check if avatar_url column exists
    $hasAvatarColumn = in_array(\'avatar_url\', $columns);
    
    // Check if author_type column exists
    $hasAuthorTypeColumn = in_array(\'author_type\', $columns);',
                $saveAuthorContent
            );
            
            // Add author_type to the update query
            $saveAuthorContent = str_replace(
                'if ($hasAvatarColumn) {
            $setClause[] = "avatar_url = ?";
            $params[] = $avatar_url;
        }',
                'if ($hasAvatarColumn) {
            $setClause[] = "avatar_url = ?";
            $params[] = $avatar_url;
        }
        
        if ($hasAuthorTypeColumn) {
            $setClause[] = "author_type = ?";
            $params[] = $author_type;
        }',
                $saveAuthorContent
            );
            
            // Add author_type to the insert query
            $saveAuthorContent = str_replace(
                'if ($hasAvatarColumn) {
            $columns[] = "avatar_url";
            $placeholders[] = "?";
            $params[] = $avatar_url;
        }',
                'if ($hasAvatarColumn) {
            $columns[] = "avatar_url";
            $placeholders[] = "?";
            $params[] = $avatar_url;
        }
        
        if ($hasAuthorTypeColumn) {
            $columns[] = "author_type";
            $placeholders[] = "?";
            $params[] = $author_type;
        }',
                $saveAuthorContent
            );
            
            // Write the updated content back to the file
            file_put_contents($saveAuthorPath, $saveAuthorContent);
            echo "Updated save-author.php to handle the author_type field.<br>";
        }
    } else {
        echo "save-author.php not found at $saveAuthorPath.<br>";
    }
    
    // Fix the story-form.php file
    $storyFormPath = __DIR__ . '/../admin/content/story-form.php';
    if (file_exists($storyFormPath)) {
        $storyFormContent = file_get_contents($storyFormPath);
        
        // Check if the author_type is already being fetched
        if (strpos($storyFormContent, "SELECT id, name, author_type FROM authors") !== false) {
            echo "author_type is already being fetched in story-form.php.<br>";
        } else {
            // Update the query to fetch author_type
            $storyFormContent = str_replace(
                "SELECT id, name FROM authors",
                "SELECT id, name, author_type FROM authors",
                $storyFormContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPath, $storyFormContent);
            echo "Updated story-form.php to fetch author_type.<br>";
        }
        
        // Check if the data-author-type attribute is already being set
        if (strpos($storyFormContent, "data-author-type") !== false) {
            echo "data-author-type attribute is already being set in story-form.php.<br>";
        } else {
            // Add the data-author-type attribute to the author options
            $storyFormContent = str_replace(
                '<option value="<?php echo $author[\'id\']; ?>"',
                '<option value="<?php echo $author[\'id\']; ?>" data-author-type="<?php echo htmlspecialchars($author[\'author_type\'] ?? \'retail\'); ?>"',
                $storyFormContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPath, $storyFormContent);
            echo "Added data-author-type attribute to author options in story-form.php.<br>";
        }
        
        // Check if the updateSourceTypeFromAuthor function is already defined
        if (strpos($storyFormContent, "updateSourceTypeFromAuthor") !== false) {
            echo "updateSourceTypeFromAuthor function is already defined in story-form.php.<br>";
        } else {
            // Add the updateSourceTypeFromAuthor function
            $storyFormContent = str_replace(
                "function updateAllowReviewsVisibility() {",
                "function updateSourceTypeFromAuthor() {
                        const authorSelect = document.getElementById('author_id');
                        const sourceTypeSelect = document.getElementById('source_type');
                        
                        if (authorSelect.selectedIndex > 0) {
                            const selectedOption = authorSelect.options[authorSelect.selectedIndex];
                            const authorType = selectedOption.getAttribute('data-author-type');
                            
                            // Map author type to source type
                            let sourceType;
                            switch (authorType) {
                                case 'child':
                                    sourceType = 'child';
                                    break;
                                case 'parent':
                                    sourceType = 'parent';
                                    break;
                                case 'retail':
                                case 'educator':
                                default:
                                    sourceType = 'classic';
                                    break;
                            }
                            
                            // Set the source type and disable the dropdown
                            sourceTypeSelect.value = sourceType;
                            sourceTypeSelect.disabled = true;
                            
                            // Update the allow reviews visibility
                            updateAllowReviewsVisibility();
                        } else {
                            // Enable the dropdown if no author is selected
                            sourceTypeSelect.disabled = false;
                        }
                    }

                    function updateAllowReviewsVisibility() {",
                $storyFormContent
            );
            
            // Add the call to updateSourceTypeFromAuthor
            $storyFormContent = str_replace(
                "// Run immediately
                    updateAllowReviewsVisibility();",
                "// Run immediately
                    updateAllowReviewsVisibility();
                    updateSourceTypeFromAuthor();",
                $storyFormContent
            );
            
            // Add the event listener for the author dropdown
            $storyFormContent = str_replace(
                "document.getElementById('source_type').addEventListener('change', updateAllowReviewsVisibility);",
                "document.getElementById('source_type').addEventListener('change', updateAllowReviewsVisibility);
                    document.getElementById('author_id').addEventListener('change', updateSourceTypeFromAuthor);",
                $storyFormContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPath, $storyFormContent);
            echo "Added updateSourceTypeFromAuthor function to story-form.php.<br>";
        }
    } else {
        echo "story-form.php file not found at $storyFormPath.<br>";
    }
    
    echo "<p>Fix completed successfully!</p>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>