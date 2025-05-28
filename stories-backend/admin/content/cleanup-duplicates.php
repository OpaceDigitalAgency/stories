<?php
/**
 * Cleanup script for duplicate publishers and tags
 * This script identifies and merges duplicate entries
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set execution time limit for large operations
set_time_limit(300);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicate Cleanup Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .sql-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
        .duplicate-group {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
            margin: 0.5rem 0;
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>🧹 Duplicate Cleanup Tool</h1>
        <p class="text-muted">Identify and clean up duplicate publishers and tags.</p>

        <div class="alert alert-warning">
            <strong>⚠️ Warning:</strong> This tool will modify your database. Make sure you have a backup before proceeding.
        </div>

        <?php
        $action = $_GET['action'] ?? 'analyze';
        
        if ($action === 'analyze') {
            // Analyze duplicates without making changes
            echo '<h2>📊 Analysis Mode</h2>';
            
            // Find duplicate publishers
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>Duplicate Publishers Analysis</h3></div>';
            echo '<div class="card-body">';
            
            try {
                $stmt = $db->query("
                    SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids
                    FROM authors 
                    WHERE type = 'publisher'
                    GROUP BY LOWER(TRIM(name))
                    HAVING count > 1
                    ORDER BY count DESC, name
                ");
                $duplicatePublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($duplicatePublishers)) {
                    echo '<div class="alert alert-success">✅ No exact duplicate publishers found</div>';
                } else {
                    echo '<div class="alert alert-warning">Found ' . count($duplicatePublishers) . ' groups of duplicate publishers:</div>';
                    
                    foreach ($duplicatePublishers as $group) {
                        echo '<div class="duplicate-group">';
                        echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                        echo 'IDs: ' . $group['ids'] . '<br>';
                        
                        // Show which books use these publishers
                        $ids = explode(',', $group['ids']);
                        foreach ($ids as $id) {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE publisher_id = ?");
                            $stmt->execute([trim($id)]);
                            $bookCount = $stmt->fetchColumn();
                            echo "ID $id: $bookCount books<br>";
                        }
                        echo '</div>';
                    }
                }
                
                // Find similar publishers (not exact duplicates)
                echo '<h4>Similar Publishers (85%+ similarity)</h4>';
                $stmt = $db->query("SELECT id, name FROM authors WHERE type = 'publisher' ORDER BY name");
                $allPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $similarGroups = [];
                $processed = [];
                
                foreach ($allPublishers as $pub1) {
                    if (in_array($pub1['id'], $processed)) continue;
                    
                    $group = [$pub1];
                    $processed[] = $pub1['id'];
                    
                    foreach ($allPublishers as $pub2) {
                        if ($pub1['id'] === $pub2['id'] || in_array($pub2['id'], $processed)) continue;
                        
                        $similarity = 0;
                        similar_text(strtolower($pub1['name']), strtolower($pub2['name']), $similarity);
                        
                        // Check for variations
                        $name1 = strtolower(str_replace([' ', "'", '"', '-'], '', $pub1['name']));
                        $name2 = strtolower(str_replace([' ', "'", '"', '-'], '', $pub2['name']));
                        
                        if ($similarity >= 85 || strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
                            $group[] = $pub2;
                            $processed[] = $pub2['id'];
                        }
                    }
                    
                    if (count($group) > 1) {
                        $similarGroups[] = $group;
                    }
                }
                
                if (empty($similarGroups)) {
                    echo '<div class="alert alert-success">✅ No similar publishers found</div>';
                } else {
                    echo '<div class="alert alert-info">Found ' . count($similarGroups) . ' groups of similar publishers:</div>';
                    
                    foreach ($similarGroups as $group) {
                        echo '<div class="duplicate-group">';
                        echo '<strong>Similar Group:</strong><br>';
                        foreach ($group as $pub) {
                            $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE publisher_id = ?");
                            $stmt->execute([$pub['id']]);
                            $bookCount = $stmt->fetchColumn();
                            echo "ID {$pub['id']}: {$pub['name']} ($bookCount books)<br>";
                        }
                        echo '</div>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div></div>';
            
            // Generate SQL for manual cleanup
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📝 SQL Commands for Manual Cleanup</h3></div>';
            echo '<div class="card-body">';
            echo '<p>Copy and paste these SQL commands into phpMyAdmin to clean up duplicates:</p>';
            
            echo '<div class="sql-output">';
            echo "-- SQL commands to clean up duplicate publishers\n";
            echo "-- Run these in phpMyAdmin after reviewing the analysis above\n\n";
            
            if (!empty($duplicatePublishers)) {
                foreach ($duplicatePublishers as $group) {
                    $ids = explode(',', $group['ids']);
                    $keepId = trim($ids[0]); // Keep the first one
                    $removeIds = array_slice($ids, 1);
                    
                    echo "-- Merge duplicates for: " . $group['name'] . "\n";
                    foreach ($removeIds as $removeId) {
                        $removeId = trim($removeId);
                        echo "UPDATE books SET publisher_id = $keepId WHERE publisher_id = $removeId;\n";
                        echo "DELETE FROM authors WHERE id = $removeId;\n";
                    }
                    echo "\n";
                }
            }
            
            if (!empty($similarGroups)) {
                echo "-- Similar publishers (review manually before running)\n";
                foreach ($similarGroups as $group) {
                    echo "-- Similar group:\n";
                    foreach ($group as $pub) {
                        echo "-- ID {$pub['id']}: {$pub['name']}\n";
                    }
                    $keepId = $group[0]['id'];
                    for ($i = 1; $i < count($group); $i++) {
                        $removeId = $group[$i]['id'];
                        echo "-- UPDATE books SET publisher_id = $keepId WHERE publisher_id = $removeId;\n";
                        echo "-- DELETE FROM authors WHERE id = $removeId;\n";
                    }
                    echo "\n";
                }
            }
            echo '</div>';
            
            echo '</div></div>';
            
        } elseif ($action === 'cleanup' && isset($_POST['confirm'])) {
            // Perform actual cleanup
            echo '<h2>🧹 Cleanup Mode</h2>';
            echo '<div class="alert alert-info">Performing cleanup operations...</div>';
            
            // This would contain the actual cleanup logic
            // For safety, we'll just show what would be done
            echo '<div class="alert alert-warning">Cleanup functionality disabled for safety. Use the SQL commands above in phpMyAdmin.</div>';
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h3>🎯 Actions</h3>
            </div>
            <div class="card-body">
                <p><strong>Recommended Process:</strong></p>
                <ol>
                    <li>Review the analysis above</li>
                    <li>Copy the SQL commands</li>
                    <li>Run them in phpMyAdmin</li>
                    <li>Test the enrichment system again</li>
                </ol>
                
                <p class="mt-3">
                    <a href="book-validation.php" class="btn btn-primary">← Back to Book Validation</a>
                    <a href="test-publisher-relationship.php" class="btn btn-secondary">Test Publisher Relationships</a>
                    <button onclick="location.reload()" class="btn btn-info">🔄 Refresh Analysis</button>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
