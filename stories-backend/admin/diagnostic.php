<?php
/**
 * Database Diagnostic Script
 *
 * This script checks the database schema and connections to help diagnose issues
 * with the admin panel.
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'includes/db-connect.php';

// Function to check if a table exists
function tableExists($db, $tableName) {
    $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
    return $stmt->rowCount() > 0;
}

// Function to check if a column exists in a table
function columnExists($db, $tableName, $columnName) {
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE '$columnName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check table structure
function checkTableStructure($db, $tableName) {
    try {
        $stmt = $db->query("DESCRIBE $tableName");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check table data
function checkTableData($db, $tableName, $limit = 5) {
    try {
        $stmt = $db->query("SELECT * FROM $tableName LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check relationships
function checkRelationship($db, $table1, $table2, $joinColumn1, $joinColumn2) {
    try {
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM $table1 t1
            JOIN $table2 t2 ON t1.$joinColumn1 = t2.$joinColumn2
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        return false;
    }
}

// Start HTML output
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background-color: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Database Diagnostic Report</h1>';

// Check database connection
if ($db) {
    echo '<p class="success">Database connection successful</p>';
} else {
    echo '<p class="error">Database connection failed</p>';
    echo '<p>Error: ' . $connectionError . '</p>';
    exit;
}

// Tables to check
$tables = [
    'stories',
    'authors',
    'story_authors',
    'story_tags',
    'tags',
    'blog_posts',
    'games',
    'directory_items',
    'ai_tools',
    'media'
];

// Check tables
echo '<h2>Table Check</h2>';
echo '<table>';
echo '<tr><th>Table</th><th>Exists</th><th>Row Count</th></tr>';

foreach ($tables as $table) {
    $exists = tableExists($db, $table);
    $rowCount = $exists ? $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() : 'N/A';
    
    echo '<tr>';
    echo '<td>' . $table . '</td>';
    echo '<td>' . ($exists ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . '</td>';
    echo '<td>' . $rowCount . '</td>';
    echo '</tr>';
}

echo '</table>';

// Check stories table structure
if (tableExists($db, 'stories')) {
    echo '<h2>Stories Table Structure</h2>';
    $structure = checkTableStructure($db, 'stories');
    
    if ($structure) {
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($structure as $column) {
            echo '<tr>';
            foreach ($column as $key => $value) {
                echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p class="error">Failed to get stories table structure</p>';
    }
}

// Check authors table structure
if (tableExists($db, 'authors')) {
    echo '<h2>Authors Table Structure</h2>';
    $structure = checkTableStructure($db, 'authors');
    
    if ($structure) {
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($structure as $column) {
            echo '<tr>';
            foreach ($column as $key => $value) {
                echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p class="error">Failed to get authors table structure</p>';
    }
}

// Check story_authors table structure
if (tableExists($db, 'story_authors')) {
    echo '<h2>Story Authors Table Structure</h2>';
    $structure = checkTableStructure($db, 'story_authors');
    
    if ($structure) {
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($structure as $column) {
            echo '<tr>';
            foreach ($column as $key => $value) {
                echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p class="error">Failed to get story_authors table structure</p>';
    }
}

// Check story_authors data
if (tableExists($db, 'story_authors')) {
    echo '<h2>Story Authors Data (First 5 Rows)</h2>';
    $data = checkTableData($db, 'story_authors');
    
    if ($data) {
        if (count($data) > 0) {
            echo '<table>';
            echo '<tr>';
            foreach (array_keys($data[0]) as $key) {
                echo '<th>' . $key . '</th>';
            }
            echo '</tr>';
            
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . ($value === null ? 'NULL' : $value) . '</td>';
                }
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="warning">No data in story_authors table</p>';
        }
    } else {
        echo '<p class="error">Failed to get story_authors data</p>';
    }
}

// Check relationships
echo '<h2>Relationship Check</h2>';
echo '<table>';
echo '<tr><th>Relationship</th><th>Count</th></tr>';

$relationships = [
    ['stories', 'story_authors', 'id', 'story_id'],
    ['authors', 'story_authors', 'id', 'author_id'],
    ['stories', 'story_tags', 'id', 'story_id'],
    ['tags', 'story_tags', 'id', 'tag_id']
];

foreach ($relationships as $rel) {
    list($table1, $table2, $joinColumn1, $joinColumn2) = $rel;
    $count = checkRelationship($db, $table1, $table2, $joinColumn1, $joinColumn2);
    
    echo '<tr>';
    echo '<td>' . $table1 . '.' . $joinColumn1 . ' -> ' . $table2 . '.' . $joinColumn2 . '</td>';
    echo '<td>' . ($count !== false ? $count : '<span class="error">Error</span>') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Check for orphaned stories (stories without authors)
if (tableExists($db, 'stories') && tableExists($db, 'story_authors')) {
    echo '<h2>Orphaned Stories Check</h2>';
    
    try {
        $stmt = $db->query("
            SELECT s.id, s.title 
            FROM stories s 
            LEFT JOIN story_authors sa ON s.id = sa.story_id 
            WHERE sa.story_id IS NULL
        ");
        $orphanedStories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orphanedStories) > 0) {
            echo '<p class="warning">Found ' . count($orphanedStories) . ' stories without authors:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Title</th></tr>';
            
            foreach ($orphanedStories as $story) {
                echo '<tr>';
                echo '<td>' . $story['id'] . '</td>';
                echo '<td>' . $story['title'] . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="success">No orphaned stories found</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">Error checking for orphaned stories: ' . $e->getMessage() . '</p>';
    }
}

// Check for orphaned authors (authors without stories)
if (tableExists($db, 'authors') && tableExists($db, 'story_authors')) {
    echo '<h2>Orphaned Authors Check</h2>';
    
    try {
        $stmt = $db->query("
            SELECT a.id, a.name 
            FROM authors a 
            LEFT JOIN story_authors sa ON a.id = sa.author_id 
            WHERE sa.author_id IS NULL
        ");
        $orphanedAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orphanedAuthors) > 0) {
            echo '<p class="warning">Found ' . count($orphanedAuthors) . ' authors without stories:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th></tr>';
            
            foreach ($orphanedAuthors as $author) {
                echo '<tr>';
                echo '<td>' . $author['id'] . '</td>';
                echo '<td>' . $author['name'] . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="success">No orphaned authors found</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">Error checking for orphaned authors: ' . $e->getMessage() . '</p>';
    }
}

// Check for stories with author_id column (legacy)
if (tableExists($db, 'stories') && columnExists($db, 'stories', 'author_id')) {
    echo '<h2>Legacy Author ID Check</h2>';
    
    try {
        $stmt = $db->query("
            SELECT s.id, s.title, s.author_id, a.name as author_name
            FROM stories s
            LEFT JOIN authors a ON s.author_id = a.id
            WHERE s.author_id IS NOT NULL
        ");
        $legacyStories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($legacyStories) > 0) {
            echo '<p class="warning">Found ' . count($legacyStories) . ' stories with legacy author_id:</p>';
            echo '<table>';
            echo '<tr><th>Story ID</th><th>Title</th><th>Author ID</th><th>Author Name</th></tr>';
            
            foreach ($legacyStories as $story) {
                echo '<tr>';
                echo '<td>' . $story['id'] . '</td>';
                echo '<td>' . $story['title'] . '</td>';
                echo '<td>' . $story['author_id'] . '</td>';
                echo '<td>' . ($story['author_name'] ?? 'Unknown') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="success">No stories with legacy author_id found</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">Error checking for legacy author_id: ' . $e->getMessage() . '</p>';
    }
}

// End HTML output
echo '</body></html>';
