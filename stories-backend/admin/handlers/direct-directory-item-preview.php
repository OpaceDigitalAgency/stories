<?php
/**
 * Direct Directory Item Preview Handler
 *
 * This script renders a directory item preview directly in the admin interface
 * without using iframes or external URLs.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Get the directory item ID from the query string
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$item = null;
$category = null;
$error = null;
$bookData = null;
$reviews = [];
$averageRating = 0;
$reviewCount = 0;

try {
    // Get directory item data
    if ($itemId > 0) {
        $stmt = $db->prepare("
            SELECT d.*, c.name as category_name
            FROM directory_items d
            LEFT JOIN directory_categories c ON d.category_id = c.id
            WHERE d.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if ($item) {
            // If this is a book type, get book data
            if ($item['type'] === 'book') {
                $bookStmt = $db->prepare("
                    SELECT * FROM books WHERE directory_item_id = ?
                ");
                $bookStmt->execute([$itemId]);
                $bookData = $bookStmt->fetch();

                // Parse purchase links JSON if available
                if (!empty($bookData['purchase_links'])) {
                    try {
                        $bookData['purchase_links_array'] = json_decode($bookData['purchase_links'], true);
                    } catch (Exception $e) {
                        // If parsing fails, keep it as a string
                        $bookData['purchase_links_array'] = [];
                    }
                }

                // Fetch reviews for this directory item
                $reviewsStmt = $db->prepare("
                    SELECT r.*, s.name as source_name
                    FROM reviews r
                    LEFT JOIN review_sources s ON r.source_id = s.id
                    WHERE r.book_id = ?
                    ORDER BY r.review_date DESC
                ");
                $reviewsStmt->execute([$itemId]);
                $reviews = $reviewsStmt->fetchAll();

                // Calculate average rating and review count
                if (!empty($reviews)) {
                    $reviewCount = count($reviews);
                    $ratingSum = 0;

                    foreach ($reviews as $review) {
                        $ratingSum += $review['rating_normalised'];
                    }

                    $averageRating = $reviewCount > 0 ? $ratingSum / $reviewCount : 0;
                }
            }
        } else {
            $error = "Directory item not found.";
        }
    } else {
        $error = "Invalid directory item ID.";
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Function to render the directory item description with proper styling
function renderDescription($description) {
    // Check if the content is HTML
    if (strpos($description, '<') !== false && strpos($description, '>') !== false) {
        // It's HTML, process it to ensure images have absolute URLs
        $dom = new DOMDocument();

        // Use error suppression to avoid warnings about HTML5 tags
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $description);

        // Process all images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            // If the src is not an absolute URL, make it absolute
            if ($src && strpos($src, 'http') !== 0) {
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'api.storiesfromtheweb.org';
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $newSrc = "$protocol://$host" . (strpos($src, '/') === 0 ? $src : "/$src");
                $img->setAttribute('src', $newSrc);
            }
        }

        // Extract the body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            // Convert back to HTML string
            $html = '';
            foreach ($body->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
            return $html;
        }

        // Fallback if DOM processing fails
        return $description;
    } else {
        // It's plain text, convert newlines to <br> tags
        return nl2br(htmlspecialchars($description));
    }
}

// Format date from MySQL date to readable format
function formatDate($date) {
    if (empty($date)) return '';

    // Check if it's a valid date format
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;

    return date('F j, Y', $timestamp);
}

// Function to render star ratings
function renderStarRating($rating, $maxRating = 5, $size = 'md') {
    // Normalize rating to a scale of 0-5
    $normalizedRating = $rating * $maxRating;

    // Calculate full and half stars
    $fullStars = floor($normalizedRating);
    $halfStar = $normalizedRating - $fullStars >= 0.5;
    $emptyStars = $maxRating - $fullStars - ($halfStar ? 1 : 0);

    // Size classes
    $sizeClasses = [
        'sm' => 'width: 16px; height: 16px;',
        'md' => 'width: 20px; height: 20px;',
        'lg' => 'width: 24px; height: 24px;'
    ];

    $starStyle = $sizeClasses[$size] ?? $sizeClasses['md'];

    $html = '<div class="star-rating" style="display: inline-flex; align-items: center;">';

    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    // Half star
    if ($halfStar) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill-opacity="0.5"></path>
            <path d="M12 17.27V2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"></path>
        </svg>';
    }

    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #e0e0e0;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    $html .= '</div>';

    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title'] ?? 'Directory Item Preview'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .item-header {
            margin-bottom: 20px;
        }
        .item-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .item-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .item-category {
            display: inline-block;
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            margin-right: 8px;
        }
        .item-featured {
            display: inline-block;
            background-color: #fbf2cc;
            color: #8a6d3b;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
        }
        .item-cover {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .item-details, .book-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        .book-details {
            background-color: #f5f8ff;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 2px;
        }
        .item-description {
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .item-description img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin: 10px 0;
        }
        .section-title {
            font-size: 18px;
            margin: 25px 0 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
            color: #444;
        }
        .purchase-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .purchase-link {
            display: inline-block;
            padding: 5px 12px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        .purchase-link:hover {
            background-color: #e0e0e0;
        }
        .item-cta {
            margin-top: 30px;
            text-align: center;
        }
        .item-cta a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .item-cta a:hover {
            background-color: #45a049;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            background-color: #ebf5f0;
            color: #495057;
            font-size: 12px;
            font-weight: 500;
            margin-right: 5px;
        }
        /* Reviews section styles */
        .reviews-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f7ff;
            border-radius: 8px;
        }
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .reviews-summary {
            display: flex;
            align-items: center;
        }
        .average-rating {
            font-size: 32px;
            font-weight: bold;
            margin-right: 15px;
        }
        .review-count {
            color: #666;
            font-size: 14px;
            margin-left: 10px;
        }
        .reviews-list {
            margin-top: 20px;
        }
        .review-item {
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .reviewer-info {
            font-weight: bold;
        }
        .reviewer-age {
            color: #666;
            font-size: 14px;
            font-weight: normal;
        }
        .review-source {
            color: #666;
            font-size: 14px;
        }
        .review-date {
            color: #666;
            font-size: 14px;
        }
        .review-rating {
            margin-bottom: 10px;
        }
        .review-text {
            line-height: 1.6;
        }
        .no-reviews {
            padding: 20px;
            text-align: center;
            color: #666;
            background-color: #f0f0f0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="error-message">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php elseif ($item): ?>
        <div class="item-header">
            <h1 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h1>

            <div>
                <?php if (!empty($item['category_name'])): ?>
                    <span class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></span>
                <?php endif; ?>

                <?php if (!empty($item['featured'])): ?>
                    <span class="item-featured">Featured</span>
                <?php endif; ?>

                <?php if (!empty($item['type']) && $item['type'] !== 'general'): ?>
                    <span class="badge"><?php echo ucfirst(htmlspecialchars($item['type'])); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($item['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($item['cover_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-cover">
            <?php endif; ?>
        </div>

        <div class="item-description">
            <?php echo renderDescription($item['description']); ?>
        </div>

        <?php if ($item['type'] === 'book'): ?>
            <h3 class="section-title">Book Information</h3>
            <div class="book-details">
            <?php if (!$bookData): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <div class="detail-label">Note:</div>
                    <div>No book data found for this directory item.</div>
                </div>
            <?php else: ?>
                <?php if (!empty($bookData['author'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Author:</div>
                        <div><?php echo htmlspecialchars($bookData['author']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['publisher'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Publisher:</div>
                        <div><?php echo htmlspecialchars($bookData['publisher']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['publication_date'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Published:</div>
                        <div><?php echo htmlspecialchars(formatDate($bookData['publication_date'])); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['isbn']) || !empty($bookData['isbn13'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">ISBN:</div>
                        <div><?php echo htmlspecialchars(!empty($bookData['isbn13']) ? $bookData['isbn13'] : $bookData['isbn']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['page_count'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Pages:</div>
                        <div><?php echo htmlspecialchars($bookData['page_count']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['genre'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Genre:</div>
                        <div><?php echo htmlspecialchars($bookData['genre']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['series'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Series:</div>
                        <div><?php echo htmlspecialchars($bookData['series']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['age_range'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Age Range:</div>
                        <div><?php echo htmlspecialchars($bookData['age_range']); ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($bookData['reading_level'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Reading Level:</div>
                        <div><?php echo htmlspecialchars($bookData['reading_level']); ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>

            <?php if ($bookData && !empty($bookData['purchase_links_array']) && is_array($bookData['purchase_links_array'])): ?>
                <div>
                    <div class="detail-label">Where to Buy:</div>
                    <div class="purchase-links">
                        <?php foreach ($bookData['purchase_links_array'] as $store => $url): ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" class="purchase-link" target="_blank"><?php echo htmlspecialchars(ucfirst($store)); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h3 class="section-title">Contact Information</h3>
        <div class="item-details">
            <?php if (!empty($item['contact_email'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Email:</div>
                    <div><?php echo htmlspecialchars($item['contact_email']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['contact_phone'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Phone:</div>
                    <div><?php echo htmlspecialchars($item['contact_phone']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['address'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Address:</div>
                    <div><?php echo nl2br(htmlspecialchars($item['address'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['price_range'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Price Range:</div>
                    <div><?php echo htmlspecialchars($item['price_range']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($item['type'] === 'book'): ?>
            <!-- Reviews Section -->
            <h3 class="section-title">Reviews</h3>
            <div class="reviews-section">
                <div class="reviews-header">
                    <div class="reviews-summary">
                        <?php if ($reviewCount > 0): ?>
                            <div class="average-rating"><?php echo number_format($averageRating * 5, 1); ?></div>
                            <div>
                                <?php echo renderStarRating($averageRating, 5, 'md'); ?>
                                <div class="review-count"><?php echo $reviewCount; ?> <?php echo $reviewCount === 1 ? 'review' : 'reviews'; ?></div>
                            </div>
                        <?php else: ?>
                            <div>No reviews yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php if ($reviewCount > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonymous'); ?>
                                        <?php if (!empty($review['reviewer_age'])): ?>
                                            <span class="reviewer-age">(Age <?php echo htmlspecialchars($review['reviewer_age']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if (!empty($review['source_name'])): ?>
                                            <span class="review-source"><?php echo htmlspecialchars($review['source_name']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($review['review_date'])): ?>
                                            <span class="review-date"><?php echo formatDate($review['review_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php echo renderStarRating($review['rating_normalised'], 5, 'sm'); ?>
                                    <span style="margin-left: 10px; font-size: 14px; color: #666;">
                                        <?php echo htmlspecialchars($review['original_rating'] ?? number_format($review['rating_normalised'] * 5, 1) . '/5'); ?>
                                    </span>
                                </div>
                                <?php if (!empty($review['review_text'])): ?>
                                    <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <p>No reviews available for this book.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($item['website_url'])): ?>
            <div class="item-cta">
                <a href="<?php echo htmlspecialchars($item['website_url']); ?>" target="_blank">Visit Website</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error-message">
            <h2>No Directory Item Found</h2>
            <p>The requested directory item could not be found.</p>
        </div>
    <?php endif; ?>
</body>
</html>
