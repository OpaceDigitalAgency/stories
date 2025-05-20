<?php
// Include database connection
require_once '../includes/db-connect.php';

// Get book ID from URL parameter
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// Get book details
$bookStmt = $db->prepare("
    SELECT di.id, di.title, di.review_count, di.average_rating, b.isbn, b.isbn13, b.author
    FROM directory_items di
    JOIN books b ON di.id = b.directory_item_id
    WHERE di.id = ?
");
$bookStmt->execute([$bookId]);
$book = $bookStmt->fetch(PDO::FETCH_ASSOC);

// Get Goodreads source only
$sourcesStmt = $db->prepare("
    SELECT id, name, url, is_third_party
    FROM review_sources
    WHERE name = 'Goodreads'
    ORDER BY name ASC
");
$sourcesStmt->execute();
$sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Scrape Reviews</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background: #fff; }
        .form-container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="form-container">
        <h4>Scrape Reviews for <?php echo htmlspecialchars($book['title']); ?></h4>
        <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
        <p><strong>ISBN:</strong> <?php echo !empty($book['isbn13']) ? htmlspecialchars($book['isbn13']) : htmlspecialchars($book['isbn']); ?></p>

        <form action="book-import-scrape.php" method="post" id="scrape-form" target="_parent">
            <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Select Sources</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($sources as $source): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sources[]"
                                   value="<?php echo $source['id']; ?>" id="source-<?php echo $source['id']; ?>" checked>
                            <label class="form-check-label" for="source-<?php echo $source['id']; ?>">
                                <?php echo htmlspecialchars($source['name']); ?>
                                <?php if ($source['is_third_party']): ?>
                                    <span class="badge badge-info">External</span>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Scraping Options</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="review_limit">Maximum Reviews</label>
                        <input type="number" class="form-control" id="review_limit" name="review_limit"
                               min="10" max="1000" value="100">
                        <small class="form-text text-muted">Number of reviews to fetch (10-1000)</small>
                    </div>

                    <div class="form-group">
                        <label for="max_pages">Maximum Pages</label>
                        <input type="number" class="form-control" id="max_pages" name="max_pages"
                               min="1" max="100" value="20">
                        <small class="form-text text-muted">Maximum pages to scrape (1-100)</small>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="force_refresh" name="force_refresh" value="1">
                        <label class="form-check-label" for="force_refresh">Force Refresh (ignore cache)</label>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-cloud-download-alt"></i> Start Scraping Reviews
                </button>
            </div>
        </form>
    </div>

    <script>
        // Handle form submission
        document.getElementById('scrape-form').addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
            window.parent.closeModal();
        });
    </script>
</body>
</html>