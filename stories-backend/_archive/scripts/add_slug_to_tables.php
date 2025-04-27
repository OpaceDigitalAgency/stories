<?php
/**
 * Add slug columns to tables that need them and populate them for existing entries
 */

// Database connection parameters
$host = 'localhost';
$dbname = 'stories_db';
$username = 'stories_user';
$password = '$tw1cac3*sOt';
$port = 3306;

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Tables to check and update
    $tables = [
        'tags' => 'name',
        'blog_posts' => 'title',
        'authors' => 'name',
        'games' => 'title',
        'ai_tools' => 'name',
        'directory_items' => 'name'
    ];
    
    foreach ($tables as $table => $nameColumn) {
        // Check if slug column already exists
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'slug'");
        
        if ($stmt->rowCount() > 0) {
            echo "Slug column already exists in $table table.\n";
        } else {
            // Add slug column to table
            $pdo->exec("ALTER TABLE $table ADD COLUMN slug VARCHAR(255) UNIQUE AFTER $nameColumn");
            echo "Added slug column to $table table.\n";
        }
        
        // Get all records without a slug
        $stmt = $pdo->query("SELECT id, $nameColumn FROM $table WHERE slug IS NULL OR slug = ''");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($records) > 0) {
            echo "Found " . count($records) . " records without a slug in $table table.\n";
            
            // Update each record with a slug based on name/title
            foreach ($records as $record) {
                $name = $record[$nameColumn];
                $id = $record['id'];
                
                // Generate slug from name/title
                $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
                
                // Check if slug already exists
                $stmt = $pdo->prepare("SELECT id FROM $table WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
                
                if ($stmt->rowCount() > 0) {
                    // Append ID to make slug unique
                    $slug .= '-' . $id;
                }
                
                // Update record with new slug
                $stmt = $pdo->prepare("UPDATE $table SET slug = ? WHERE id = ?");
                $stmt->execute([$slug, $id]);
                
                echo "Updated $table '$name' with slug '$slug'.\n";
            }
        } else {
            echo "No records found without a slug in $table table.\n";
        }
    }
    
    echo "Script completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
