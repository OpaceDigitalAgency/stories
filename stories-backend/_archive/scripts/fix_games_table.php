<?php
/**
 * Script to fix issues with the games table
 * This script will:
 * 1. Check for case sensitivity issues in the table and column names
 * 2. Ensure the table has the correct structure
 * 3. Fix any issues found
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Games Table Fix</h1>";

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories';
$username = 'stories_user';
$password = 'stories_password';

echo "<h2>Database Connection</h2>";
echo "<p>Attempting to connect to database: $dbname on $host</p>";

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
    
    // Check if games table exists
    echo "<h2>Games Table Check</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green'>✅ Games table exists</p>";
        
        // Get table structure
        echo "<h2>Current Table Structure</h2>";
        $stmt = $pdo->query("DESCRIBE games");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check for required columns
        $requiredColumns = [
            'id' => 'int',
            'title' => 'varchar',
            'description' => 'text',
            'slug' => 'varchar',
            'featured' => 'tinyint',
            'is_published' => 'tinyint',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];
        
        $missingColumns = [];
        $existingColumns = [];
        
        foreach ($columns as $column) {
            $existingColumns[$column['Field']] = $column['Type'];
        }
        
        foreach ($requiredColumns as $column => $type) {
            if (!isset($existingColumns[$column])) {
                $missingColumns[$column] = $type;
            }
        }
        
        if (count($missingColumns) > 0) {
            echo "<h2>Missing Columns</h2>";
            echo "<p style='color:orange'>⚠️ The following required columns are missing:</p>";
            echo "<ul>";
            foreach ($missingColumns as $column => $type) {
                echo "<li>$column ($type)</li>";
            }
            echo "</ul>";
            
            echo "<h2>Adding Missing Columns</h2>";
            
            foreach ($missingColumns as $column => $type) {
                $sql = "";
                
                switch ($column) {
                    case 'id':
                        $sql = "ALTER TABLE games ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
                        break;
                    case 'title':
                        $sql = "ALTER TABLE games ADD COLUMN title VARCHAR(255) NOT NULL";
                        break;
                    case 'description':
                        $sql = "ALTER TABLE games ADD COLUMN description TEXT";
                        break;
                    case 'slug':
                        $sql = "ALTER TABLE games ADD COLUMN slug VARCHAR(255) NOT NULL";
                        break;
                    case 'featured':
                        $sql = "ALTER TABLE games ADD COLUMN featured TINYINT(1) DEFAULT 0";
                        break;
                    case 'is_published':
                        $sql = "ALTER TABLE games ADD COLUMN is_published TINYINT(1) DEFAULT 0";
                        break;
                    case 'published_at':
                        $sql = "ALTER TABLE games ADD COLUMN published_at DATETIME";
                        break;
                    case 'created_at':
                        $sql = "ALTER TABLE games ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
                        break;
                    case 'updated_at':
                        $sql = "ALTER TABLE games ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
                        break;
                }
                
                if (!empty($sql)) {
                    try {
                        $pdo->exec($sql);
                        echo "<p style='color:green'>✅ Added column: $column</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red'>❌ Failed to add column $column: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:green'>✅ All required columns exist</p>";
        }
        
        // Check for case sensitivity issues in column names
        $caseIssues = [];
        foreach ($columns as $column) {
            $field = $column['Field'];
            if ($field !== strtolower($field) && $field !== 'ID') {
                $caseIssues[] = $field;
            }
        }
        
        if (count($caseIssues) > 0) {
            echo "<h2>Case Sensitivity Issues</h2>";
            echo "<p style='color:orange'>⚠️ Found columns with case sensitivity issues:</p>";
            echo "<ul>";
            foreach ($caseIssues as $field) {
                echo "<li>$field</li>";
            }
            echo "</ul>";
            
            echo "<h2>Fixing Case Sensitivity Issues</h2>";
            
            foreach ($caseIssues as $field) {
                $lowerField = strtolower($field);
                
                // Get column definition
                $stmt = $pdo->query("SHOW COLUMNS FROM games LIKE '$field'");
                $columnDef = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($columnDef) {
                    $type = $columnDef['Type'];
                    $null = $columnDef['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                    $default = $columnDef['Default'] ? "DEFAULT '" . $columnDef['Default'] . "'" : '';
                    $extra = $columnDef['Extra'];
                    
                    $sql = "ALTER TABLE games CHANGE `$field` `$lowerField` $type $null $default $extra";
                    
                    try {
                        $pdo->exec($sql);
                        echo "<p style='color:green'>✅ Renamed column: $field to $lowerField</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red'>❌ Failed to rename column $field: " . $e->getMessage() . "</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:green'>✅ No case sensitivity issues found in column names</p>";
        }
        
        // Add sample data if the table is empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM games");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "<h2>Adding Sample Data</h2>";
            echo "<p>The games table is empty. Adding sample data...</p>";
            
            $sampleGames = [
                [
                    'title' => 'Adventure Quest',
                    'description' => 'An exciting adventure game for all ages',
                    'slug' => 'adventure-quest',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Puzzle Master',
                    'description' => 'Test your brain with challenging puzzles',
                    'slug' => 'puzzle-master',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Space Explorer',
                    'description' => 'Explore the vastness of space in this sci-fi adventure',
                    'slug' => 'space-explorer',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO games (title, description, slug, featured, is_published, published_at) VALUES (:title, :description, :slug, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleGames as $game) {
                try {
                    $stmt->execute($game);
                    echo "<p style='color:green'>✅ Added game: {$game['title']}</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:red'>❌ Failed to add game {$game['title']}: " . $e->getMessage() . "</p>";
                }
            }
        } else {
            echo "<p>The games table contains $count records. No sample data needed.</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ Games table does not exist!</p>";
        
        echo "<h2>Creating Games Table</h2>";
        
        $sql = "CREATE TABLE games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✅ Games table created successfully!</p>";
            
            // Add sample data
            echo "<h2>Adding Sample Data</h2>";
            
            $sampleGames = [
                [
                    'title' => 'Adventure Quest',
                    'description' => 'An exciting adventure game for all ages',
                    'slug' => 'adventure-quest',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Puzzle Master',
                    'description' => 'Test your brain with challenging puzzles',
                    'slug' => 'puzzle-master',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Space Explorer',
                    'description' => 'Explore the vastness of space in this sci-fi adventure',
                    'slug' => 'space-explorer',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO games (title, description, slug, featured, is_published, published_at) VALUES (:title, :description, :slug, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleGames as $game) {
                try {
                    $stmt->execute($game);
                    echo "<p style='color:green'>✅ Added game: {$game['title']}</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:red'>❌ Failed to add game {$game['title']}: " . $e->getMessage() . "</p>";
                }
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Failed to create games table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Final check
    echo "<h2>Final Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE games");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check data
    $stmt = $pdo->query("SELECT * FROM games LIMIT 5");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Data</h2>";
    if (count($games) > 0) {
        echo "<pre>" . json_encode($games, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>No games found in the table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Check that the API endpoint is correctly configured to use the games table.</li>";
echo "<li>Verify that the Response class is correctly formatting the data.</li>";
echo "<li>Ensure that the routes are pointing to the correct controller.</li>";
echo "</ol>";

echo "<p>You can test the games table directly using the <a href='test_games_table.php'>test_games_table.php</a> script.</p>";