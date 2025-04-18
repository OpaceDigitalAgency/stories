<?php
/**
 * Add slug column to games table and populate it for existing entries
 */

// Database connection parameters
$host = 'localhost';
$dbname = 'stories';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Check if slug column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE 'slug'");
    if ($stmt->rowCount() > 0) {
        echo "Slug column already exists in games table.\n";
    } else {
        // Add slug column to games table
        $pdo->exec("ALTER TABLE games ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title");
        echo "Added slug column to games table.\n";
    }
    
    // Get all games without a slug
    $stmt = $pdo->query("SELECT id, title FROM games WHERE slug IS NULL OR slug = ''");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($games) > 0) {
        echo "Found " . count($games) . " games without a slug.\n";
        
        // Update each game with a slug based on title
        foreach ($games as $game) {
            $title = $game['title'];
            $id = $game['id'];
            
            // Generate slug from title
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
            
            // Check if slug already exists
            $stmt = $pdo->prepare("SELECT id FROM games WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            
            if ($stmt->rowCount() > 0) {
                // Append ID to make slug unique
                $slug .= '-' . $id;
            }
            
            // Update game with new slug
            $stmt = $pdo->prepare("UPDATE games SET slug = ? WHERE id = ?");
            $stmt->execute([$slug, $id]);
            
            echo "Updated game '$title' with slug '$slug'.\n";
        }
    } else {
        echo "No games found without a slug.\n";
    }
    
    echo "Script completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
