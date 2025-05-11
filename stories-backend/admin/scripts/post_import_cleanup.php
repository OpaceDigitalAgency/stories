<?php
/**
 * Post-Import Cleanup Script
 * 
 * This script performs a comprehensive cleanup after importing reviews:
 * 1. Standardizes reviewer names by removing asterisks and extra formatting
 * 2. Removes duplicate reviews
 * 3. Updates book ratings
 * 
 * Run this script after importing reviews to ensure data consistency.
 */

// Set execution time limit to 5 minutes
set_time_limit(300);

// Include database connection
require_once '../includes/db-connect.php';

// Start output buffering
ob_start();

// Set content type to text/html
header('Content-Type: text/html; charset=utf-8');

// Include basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Post-Import Cleanup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .warning {
            color: orange;
            font-weight: bold;
        }
        .info {
            color: blue;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .button.danger {
            background-color: #f44336;
        }
        .button:hover {
            opacity: 0.8;
        }
        .progress-bar {
            width: 100%;
            background-color: #e0e0e0;
            padding: 3px;
            border-radius: 3px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, .2);
            margin-bottom: 20px;
        }
        .progress-bar-fill {
            display: block;
            height: 22px;
            background-color: #4CAF50;
            border-radius: 3px;
            transition: width 0.5s ease-in-out;
            text-align: center;
            line-height: 22px;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Post-Import Cleanup</h1>
    <p>This script performs a comprehensive cleanup after importing reviews.</p>
';

// Check if the script is being run with the 'execute' parameter
$execute = isset($_GET['execute']) && $_GET['execute'] === 'true';

// Function to log messages
function logMessage($message, $type = 'info') {
    echo "<p class=\"$type\">$message</p>";
    ob_flush();
    flush();
}

try {
    // Step 1: Fix reviewer names by removing asterisks and standardizing format
    logMessage("Step 1: Standardizing reviewer names...");
    
    if ($execute) {
        $db->beginTransaction();
        
        // Remove asterisks and standardize format
        $stmt = $db->prepare("
            UPDATE reviews 
            SET reviewer_name = TRIM(REPLACE(REPLACE(reviewer_name, '**', ''), '*', ''))
            WHERE reviewer_name LIKE '%**%' OR reviewer_name LIKE '%*%'
        ");
        $stmt->execute();
        $namesUpdated = $stmt->rowCount();
        
        // Fix specific issues with reviewer names
        $stmt = $db->prepare("
            UPDATE reviews 
            SET reviewer_name = 'Jessica'
            WHERE reviewer_name LIKE '## The Whizz Pop Chocolate Shop%Jessica%'
        ");
        $stmt->execute();
        $specialNamesUpdated = $stmt->rowCount();
        
        // Fix "Review" as reviewer name
        $stmt = $db->prepare("
            UPDATE reviews
            SET reviewer_name = CONCAT('Anonymous ', id)
            WHERE reviewer_name = 'Review'
        ");
        $stmt->execute();
        $anonymousNamesUpdated = $stmt->rowCount();
        
        $db->commit();
        
        logMessage("Updated $namesUpdated reviewer names with asterisks.", "success");
        logMessage("Fixed $specialNamesUpdated special reviewer names.", "success");
        logMessage("Fixed $anonymousNamesUpdated anonymous reviewer names.", "success");
    } else {
        // Count how many would be updated
        $stmt = $db->query("SELECT COUNT(*) FROM reviews WHERE reviewer_name LIKE '%**%' OR reviewer_name LIKE '%*%'");
        $namesCount = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) FROM reviews WHERE reviewer_name LIKE '## The Whizz Pop Chocolate Shop%Jessica%'");
        $specialNamesCount = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) FROM reviews WHERE reviewer_name = 'Review'");
        $anonymousNamesCount = $stmt->fetchColumn();
        
        logMessage("Would update $namesCount reviewer names with asterisks.", "info");
        logMessage("Would fix $specialNamesCount special reviewer names.", "info");
        logMessage("Would fix $anonymousNamesCount anonymous reviewer names.", "info");
    }
    
    // Step 2: Remove duplicate reviews
    logMessage("Step 2: Removing duplicate reviews...");
    
    // Find duplicate reviews
    $stmt = $db->query("
        SELECT book_id, LOWER(TRIM(reviewer_name)) as clean_name, COUNT(*) as count
        FROM reviews
        GROUP BY book_id, LOWER(TRIM(reviewer_name))
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    $duplicateGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($duplicateGroups) . " groups of duplicate reviews.");
    
    if (count($duplicateGroups) > 0) {
        echo "<h3>Duplicate Review Groups</h3>";
        echo "<table>";
        echo "<tr><th>Book ID</th><th>Reviewer</th><th>Count</th></tr>";
        
        foreach ($duplicateGroups as $group) {
            echo "<tr>";
            echo "<td>{$group['book_id']}</td>";
            echo "<td>" . htmlspecialchars($group['clean_name']) . "</td>";
            echo "<td>{$group['count']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        if ($execute) {
            logMessage("Removing duplicate reviews...", "warning");
            
            $db->beginTransaction();
            
            try {
                // Create a temporary table to store the IDs of reviews to keep
                $db->exec("
                    CREATE TEMPORARY TABLE reviews_to_keep (
                        id INT NOT NULL,
                        book_id INT NOT NULL,
                        clean_name VARCHAR(255),
                        PRIMARY KEY (id)
                    )
                ");
                
                // Insert the first review for each book and normalized reviewer name combination
                $db->exec("
                    INSERT INTO reviews_to_keep (id, book_id, clean_name)
                    SELECT 
                        MIN(r.id) as id,
                        r.book_id,
                        LOWER(TRIM(reviewer_name)) as clean_name
                    FROM 
                        reviews r
                    GROUP BY 
                        r.book_id, 
                        LOWER(TRIM(reviewer_name))
                ");
                
                // Count how many reviews will be deleted
                $stmt = $db->query("
                    SELECT COUNT(*) FROM reviews 
                    WHERE id NOT IN (SELECT id FROM reviews_to_keep)
                ");
                $deletedCount = $stmt->fetchColumn();
                
                // Delete reviews that are not in the reviews_to_keep table
                $db->exec("
                    DELETE FROM reviews 
                    WHERE id NOT IN (SELECT id FROM reviews_to_keep)
                ");
                
                // Drop the temporary table
                $db->exec("DROP TEMPORARY TABLE IF EXISTS reviews_to_keep");
                
                $db->commit();
                logMessage("Successfully deleted $deletedCount duplicate reviews.", "success");
            } catch (Exception $e) {
                $db->rollBack();
                logMessage("Error deleting duplicate reviews: " . $e->getMessage(), "error");
            }
        } else {
            // Count how many would be deleted
            $totalDuplicates = 0;
            foreach ($duplicateGroups as $group) {
                $totalDuplicates += $group['count'] - 1;
            }
            
            logMessage("Would delete $totalDuplicates duplicate reviews.", "info");
            echo "<p><a href=\"?execute=true\" class=\"button danger\">Execute Cleanup</a></p>";
        }
    }
    
    // Step 3: Update book ratings
    logMessage("Step 3: Updating book ratings...");
    
    // Get all books with reviews
    $stmt = $db->query("
        SELECT DISTINCT book_id 
        FROM reviews
        ORDER BY book_id
    ");
    $books = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    logMessage("Found " . count($books) . " books with reviews.");
    
    if ($execute && count($books) > 0) {
        $updatedBooks = 0;
        
        foreach ($books as $bookId) {
            // Get all reviews for the book
            $stmt = $db->prepare("SELECT rating_normalised FROM reviews WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $reviews = $stmt->fetchAll();
            
            // Calculate average rating and review count
            $reviewCount = count($reviews);
            $averageRating = 0;
            $highestRating = 0;
            $lowestRating = 1;
            
            if ($reviewCount > 0) {
                $ratingSum = 0;
                foreach ($reviews as $review) {
                    $rating = (float)$review['rating_normalised'];
                    $ratingSum += $rating;
                    
                    if ($rating > $highestRating) {
                        $highestRating = $rating;
                    }
                    if ($rating < $lowestRating) {
                        $lowestRating = $rating;
                    }
                }
                $averageRating = $ratingSum / $reviewCount;
            } else {
                $lowestRating = 0;
            }
            
            // Update the directory item with the new ratings data
            $stmt = $db->prepare("
                UPDATE directory_items SET
                    average_rating = ?,
                    review_count = ?,
                    highest_rating = ?,
                    lowest_rating = ?
                WHERE id = ?
            ");
            $stmt->execute([$averageRating, $reviewCount, $highestRating, $lowestRating, $bookId]);
            
            if ($stmt->rowCount() > 0) {
                $updatedBooks++;
            }
        }
        
        logMessage("Updated ratings for $updatedBooks books.", "success");
    } else {
        logMessage("Would update ratings for " . count($books) . " books.", "info");
    }
    
    // Step 4: Display summary
    echo "<h2>Summary</h2>";
    
    // Get current review count
    $stmt = $db->query("SELECT COUNT(*) FROM reviews");
    $totalReviews = $stmt->fetchColumn();
    
    echo "<ul>";
    echo "<li>Total reviews in database: $totalReviews</li>";
    
    if ($execute) {
        echo "<li>Cleanup completed successfully!</li>";
    } else {
        echo "<li>Dry run completed. Click 'Execute Cleanup' to perform the cleanup.</li>";
        echo "<p><a href=\"?execute=true\" class=\"button danger\">Execute Cleanup</a></p>";
    }
    echo "</ul>";
    
    // Add link to go back to admin
    echo "<p><a href=\"../content/directory-items.php\" class=\"button\">Back to Directory Items</a></p>";
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), "error");
}

echo '</body></html>';
