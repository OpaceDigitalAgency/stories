<?php
/**
 * Check Fetchers
 * 
 * This page allows testing of review fetchers with specific ISBNs.
 */

// Set page title and current page
$pageTitle = 'Check Review Fetchers';
$currentPage = 'check-fetchers';
$pageDescription = 'Test review fetchers with specific ISBNs';

// Include the header
require_once '../includes/auth.php';
require_once '../includes/header.php';

// Include the review fetcher factory
require_once dirname(dirname(dirname(__FILE__))) . '/services/ReviewFetcher/ReviewFetcherFactory.php';

// Create the review fetcher factory
$reviewFetcherFactory = new Services\ReviewFetcher\ReviewFetcherFactory($db);

// Get all review sources
$stmt = $db->prepare("SELECT id, name FROM review_sources ORDER BY name");
$stmt->execute();
$sources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the debug directory
$debugDir = dirname(dirname(dirname(__FILE__))) . '/services/ReviewFetcher/debug';

// Create the debug directory if it doesn't exist
if (!is_dir($debugDir)) {
    mkdir($debugDir, 0755, true);
    chmod($debugDir, 0777);
}

// Set up error log capture
$logFile = $debugDir . '/check-fetchers.txt';

// Clear the log file
file_put_contents($logFile, "Starting fetcher check at " . date('Y-m-d H:i:s') . "\n");
chmod($logFile, 0666);

// Handle form submission
$results = [];
$isbn = '';
$sourceId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isbn']) && isset($_POST['source_id'])) {
    $isbn = trim($_POST['isbn']);
    $sourceId = (int)$_POST['source_id'];
    
    if (empty($isbn)) {
        $results['error'] = 'Please enter an ISBN';
    } else {
        // Get the source name
        $sourceName = '';
        foreach ($sources as $source) {
            if ($source['id'] == $sourceId) {
                $sourceName = $source['name'];
                break;
            }
        }
        
        // Log the request
        file_put_contents($logFile, "\n\n=== Testing {$sourceName} fetcher with ISBN: {$isbn} ===\n", FILE_APPEND);
        
        // Get the fetcher
        $fetcher = $reviewFetcherFactory->getFetcher($sourceId);
        
        if (!$fetcher) {
            $results['error'] = "No fetcher available for source ID: {$sourceId}";
            file_put_contents($logFile, "ERROR: {$results['error']}\n", FILE_APPEND);
        } else {
            // Test the fetcher
            $startTime = microtime(true);
            $reviews = $fetcher->fetchReviewsByISBN($isbn, 5);
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            // Log the results
            file_put_contents($logFile, "Execution time: {$executionTime} seconds\n", FILE_APPEND);
            file_put_contents($logFile, "Found " . count($reviews) . " reviews\n", FILE_APPEND);
            
            if (empty($reviews)) {
                $results['error'] = "No reviews found. Error: " . $fetcher->getLastError();
                file_put_contents($logFile, "ERROR: {$results['error']}\n", FILE_APPEND);
            } else {
                $results['success'] = true;
                $results['reviews'] = $reviews;
                $results['execution_time'] = $executionTime;
                $results['count'] = count($reviews);
                
                // Log review details
                foreach ($reviews as $index => $review) {
                    file_put_contents($logFile, "\nReview #" . ($index + 1) . ":\n", FILE_APPEND);
                    file_put_contents($logFile, "Reviewer: {$review['reviewer_name']}\n", FILE_APPEND);
                    file_put_contents($logFile, "Rating: {$review['original_rating']} ({$review['rating_value']}/{$review['rating_scale']})\n", FILE_APPEND);
                    file_put_contents($logFile, "Date: {$review['review_date']}\n", FILE_APPEND);
                    file_put_contents($logFile, "Text: " . substr($review['review_text'], 0, 100) . "...\n", FILE_APPEND);
                }
            }
        }
    }
}

// Add page actions
$pageActions = '
<div class="btn-group">
    <a href="book-import-tool.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Import Tool
    </a>
    <a href="debug-logs.php" class="btn btn-info">
        <i class="fas fa-file-alt"></i> View Debug Logs
    </a>
    <a href="check-fetcher-files.php" class="btn btn-secondary">
        <i class="fas fa-file-code"></i> Check Fetcher Files
    </a>
</div>';

?>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Test Review Fetchers</h5>
                <?php echo $pageActions; ?>
            </div>
            <div class="card-body">
                <p>Use this form to test review fetchers with specific ISBNs. This will help diagnose issues with the review fetching process.</p>
                
                <form method="post" action="" class="mb-4">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="isbn">ISBN</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>" placeholder="Enter ISBN-10 or ISBN-13" required>
                            <small class="form-text text-muted">Example: 9780545010221 or 0545010225</small>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="source_id">Review Source</label>
                            <select class="form-control" id="source_id" name="source_id" required>
                                <option value="">Select a source</option>
                                <?php foreach ($sources as $source): ?>
                                    <option value="<?php echo $source['id']; ?>" <?php echo $sourceId == $source['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($source['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Test Fetcher
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (isset($results['error'])): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($results['error']); ?>
                    </div>
                <?php elseif (isset($results['success'])): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> Found <?php echo $results['count']; ?> reviews in <?php echo $results['execution_time']; ?> seconds.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Reviewer</th>
                                    <th>Rating</th>
                                    <th>Date</th>
                                    <th>Review Text</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['reviews'] as $index => $review): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($review['original_rating']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo $review['rating_value']; ?>/<?php echo $review['rating_scale']; ?>
                                                (<?php echo round($review['rating_normalised'] * 100); ?>%)
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($review['review_date']); ?></td>
                                        <td>
                                            <?php 
                                            $text = $review['review_text'];
                                            if (strlen($text) > 200) {
                                                echo htmlspecialchars(substr($text, 0, 200)) . '...';
                                                echo '<a href="#" class="show-full-review" data-review="' . htmlspecialchars($text) . '">Show More</a>';
                                            } else {
                                                echo htmlspecialchars($text);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Debug Information:</strong> Check the <a href="debug-logs.php" class="alert-link">debug logs</a> for more details.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal for full review text -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Full Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="fullReviewText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Show full review text in modal
        $('.show-full-review').click(function(e) {
            e.preventDefault();
            var reviewText = $(this).data('review');
            $('#fullReviewText').text(reviewText);
            $('#reviewModal').modal('show');
        });
    });
</script>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
