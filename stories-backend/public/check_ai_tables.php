<?php
/**
 * Check AI Tables
 * 
 * This script checks if the AI-related tables exist and have data.
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
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "<p>Database connection successful.</p>";
} catch (PDOException $e) {
    die("<p>Database Error: " . $e->getMessage() . "</p>");
}

try {
    // Check if ai_prompt_templates table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_prompt_templates'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h2>AI Tables Check</h2>";
    
    if ($tableExists) {
        echo "<p>✅ The 'ai_prompt_templates' table exists.</p>";
        
        // Check if the table has data
        $stmt = $db->query("SELECT COUNT(*) FROM ai_prompt_templates");
        $count = $stmt->fetchColumn();
        
        echo "<p>The 'ai_prompt_templates' table has $count records.</p>";
        
        if ($count > 0) {
            // Show a sample of the data
            $stmt = $db->query("SELECT * FROM ai_prompt_templates LIMIT 3");
            $templates = $stmt->fetchAll();
            
            echo "<h3>Sample Templates:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Content Type</th><th>Description</th></tr>";
            
            foreach ($templates as $template) {
                echo "<tr>";
                echo "<td>{$template['id']}</td>";
                echo "<td>{$template['name']}</td>";
                echo "<td>{$template['content_type']}</td>";
                echo "<td>{$template['description']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>⚠️ The 'ai_prompt_templates' table exists but has no data.</p>";
            
            // Check if setup script exists
            $setupScriptPath = '../public/setup_ai_tables.php';
            if (file_exists($setupScriptPath)) {
                echo "<p>The setup script exists at: $setupScriptPath</p>";
                echo "<p><a href='setup_ai_tables.php'>Run Setup Script</a></p>";
            } else {
                echo "<p>❌ The setup script does not exist at: $setupScriptPath</p>";
            }
        }
    } else {
        echo "<p>❌ The 'ai_prompt_templates' table does not exist.</p>";
        
        // Check if setup script exists
        $setupScriptPath = '../public/setup_ai_tables.php';
        if (file_exists($setupScriptPath)) {
            echo "<p>The setup script exists at: $setupScriptPath</p>";
            echo "<p><a href='setup_ai_tables.php'>Run Setup Script</a></p>";
        } else {
            echo "<p>❌ The setup script does not exist at: $setupScriptPath</p>";
        }
    }
    
    // Check if ai_providers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'ai_providers'");
    $providersTableExists = $stmt->rowCount() > 0;
    
    if ($providersTableExists) {
        echo "<p>✅ The 'ai_providers' table exists.</p>";
        
        // Check if the table has data
        $stmt = $db->query("SELECT COUNT(*) FROM ai_providers");
        $count = $stmt->fetchColumn();
        
        echo "<p>The 'ai_providers' table has $count records.</p>";
    } else {
        echo "<p>❌ The 'ai_providers' table does not exist.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error checking tables: " . $e->getMessage() . "</p>";
}
?>
