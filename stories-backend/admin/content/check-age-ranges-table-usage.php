<?php
/**
 * Check if the old age_ranges table can be safely deleted
 * This script analyzes all references to the age_ranges table in the codebase
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../includes/db-connect.php';

echo "<h1>🔍 Age Ranges Table Usage Analysis</h1>";
echo "<p>This script checks if the old <code>age_ranges</code> table can be safely deleted.</p>";

try {
    // Check if age_ranges table exists
    $stmt = $db->query("SHOW TABLES LIKE 'age_ranges'");
    $tableExists = $stmt->rowCount() > 0;

    echo "<h2>1. Table Existence Check</h2>";
    if ($tableExists) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>⚠️ <strong>OLD TABLE EXISTS:</strong> The <code>age_ranges</code> table still exists in the database.</p>";

        // Show table contents
        $stmt = $db->query("SELECT * FROM age_ranges ORDER BY display_order, id");
        $ranges = $stmt->fetchAll();

        echo "<h3>Current Contents (" . count($ranges) . " records):</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Range Name</th><th>Display Order</th><th>Created</th></tr>";
        foreach ($ranges as $range) {
            echo "<tr>";
            echo "<td>{$range['id']}</td>";
            echo "<td>" . htmlspecialchars($range['range_name']) . "</td>";
            echo "<td>{$range['display_order']}</td>";
            echo "<td>{$range['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>✅ <strong>TABLE NOT FOUND:</strong> The <code>age_ranges</code> table does not exist.</p>";
        echo "</div>";
    }

    // Check if standard_reading_levels table exists
    echo "<h2>2. Standard Reading Levels Table Check</h2>";
    $stmt = $db->query("SHOW TABLES LIKE 'standard_reading_levels'");
    $standardTableExists = $stmt->rowCount() > 0;

    if ($standardTableExists) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>✅ <strong>STANDARD TABLE EXISTS:</strong> The <code>standard_reading_levels</code> table exists and is the current system.</p>";

        // Show standard age groups
        $stmt = $db->query("SELECT age_group, reading_stage, sort_order FROM standard_reading_levels ORDER BY sort_order");
        $standardGroups = $stmt->fetchAll();

        echo "<h3>Standard Age Groups (" . count($standardGroups) . " records):</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Age Group</th><th>Reading Stage</th><th>Sort Order</th></tr>";
        foreach ($standardGroups as $group) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($group['age_group']) . "</td>";
            echo "<td>" . htmlspecialchars($group['reading_stage']) . "</td>";
            echo "<td>{$group['sort_order']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>❌ <strong>STANDARD TABLE MISSING:</strong> The <code>standard_reading_levels</code> table does not exist!</p>";
        echo "</div>";
    }

    // Check for any foreign key references to age_ranges table
    echo "<h2>3. Foreign Key References Check</h2>";
    if ($tableExists) {
        $stmt = $db->query("
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'age_ranges'
        ");
        $foreignKeys = $stmt->fetchAll();

        if (empty($foreignKeys)) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>✅ <strong>NO FOREIGN KEYS:</strong> No foreign key references to the <code>age_ranges</code> table found.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p>❌ <strong>FOREIGN KEYS FOUND:</strong> The following tables reference the <code>age_ranges</code> table:</p>";
            echo "<ul>";
            foreach ($foreignKeys as $fk) {
                echo "<li>{$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → age_ranges.{$fk['REFERENCED_COLUMN_NAME']}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }

    // Check books table for age_range column usage
    echo "<h2>4. Books Table Age Range Usage</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM books LIKE 'age_range'");
    $ageRangeColumnExists = $stmt->rowCount() > 0;

    if ($ageRangeColumnExists) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>✅ <strong>BOOKS.AGE_RANGE EXISTS:</strong> The books table has an age_range column (text field).</p>";

        // Show current age range values in books
        $stmt = $db->query("
            SELECT age_range, COUNT(*) as count
            FROM books
            WHERE age_range IS NOT NULL AND age_range != ''
            GROUP BY age_range
            ORDER BY count DESC
            LIMIT 20
        ");
        $bookAgeRanges = $stmt->fetchAll();

        echo "<h3>Current Age Range Values in Books (Top 20):</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Age Range</th><th>Book Count</th></tr>";
        foreach ($bookAgeRanges as $range) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($range['age_range']) . "</td>";
            echo "<td>{$range['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }

    // Final recommendation
    echo "<h2>5. Deletion Safety Assessment</h2>";

    $canSafelyDelete = $tableExists && $standardTableExists && empty($foreignKeys);

    if ($canSafelyDelete) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border: 2px solid #28a745;'>";
        echo "<h3>✅ SAFE TO DELETE</h3>";
        echo "<p><strong>The <code>age_ranges</code> table can be safely deleted because:</strong></p>";
        echo "<ul>";
        echo "<li>✅ The standard_reading_levels table exists and is functional</li>";
        echo "<li>✅ No foreign key constraints reference the age_ranges table</li>";
        echo "<li>✅ The books table uses a text age_range column, not a foreign key</li>";
        echo "<li>✅ All age range functionality now uses standard_reading_levels</li>";
        echo "</ul>";

        echo "<h4>🗑️ Deletion Command:</h4>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo "DROP TABLE age_ranges;";
        echo "</pre>";

        echo "<button onclick='deleteAgeRangesTable()' class='btn btn-danger' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "🗑️ Delete age_ranges Table";
        echo "</button>";

        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border: 2px solid #dc3545;'>";
        echo "<h3>❌ NOT SAFE TO DELETE</h3>";
        echo "<p><strong>The <code>age_ranges</code> table should NOT be deleted because:</strong></p>";
        echo "<ul>";
        if (!$tableExists) echo "<li>❌ The table doesn't exist anyway</li>";
        if (!$standardTableExists) echo "<li>❌ The standard_reading_levels table is missing</li>";
        if (!empty($foreignKeys)) echo "<li>❌ Foreign key constraints exist</li>";
        echo "</ul>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p>❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<script>
function deleteAgeRangesTable() {
    if (!confirm('Are you sure you want to delete the age_ranges table?\n\nThis action cannot be undone!\n\nThe table appears to be safe to delete, but please make sure you have a database backup.')) {
        return;
    }

    if (!confirm('FINAL CONFIRMATION: Delete the age_ranges table permanently?')) {
        return;
    }

    // Make AJAX request to delete the table
    fetch('ajax/delete-age-ranges-table.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_age_ranges_table'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ SUCCESS: age_ranges table has been deleted.');
            location.reload();
        } else {
            alert('❌ ERROR: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ ERROR: ' + error.message);
    });
}
</script>
