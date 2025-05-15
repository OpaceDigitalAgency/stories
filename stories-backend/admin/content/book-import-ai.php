<?php
/**
 * Book Import AI Analysis
 *
 * This script handles the AI analysis of book reviews to identify age-related content,
 * content flags, and generate summaries.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include the AI review analyzer
require_once '../../services/AI/ReviewAnalyzer.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Get OpenAI API key from settings
$openaiApiKey = '';
try {
    $settingsStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'openai_api_key'");
    $settingsStmt->execute();
    $openaiApiKey = $settingsStmt->fetchColumn();
} catch (Exception $e) {
    // If we can't get the API key, we'll just use the fallback method
    error_log("Error getting OpenAI API key: " . $e->getMessage());
}

// Create the review analyzer
$reviewAnalyzer = null;
try {
    // First try to create with database connection
    $reviewAnalyzer = new \Services\AI\ReviewAnalyzer($db);

    // If we don't have an API key from the database, but we got one from settings
    if (empty($reviewAnalyzer->getApiKey()) && !empty($openaiApiKey)) {
        // Create with explicit API key
        $reviewAnalyzer = new \Services\AI\ReviewAnalyzer($openaiApiKey, 'gpt-4o');
    }
} catch (Exception $e) {
    error_log("Error creating ReviewAnalyzer: " . $e->getMessage());

    // Fallback to creating with API key if we have one
    if (!empty($openaiApiKey)) {
        $reviewAnalyzer = new \Services\AI\ReviewAnalyzer($openaiApiKey, 'gpt-4o');
    }
}

// Function to analyze review for age suitability
function analyzeReviewForAgeSuitability($reviewText, $bookTitle = '', $bookAuthor = '') {
    global $reviewAnalyzer;

    // If we have a review analyzer, use it
    if ($reviewAnalyzer !== null) {
        try {
            $analysis = $reviewAnalyzer->analyzeReviewForAgeSuitability($reviewText, $bookTitle, $bookAuthor);

            if ($analysis !== null) {
                return $analysis;
            }

            // If the analysis failed, log the error and fall back to the pattern-based method
            error_log("AI analysis failed: " . $reviewAnalyzer->getLastError());
        } catch (Exception $e) {
            error_log("Error in AI analysis: " . $e->getMessage());
        }
    }

    // Fallback to pattern-based analysis if AI is not available or fails
    return patternBasedAnalysis($reviewText);
}

// Fallback function using pattern matching
function patternBasedAnalysis($reviewText) {
    // Extract age-related content from the review text
    $agePatterns = [
        '/(\d+)[- ]year[- ]old/i',
        '/ages? (\d+)[-+](\d+)?/i',
        '/grade (\d+)/i',
        '/(\d+)(?:st|nd|rd|th) grade/i'
    ];

    $ageMatches = [];
    foreach ($agePatterns as $pattern) {
        if (preg_match_all($pattern, $reviewText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $ageMatches[] = $match[0];
            }
        }
    }

    // Determine suitability score based on content
    $suitabilityScore = 0.8; // Default high score

    // Look for keywords that might indicate content concerns
    $concernKeywords = [
        'scary' => 0.1,
        'frightening' => 0.15,
        'violent' => 0.2,
        'mature' => 0.2,
        'inappropriate' => 0.3,
        'adult' => 0.2,
        'language' => 0.15,
        'swear' => 0.2
    ];

    foreach ($concernKeywords as $keyword => $penalty) {
        if (stripos($reviewText, $keyword) !== false) {
            $suitabilityScore -= $penalty;
        }
    }

    // Look for positive indicators
    $positiveKeywords = [
        'appropriate' => 0.05,
        'suitable' => 0.05,
        'perfect for' => 0.1,
        'great for' => 0.05,
        'recommend' => 0.05,
        'educational' => 0.1
    ];

    foreach ($positiveKeywords as $keyword => $bonus) {
        if (stripos($reviewText, $keyword) !== false) {
            $suitabilityScore += $bonus;
        }
    }

    // Ensure score is between 0 and 1
    $suitabilityScore = max(0, min(1, $suitabilityScore));

    // Generate a summary focusing on age-related content
    $summary = "This review ";

    if (!empty($ageMatches)) {
        $summary .= "specifically mentions " . implode(', ', $ageMatches) . ". ";
    } else {
        $summary .= "doesn't explicitly mention age ranges. ";
    }

    if ($suitabilityScore > 0.8) {
        $summary .= "The content appears highly suitable for children with no concerning elements mentioned.";
    } elseif ($suitabilityScore > 0.6) {
        $summary .= "The content appears generally suitable for children with minor elements that may require parental guidance.";
    } elseif ($suitabilityScore > 0.4) {
        $summary .= "The content contains some elements that may not be suitable for younger children without parental guidance.";
    } else {
        $summary .= "The content contains elements that may be concerning for children's reading without significant parental guidance.";
    }

    // Identify content flags
    $contentFlags = [];

    if (stripos($reviewText, 'scar') !== false || stripos($reviewText, 'frighten') !== false) {
        $contentFlags[] = 'scary_content';
    }

    if (stripos($reviewText, 'violen') !== false || stripos($reviewText, 'fight') !== false) {
        $contentFlags[] = 'violence';
    }

    if (stripos($reviewText, 'language') !== false || stripos($reviewText, 'swear') !== false) {
        $contentFlags[] = 'language';
    }

    if (stripos($reviewText, 'death') !== false || stripos($reviewText, 'dying') !== false) {
        $contentFlags[] = 'death';
    }

    if (stripos($reviewText, 'mature') !== false || stripos($reviewText, 'adult') !== false) {
        $contentFlags[] = 'mature_themes';
    }

    return [
        'suitability_score' => $suitabilityScore,
        'content_flags' => $contentFlags,
        'ai_summary' => $summary,
        'age_mentions' => $ageMatches
    ];
}

// Function to update book aggregate AI values
function updateBookAggregateAiValues($db, $bookId) {
    // Get aggregate values
    $aggregateStmt = $db->prepare("
        SELECT
            AVG(suitability_score) as avg_suitability_score,
            JSON_ARRAYAGG(content_flags) as all_content_flags
        FROM reviews
        WHERE book_id = ? AND suitability_score IS NOT NULL
    ");
    $aggregateStmt->execute([$bookId]);
    $aggregateValues = $aggregateStmt->fetch(PDO::FETCH_ASSOC);

    if (!$aggregateValues['avg_suitability_score']) {
        return false;
    }

    // Process content flags
    $allFlags = [];
    if ($aggregateValues['all_content_flags']) {
        $flagsArray = json_decode($aggregateValues['all_content_flags'], true);
        foreach ($flagsArray as $flagsJson) {
            $flags = json_decode($flagsJson, true);
            if (is_array($flags)) {
                $allFlags = array_merge($allFlags, $flags);
            }
        }
    }

    // Count occurrences of each flag
    $flagCounts = array_count_values($allFlags);
    arsort($flagCounts); // Sort by frequency

    // Update the directory item
    $stmt = $db->prepare("
        UPDATE directory_items
        SET
            suitability_score = ?,
            content_flags = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $aggregateValues['avg_suitability_score'],
        json_encode($flagCounts),
        $bookId
    ]);

    return true;
}

// Main processing logic
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Review AI Analysis</title>
    <link rel="stylesheet" href="../assets/css/enhanced-admin.css">
    <style>
        .progress-container {
            margin: 20px 0;
            background-color: #f1f1f1;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 30px;
            background-color: #4CAF50;
            text-align: center;
            line-height: 30px;
            color: white;
            transition: width 0.3s;
        }
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Book Review AI Analysis</h1>

        <div class="progress-container">
            <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
        </div>

        <div class="log-container" id="logContainer">
            <p class="info">Starting AI analysis process...</p>
            <?php
            // Process the request
            try {
                // Get parameters
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $analysisType = $_POST['analysis_type'] ?? 'age_suitability';
                    $bookSelection = $_POST['book_selection'] ?? 'all';
                    $specificBooks = $_POST['specific_books'] ?? [];
                    $aiModel = $_POST['ai_model'] ?? 'gpt-4o';
                } else {
                    $analysisType = $_GET['type'] ?? 'age_suitability';
                    $bookSelection = 'specific';
                    $specificBooks = isset($_GET['books']) ? explode(',', $_GET['books']) : [];
                    $aiModel = $_GET['model'] ?? 'gpt-4o';
                }

                echo "<p class='info'>Analysis type: $analysisType</p>";
                echo "<p class='info'>Book selection: $bookSelection</p>";
                echo "<p class='info'>AI model: $aiModel</p>";
                flushOutput();

                // Get books to analyze
                $bookIds = [];

                if ($bookSelection === 'specific' && !empty($specificBooks)) {
                    $bookIds = array_filter($specificBooks, 'is_numeric');
                } else if ($bookSelection === 'missing') {
                    // Get books missing AI analysis
                    $missingStmt = $db->prepare("
                        SELECT di.id
                        FROM directory_items di
                        LEFT JOIN reviews r ON di.id = r.book_id AND r.ai_summary IS NOT NULL
                        WHERE di.type = 'book' AND r.id IS NULL
                    ");
                    $missingStmt->execute();
                    $bookIds = $missingStmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    // Get all books
                    $allStmt = $db->prepare("
                        SELECT id FROM directory_items WHERE type = 'book'
                    ");
                    $allStmt->execute();
                    $bookIds = $allStmt->fetchAll(PDO::FETCH_COLUMN);
                }

                if (empty($bookIds)) {
                    echo "<p class='error'>No books found for analysis</p>";
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                    exit;
                }

                echo "<p class='info'>Found " . count($bookIds) . " books for analysis</p>";
                flushOutput();

                // Process each book
                $totalBooks = count($bookIds);
                $totalReviewsAnalyzed = 0;

                foreach ($bookIds as $index => $bookId) {
                    $progress = round(($index / $totalBooks) * 100);
                    echo "<script>
                        document.getElementById('progressBar').style.width = '$progress%';
                        document.getElementById('progressBar').innerText = '$progress%';
                    </script>";
                    flushOutput();

                    // Get book details
                    $bookStmt = $db->prepare("
                        SELECT title FROM directory_items WHERE id = ?
                    ");
                    $bookStmt->execute([$bookId]);
                    $bookTitle = $bookStmt->fetchColumn();

                    if (!$bookTitle) {
                        echo "<p class='warning'>Book with ID $bookId not found, skipping</p>";
                        flushOutput();
                        continue;
                    }

                    echo "<h3>Processing book " . ($index + 1) . " of $totalBooks: $bookTitle</h3>";
                    flushOutput();

                    // Get reviews for this book
                    $reviewsStmt = $db->prepare("
                        SELECT id, reviewer_name, review_text
                        FROM reviews
                        WHERE book_id = ? AND review_text IS NOT NULL AND review_text != ''
                    ");
                    $reviewsStmt->execute([$bookId]);
                    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($reviews)) {
                        echo "<p class='warning'>No reviews found for book: $bookTitle</p>";
                        flushOutput();
                        continue;
                    }

                    echo "<p class='info'>Found " . count($reviews) . " reviews for analysis</p>";
                    flushOutput();

                    $bookReviewsAnalyzed = 0;

                    // Process each review
                    foreach ($reviews as $review) {
                        echo "<p class='info'>Analyzing review by {$review['reviewer_name']}</p>";
                        flushOutput();

                        try {
                            // Analyze the review based on analysis type
                            if ($analysisType === 'age_suitability' || $analysisType === 'content_flags') {
                                $analysis = analyzeReviewForAgeSuitability($review['review_text'], $bookTitle);

                                // Update the review with analysis results
                                $updateStmt = $db->prepare("
                                    UPDATE reviews
                                    SET
                                        ai_summary = ?,
                                        suitability_score = ?,
                                        content_flags = ?,
                                        updated_at = NOW()
                                    WHERE id = ?
                                ");

                                $updateStmt->execute([
                                    $analysis['ai_summary'],
                                    $analysis['suitability_score'],
                                    json_encode($analysis['content_flags']),
                                    $review['id']
                                ]);

                                echo "<p class='success'>Updated review with AI analysis</p>";
                                echo "<p class='info'>Suitability score: " . number_format($analysis['suitability_score'], 2) . "</p>";
                                echo "<p class='info'>Content flags: " . (!empty($analysis['content_flags']) ? implode(', ', $analysis['content_flags']) : 'None') . "</p>";
                                echo "<p class='info'>Summary: " . $analysis['ai_summary'] . "</p>";
                                flushOutput();
                            } else if ($analysisType === 'review_summary') {
                                // For review summary, we would typically call a different AI function
                                // For now, we'll reuse the age suitability analysis
                                $analysis = analyzeReviewForAgeSuitability($review['review_text'], $bookTitle);

                                // Update only the summary
                                $updateStmt = $db->prepare("
                                    UPDATE reviews
                                    SET
                                        ai_summary = ?,
                                        updated_at = NOW()
                                    WHERE id = ?
                                ");

                                $updateStmt->execute([
                                    $analysis['ai_summary'],
                                    $review['id']
                                ]);

                                echo "<p class='success'>Updated review with AI summary</p>";
                                echo "<p class='info'>Summary: " . $analysis['ai_summary'] . "</p>";
                                flushOutput();
                            }

                            $bookReviewsAnalyzed++;
                        } catch (Exception $e) {
                            echo "<p class='error'>Error analyzing review: " . $e->getMessage() . "</p>";
                            flushOutput();
                        }
                    }

                    // Update book aggregate values
                    if ($bookReviewsAnalyzed > 0) {
                        if (updateBookAggregateAiValues($db, $bookId)) {
                            echo "<p class='success'>Updated aggregate AI values for book: $bookTitle</p>";
                        } else {
                            echo "<p class='warning'>Failed to update aggregate AI values for book: $bookTitle</p>";
                        }
                        flushOutput();
                    }

                    echo "<p class='info'>Book summary: Analyzed $bookReviewsAnalyzed reviews</p>";
                    flushOutput();

                    $totalReviewsAnalyzed += $bookReviewsAnalyzed;
                }

                // Update progress to 100%
                echo "<script>
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('progressBar').innerText = '100%';
                </script>";
                flushOutput();

                // Summary
                echo "<h3>Analysis Summary</h3>";
                echo "<p>Total books processed: $totalBooks</p>";
                echo "<p>Total reviews analyzed: $totalReviewsAnalyzed</p>";

                echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
                echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
            }
            ?>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of log container
        const logContainer = document.getElementById('logContainer');
        logContainer.scrollTop = logContainer.scrollHeight;

        // Set up interval to auto-scroll
        setInterval(function() {
            logContainer.scrollTop = logContainer.scrollHeight;
        }, 500);
    </script>
</body>
</html>
