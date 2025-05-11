<?php
/**
 * Clean Duplicate Reviews Script
 * 
 * This script identifies and removes duplicate reviews in the database.
 * It looks for reviews with the same book_id and similar reviewer_name
 * (with or without ** prefixes) and keeps only one copy.
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
    <title>Clean Duplicate Reviews</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
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
    </style>
</head>
<body>
    <h1>Clean Duplicate Reviews</h1>
    <p>This script identifies and removes duplicate reviews in the database.</p>
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
    // Step 1: Find all reviews with ** in the reviewer_name
    logMessage("Step 1: Finding reviews with ** in the reviewer_name...");
    
    $stmt = $db->query("SELECT id, book_id, reviewer_name, review_text FROM reviews WHERE reviewer_name LIKE '%**%'");
    $reviewsWithAsterisks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($reviewsWithAsterisks) . " reviews with ** in the reviewer_name.");
    
    // Step 2: Find duplicate reviews based on book_id and similar reviewer_name
    logMessage("Step 2: Finding duplicate reviews...");
    
    $duplicateGroups = [];
    $processedIds = [];
    
    // Get all reviews
    $stmt = $db->query("SELECT id, book_id, reviewer_name, review_text FROM reviews ORDER BY book_id, id");
    $allReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Total reviews in database: " . count($allReviews));
    
    // Group reviews by book_id
    $reviewsByBook = [];
    foreach ($allReviews as $review) {
        $bookId = $review['book_id'];
        if (!isset($reviewsByBook[$bookId])) {
            $reviewsByBook[$bookId] = [];
        }
        $reviewsByBook[$bookId][] = $review;
    }
    
    // Find duplicates within each book group
    foreach ($reviewsByBook as $bookId => $bookReviews) {
        // Skip if there's only one review for this book
        if (count($bookReviews) <= 1) {
            continue;
        }
        
        // Check each review against others in the same book
        foreach ($bookReviews as $i => $review1) {
            // Skip if already processed
            if (in_array($review1['id'], $processedIds)) {
                continue;
            }
            
            $duplicateGroup = [$review1];
            
            // Clean reviewer name for comparison
            $cleanName1 = preg_replace('/\*\*.*$/', '', preg_replace('/^\*\*/', '', $review1['reviewer_name']));
            $cleanName1 = trim($cleanName1);
            
            // Compare with other reviews
            for ($j = $i + 1; $j < count($bookReviews); $j++) {
                $review2 = $bookReviews[$j];
                
                // Skip if already processed
                if (in_array($review2['id'], $processedIds)) {
                    continue;
                }
                
                // Clean reviewer name for comparison
                $cleanName2 = preg_replace('/\*\*.*$/', '', preg_replace('/^\*\*/', '', $review2['reviewer_name']));
                $cleanName2 = trim($cleanName2);
                
                // Check if names are similar
                if ($cleanName1 === $cleanName2 || 
                    (strlen($cleanName1) > 0 && strlen($cleanName2) > 0 && 
                     (strpos($cleanName1, $cleanName2) !== false || strpos($cleanName2, $cleanName1) !== false))) {
                    
                    // Check if review texts are similar
                    $similarity = 0;
                    if (!empty($review1['review_text']) && !empty($review2['review_text'])) {
                        similar_text($review1['review_text'], $review2['review_text'], $similarity);
                    }
                    
                    // If names match or review texts are similar, consider it a duplicate
                    if ($cleanName1 === $cleanName2 || $similarity > 70) {
                        $duplicateGroup[] = $review2;
                        $processedIds[] = $review2['id'];
                    }
                }
            }
            
            // If duplicates found, add to groups
            if (count($duplicateGroup) > 1) {
                $duplicateGroups[] = $duplicateGroup;
                $processedIds[] = $review1['id'];
            }
        }
    }
    
    logMessage("Found " . count($duplicateGroups) . " groups of duplicate reviews.");
    
    // Display duplicate groups
    if (count($duplicateGroups) > 0) {
        echo "<h2>Duplicate Review Groups</h2>";
        echo "<table>";
        echo "<tr><th>Group</th><th>Review ID</th><th>Book ID</th><th>Reviewer Name</th><th>Review Text</th><th>Action</th></tr>";
        
        foreach ($duplicateGroups as $groupIndex => $group) {
            $groupNumber = $groupIndex + 1;
            $rowspan = count($group);
            
            foreach ($group as $index => $review) {
                echo "<tr>";
                
                if ($index === 0) {
                    echo "<td rowspan=\"$rowspan\">Group $groupNumber</td>";
                }
                
                echo "<td>{$review['id']}</td>";
                echo "<td>{$review['book_id']}</td>";
                echo "<td>" . htmlspecialchars($review['reviewer_name']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($review['review_text'], 0, 100)) . (strlen($review['review_text']) > 100 ? '...' : '') . "</td>";
                
                if ($index === 0) {
                    echo "<td rowspan=\"$rowspan\">Keep</td>";
                } else {
                    echo "<td>Delete</td>";
                }
                
                echo "</tr>";
            }
        }
        
        echo "</table>";
    }
    
    // Step 3: Delete duplicate reviews if execute is true
    if ($execute) {
        logMessage("Step 3: Deleting duplicate reviews...", "warning");
        
        $deletedCount = 0;
        $db->beginTransaction();
        
        try {
            foreach ($duplicateGroups as $group) {
                // Keep the first review, delete the rest
                for ($i = 1; $i < count($group); $i++) {
                    $reviewId = $group[$i]['id'];
                    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
                    $stmt->execute([$reviewId]);
                    $deletedCount++;
                }
            }
            
            $db->commit();
            logMessage("Successfully deleted $deletedCount duplicate reviews.", "success");
        } catch (Exception $e) {
            $db->rollBack();
            logMessage("Error deleting duplicate reviews: " . $e->getMessage(), "error");
        }
        
        // Step 4: Update book ratings
        logMessage("Step 4: Updating book ratings...");
        
        $updatedBooks = [];
        
        foreach ($duplicateGroups as $group) {
            $bookId = $group[0]['book_id'];
            if (!in_array($bookId, $updatedBooks)) {
                $updatedBooks[] = $bookId;
                
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
            }
        }
        
        logMessage("Updated ratings for " . count($updatedBooks) . " books.", "success");
    } else {
        echo "<p><a href=\"?execute=true\" class=\"button danger\">Execute Cleanup</a></p>";
        logMessage("Dry run completed. Click 'Execute Cleanup' to remove duplicate reviews.", "warning");
    }
    
    // Step 5: Display summary
    echo "<h2>Summary</h2>";
    echo "<ul>";
    echo "<li>Total reviews in database: " . count($allReviews) . "</li>";
    echo "<li>Reviews with ** in name: " . count($reviewsWithAsterisks) . "</li>";
    echo "<li>Duplicate review groups found: " . count($duplicateGroups) . "</li>";
    
    $totalDuplicates = 0;
    foreach ($duplicateGroups as $group) {
        $totalDuplicates += count($group) - 1;
    }
    
    echo "<li>Total duplicate reviews: " . $totalDuplicates . "</li>";
    
    if ($execute) {
        echo "<li>Reviews deleted: " . $deletedCount . "</li>";
        echo "<li>Books with updated ratings: " . count($updatedBooks) . "</li>";
    }
    
    echo "</ul>";
    
    // Add link to go back to admin
    echo "<p><a href=\"../content/directory-items.php\" class=\"button\">Back to Directory Items</a></p>";
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), "error");
}

echo '</body></html>';
