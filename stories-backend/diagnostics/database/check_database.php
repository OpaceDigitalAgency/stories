<?php
/**
 * Check Database
 * 
 * This tool checks database schema and data.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common functions
require_once __DIR__ . '/../includes/common.php';

// Include database connection
if (file_exists(__DIR__ . '/../../includes/db-connect.php')) {
    require_once __DIR__ . '/../../includes/db-connect.php';
} else if (file_exists(__DIR__ . '/../../admin/includes/db-connect.php')) {
    require_once __DIR__ . '/../../admin/includes/db-connect.php';
}

// Check if database connection is available
$dbConnected = isset($db) && $db instanceof PDO;

// Get tables
$tables = [];
if ($dbConnected) {
    try {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

// Define expected tables and their columns
$expectedTables = [
    'stories' => [
        'id', 'title', 'content', 'summary', 'author_id', 'created_at', 'updated_at'
    ],
    'authors' => [
        'id', 'name', 'bio', 'email', 'created_at', 'updated_at'
    ],
    'blog_posts' => [
        'id', 'title', 'content', 'author_id', 'created_at', 'updated_at'
    ],
    'games' => [
        'id', 'title', 'description', 'url', 'created_at', 'updated_at'
    ],
    'directory_items' => [
        'id', 'title', 'description', 'url', 'created_at', 'updated_at'
    ],
    'ai_tools' => [
        'id', 'title', 'description', 'url', 'created_at', 'updated_at'
    ],
    'media' => [
        'id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text', 'created_at', 'updated_at'
    ],
    'subscribers' => [
        'id', 'name', 'email', 'created_at', 'updated_at'
    ],
    'contacts' => [
        'id', 'name', 'email', 'message', 'created_at', 'updated_at'
    ],
    'users' => [
        'id', 'name', 'email', 'password', 'role', 'created_at', 'updated_at'
    ]
];

// Check tables and columns
$tableResults = [];
if ($dbConnected) {
    foreach ($expectedTables as $tableName => $expectedColumns) {
        $tableExists = in_array($tableName, $tables);
        $columns = [];
        $rowCount = 0;
        
        if ($tableExists) {
            try {
                $stmt = $db->query("DESCRIBE `$tableName`");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $stmt = $db->query("SELECT COUNT(*) FROM `$tableName`");
                $rowCount = $stmt->fetchColumn();
            } catch (PDOException $e) {
                // Ignore errors
            }
        }
        
        $missingColumns = array_diff($expectedColumns, $columns);
        
        $tableResults[$tableName] = [
            'exists' => $tableExists,
            'columns' => $columns,
            'missing_columns' => $missingColumns,
            'row_count' => $rowCount
        ];
    }
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Check Database</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Check Database</h1>
        <p class='lead'>This tool checks database schema and data.</p>
        
        <div class='alert alert-" . ($dbConnected ? 'success' : 'danger') . " mb-4'>
            <h4 class='alert-heading'>Database Connection: " . ($dbConnected ? 'Connected' : 'Not Connected') . "</h4>
            <p>" . ($dbConnected ? 'Successfully connected to the database.' : 'Failed to connect to the database.') . "</p>";

if (isset($dbError)) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($dbError) . "</p>";
}

echo "
        </div>";

if ($dbConnected) {
    // Display database information
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h2 class='m-0'>Database Information</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<p><strong>Database Name:</strong> " . htmlspecialchars($db->query("SELECT DATABASE()")->fetchColumn()) . "</p>";
    echo "<p><strong>Tables Found:</strong> " . count($tables) . "</p>";
    
    echo "</div>";
    echo "</div>";
    
    // Display table information
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h2 class='m-0'>Table Information</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered table-striped'>";
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>Table Name</th>";
    echo "<th>Status</th>";
    echo "<th>Row Count</th>";
    echo "<th>Missing Columns</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach ($tableResults as $tableName => $result) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($tableName) . "</td>";
        echo "<td>" . ($result['exists'] ? '<span class="success">Exists</span>' : '<span class="error">Missing</span>') . "</td>";
        echo "<td>" . ($result['exists'] ? $result['row_count'] : 'N/A') . "</td>";
        echo "<td>";
        
        if (!$result['exists']) {
            echo "<span class='error'>Table does not exist</span>";
        } else if (empty($result['missing_columns'])) {
            echo "<span class='success'>All columns present</span>";
        } else {
            echo "<span class='warning'>" . implode(', ', $result['missing_columns']) . "</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Display detailed table information
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h2 class='m-0'>Detailed Table Information</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    foreach ($tableResults as $tableName => $result) {
        if ($result['exists']) {
            echo "<div class='mb-4'>";
            echo "<h3>" . htmlspecialchars($tableName) . "</h3>";
            
            echo "<p><strong>Columns:</strong> " . implode(', ', $result['columns']) . "</p>";
            
            if (!empty($result['missing_columns'])) {
                echo "<p><strong>Missing Columns:</strong> <span class='warning'>" . implode(', ', $result['missing_columns']) . "</span></p>";
            }
            
            echo "<p><strong>Row Count:</strong> " . $result['row_count'] . "</p>";
            
            // Show sample data if available
            if ($result['row_count'] > 0) {
                try {
                    $stmt = $db->query("SELECT * FROM `$tableName` LIMIT 5");
                    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($sampleData)) {
                        echo "<p><strong>Sample Data:</strong></p>";
                        echo "<pre>" . htmlspecialchars(json_encode($sampleData, JSON_PRETTY_PRINT)) . "</pre>";
                    }
                } catch (PDOException $e) {
                    // Ignore errors
                }
            }
            
            echo "</div>";
        }
    }
    
    echo "</div>";
    echo "</div>";
}

// HTML footer
echo "
        <div class='mt-4'>
            <a href='/diagnostic-dashboard.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Back to Diagnostic Dashboard
            </a>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
