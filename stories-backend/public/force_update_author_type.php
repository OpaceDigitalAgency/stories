<?php
/**
 * Force update author_type field in the admin interface
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
    
    // Force update the admin form
    echo "<h3>Forcing update of admin form:</h3>";
    
    // Check if the authors.php file exists
    $authorsPhpPath = __DIR__ . '/../admin/authors.php';
    if (file_exists($authorsPhpPath)) {
        $authorsPhpContent = file_get_contents($authorsPhpPath);
        
        // Check if the author_type field is already defined
        if (strpos($authorsPhpContent, "'name' => 'author_type'") !== false) {
            echo "author_type field is already defined in authors.php.<br>";
        } else {
            // Add the author_type field
            $authorsPhpContent = str_replace(
                "'default' => null\n            ]",
                "'default' => null\n            ],\n            [\n                'name' => 'author_type',\n                'label' => 'Author Type',\n                'type' => 'select',\n                'list' => true,\n                'form' => true,\n                'view' => true,\n                'default' => 'retail',\n                'options' => [\n                    ['value' => 'retail', 'label' => 'Retail (Book Author)'],\n                    ['value' => 'parent', 'label' => 'Parent'],\n                    ['value' => 'child', 'label' => 'Child'],\n                    ['value' => 'educator', 'label' => 'Educator']\n                ]\n            ]",
                $authorsPhpContent
            );
            
            // Write the updated content back to the file
            file_put_contents($authorsPhpPath, $authorsPhpContent);
            echo "Added author_type field to authors.php.<br>";
        }
    } else {
        echo "authors.php file not found at {$authorsPhpPath}.<br>";
    }
    
    // Check if the story-form.php file exists
    $storyFormPhpPath = __DIR__ . '/../admin/story-form.php';
    if (file_exists($storyFormPhpPath)) {
        $storyFormPhpContent = file_get_contents($storyFormPhpPath);
        
        // Check if the author_type is already being fetched
        if (strpos($storyFormPhpContent, "SELECT id, name, author_type FROM authors") !== false) {
            echo "author_type is already being fetched in story-form.php.<br>";
        } else {
            // Update the query to fetch author_type
            $storyFormPhpContent = str_replace(
                "SELECT id, name FROM authors",
                "SELECT id, name, author_type FROM authors",
                $storyFormPhpContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPhpPath, $storyFormPhpContent);
            echo "Updated story-form.php to fetch author_type.<br>";
        }
        
        // Check if the data-author-type attribute is already being set
        if (strpos($storyFormPhpContent, "data-author-type") !== false) {
            echo "data-author-type attribute is already being set in story-form.php.<br>";
        } else {
            // Add the data-author-type attribute to the author options
            $storyFormPhpContent = str_replace(
                '<option value="<?php echo $author[\'id\']; ?>"',
                '<option value="<?php echo $author[\'id\']; ?>" data-author-type="<?php echo htmlspecialchars($author[\'author_type\'] ?? \'retail\'); ?>"',
                $storyFormPhpContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPhpPath, $storyFormPhpContent);
            echo "Added data-author-type attribute to author options in story-form.php.<br>";
        }
        
        // Check if the updateSourceTypeFromAuthor function is already defined
        if (strpos($storyFormPhpContent, "updateSourceTypeFromAuthor") !== false) {
            echo "updateSourceTypeFromAuthor function is already defined in story-form.php.<br>";
        } else {
            // Add the updateSourceTypeFromAuthor function
            $storyFormPhpContent = str_replace(
                "function updateAllowReviewsVisibility() {",
                "function updateSourceTypeFromAuthor() {\n                        const authorSelect = document.getElementById('author_id');\n                        const sourceTypeSelect = document.getElementById('source_type');\n                        \n                        if (authorSelect.selectedIndex > 0) {\n                            const selectedOption = authorSelect.options[authorSelect.selectedIndex];\n                            const authorType = selectedOption.getAttribute('data-author-type');\n                            \n                            // Map author type to source type\n                            let sourceType;\n                            switch (authorType) {\n                                case 'child':\n                                    sourceType = 'child';\n                                    break;\n                                case 'parent':\n                                    sourceType = 'parent';\n                                    break;\n                                case 'retail':\n                                case 'educator':\n                                default:\n                                    sourceType = 'classic';\n                                    break;\n                            }\n                            \n                            // Set the source type and disable the dropdown\n                            sourceTypeSelect.value = sourceType;\n                            sourceTypeSelect.disabled = true;\n                            \n                            // Update the allow reviews visibility\n                            updateAllowReviewsVisibility();\n                        } else {\n                            // Enable the dropdown if no author is selected\n                            sourceTypeSelect.disabled = false;\n                        }\n                    }\n\n                    function updateAllowReviewsVisibility() {",
                $storyFormPhpContent
            );
            
            // Add the call to updateSourceTypeFromAuthor
            $storyFormPhpContent = str_replace(
                "// Run immediately\n                    updateAllowReviewsVisibility();",
                "// Run immediately\n                    updateAllowReviewsVisibility();\n                    updateSourceTypeFromAuthor();",
                $storyFormPhpContent
            );
            
            // Add the event listener for the author dropdown
            $storyFormPhpContent = str_replace(
                "document.getElementById('source_type').addEventListener('change', updateAllowReviewsVisibility);",
                "document.getElementById('source_type').addEventListener('change', updateAllowReviewsVisibility);\n                    document.getElementById('author_id').addEventListener('change', updateSourceTypeFromAuthor);",
                $storyFormPhpContent
            );
            
            // Write the updated content back to the file
            file_put_contents($storyFormPhpPath, $storyFormPhpContent);
            echo "Added updateSourceTypeFromAuthor function to story-form.php.<br>";
        }
    } else {
        echo "story-form.php file not found at {$storyFormPhpPath}.<br>";
    }
    
    echo "<p>Force update completed successfully!</p>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>