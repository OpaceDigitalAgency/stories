<?php
/**
 * Check and Update Tables
 * 
 * This script checks if the directory_items and ai_tools tables have all the required columns
 * and adds any missing columns without dropping or recreating the tables.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Check and Update Tables</h1>";

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories_db';
$username = 'stories_user';
$password = '$tw1cac3*sOt'; // Use the actual password from api.php

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p style='color:green'>✅ Database connection successful!</p>";
    
    // Check directory_items table
    echo "<h2>Checking directory_items Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'directory_items'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color:red'>❌ directory_items table does not exist!</p>";
        echo "<p>Please run the database setup script first.</p>";
    } else {
        echo "<p style='color:green'>✅ directory_items table exists.</p>";
        
        // Get current columns
        $stmt = $pdo->query("DESCRIBE directory_items");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Current columns: " . implode(", ", $columns) . "</p>";
        
        // Required columns
        $requiredColumns = [
            'id', 'title', 'description', 'slug', 'website_url', 'category', 
            'rating', 'price_range', 'cover_url', 'is_published', 'created_at', 'updated_at'
        ];
        
        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (count($missingColumns) > 0) {
            echo "<p style='color:orange'>⚠️ Missing columns: " . implode(", ", $missingColumns) . "</p>";
            
            // Add missing columns
            foreach ($missingColumns as $column) {
                $sql = "";
                
                switch ($column) {
                    case 'slug':
                        $sql = "ALTER TABLE directory_items ADD COLUMN slug VARCHAR(255) AFTER description";
                        break;
                    case 'website_url':
                        $sql = "ALTER TABLE directory_items ADD COLUMN website_url VARCHAR(255) AFTER slug";
                        break;
                    case 'category':
                        $sql = "ALTER TABLE directory_items ADD COLUMN category VARCHAR(100) AFTER website_url";
                        break;
                    case 'rating':
                        $sql = "ALTER TABLE directory_items ADD COLUMN rating DECIMAL(3,1) DEFAULT NULL AFTER category";
                        break;
                    case 'price_range':
                        $sql = "ALTER TABLE directory_items ADD COLUMN price_range VARCHAR(50) DEFAULT NULL AFTER rating";
                        break;
                    case 'cover_url':
                        $sql = "ALTER TABLE directory_items ADD COLUMN cover_url VARCHAR(255) DEFAULT NULL AFTER price_range";
                        break;
                    case 'is_published':
                        $sql = "ALTER TABLE directory_items ADD COLUMN is_published TINYINT(1) DEFAULT 1 AFTER cover_url";
                        break;
                    case 'created_at':
                        $sql = "ALTER TABLE directory_items ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_published";
                        break;
                    case 'updated_at':
                        $sql = "ALTER TABLE directory_items ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                        break;
                }
                
                if ($sql) {
                    try {
                        $pdo->exec($sql);
                        echo "<p style='color:green'>✅ Added column: $column</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red'>❌ Error adding column $column: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:green'>✅ All required columns exist in directory_items table.</p>";
        }
    }
    
    // Check ai_tools table
    echo "<h2>Checking ai_tools Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_tools'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color:red'>❌ ai_tools table does not exist!</p>";
        echo "<p>Please run the database setup script first.</p>";
    } else {
        echo "<p style='color:green'>✅ ai_tools table exists.</p>";
        
        // Get current columns
        $stmt = $pdo->query("DESCRIBE ai_tools");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Current columns: " . implode(", ", $columns) . "</p>";
        
        // Required columns
        $requiredColumns = [
            'id', 'title', 'description', 'slug', 'website_url', 'category', 
            'pricing_type', 'price_info', 'features', 'rating', 'featured', 
            'cover_url', 'is_published', 'created_at', 'updated_at'
        ];
        
        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (count($missingColumns) > 0) {
            echo "<p style='color:orange'>⚠️ Missing columns: " . implode(", ", $missingColumns) . "</p>";
            
            // Add missing columns
            foreach ($missingColumns as $column) {
                $sql = "";
                
                switch ($column) {
                    case 'slug':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN slug VARCHAR(255) AFTER description";
                        break;
                    case 'website_url':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN website_url VARCHAR(255) AFTER slug";
                        break;
                    case 'category':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN category VARCHAR(100) AFTER website_url";
                        break;
                    case 'pricing_type':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN pricing_type ENUM('free', 'freemium', 'paid') DEFAULT NULL AFTER category";
                        break;
                    case 'price_info':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN price_info TEXT DEFAULT NULL AFTER pricing_type";
                        break;
                    case 'features':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN features TEXT DEFAULT NULL AFTER price_info";
                        break;
                    case 'rating':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN rating DECIMAL(3,1) DEFAULT NULL AFTER features";
                        break;
                    case 'featured':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER rating";
                        break;
                    case 'cover_url':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN cover_url VARCHAR(255) DEFAULT NULL AFTER featured";
                        break;
                    case 'is_published':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN is_published TINYINT(1) DEFAULT 1 AFTER cover_url";
                        break;
                    case 'created_at':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_published";
                        break;
                    case 'updated_at':
                        $sql = "ALTER TABLE ai_tools ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                        break;
                }
                
                if ($sql) {
                    try {
                        $pdo->exec($sql);
                        echo "<p style='color:green'>✅ Added column: $column</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red'>❌ Error adding column $column: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:green'>✅ All required columns exist in ai_tools table.</p>";
        }
    }
    
    echo "<h2>Table Check Complete</h2>";
    echo "<p>The database tables have been checked and updated as needed.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}