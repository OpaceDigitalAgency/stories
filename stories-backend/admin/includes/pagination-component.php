<?php

/**
 * Pagination Component
 *
 * A reusable pagination component for content listing pages.
 *
 * Usage:
 * include '../includes/pagination-component.php';
 * renderPagination($totalItems, $itemsPerPage, $currentPage);
 */

/**
 * Renders a pagination component
 *
 * @param int $totalItems Total number of items
 * @param int $itemsPerPage Number of items per page
 * @param int $currentPage Current page number
 * @param int $visiblePages Number of visible page links (odd number recommended)
 * @return void
 */
function renderPagination($totalItems, $itemsPerPage, $currentPage = 1, $visiblePages = 5) {
    // Calculate total pages
    $totalPages = ceil($totalItems / $itemsPerPage);

    // If only one page, don't show pagination
    if ($totalPages <= 1) {
        return;
    }

    // Ensure current page is valid
    $currentPage = max(1, min($currentPage, $totalPages));

    // Calculate start and end page numbers for visible range
    $halfVisible = floor($visiblePages / 2);
    $startPage = max(1, $currentPage - $halfVisible);
    $endPage = min($totalPages, $startPage + $visiblePages - 1);

    // Adjust start page if end page is at maximum
    if ($endPage == $totalPages) {
        $startPage = max(1, $endPage - $visiblePages + 1);
    }

    // Get current URL and query parameters
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;

    // Debug log for troubleshooting
    error_log("Pagination: Current URL: " . $currentUrl);
    error_log("Pagination: Query params: " . print_r($queryParams, true));

    // Make sure we preserve all existing query parameters
    foreach ($_GET as $key => $value) {
        if ($key !== 'page') {
            $queryParams[$key] = $value;
        }
    }

    // Function to generate page URL
    $getPageUrl = function($page) use ($currentUrl, $queryParams) {
        $params = $queryParams;
        $params['page'] = $page;
        return $currentUrl . '?' . http_build_query($params);
    };

    // Render pagination
    ?>
    <nav aria-label="Page navigation" class="pagination-container">
        <div class="pagination-info">
            Showing <?php echo ($currentPage - 1) * $itemsPerPage + 1; ?> to
            <?php echo min($currentPage * $itemsPerPage, $totalItems); ?> of
            <?php echo $totalItems; ?> items
        </div>

        <ul class="pagination">
            <!-- First page link -->
            <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $getPageUrl(1); ?>" aria-label="First page">
                        <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&laquo;&laquo;</span>
                </li>
            <?php endif; ?>

            <!-- Previous page link -->
            <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $getPageUrl($currentPage - 1); ?>" aria-label="Previous page">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            <?php endif; ?>

            <!-- Page number links -->
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <li class="page-item active" aria-current="page">
                        <span class="page-link"><?php echo $i; ?></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo $getPageUrl($i); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Next page link -->
            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $getPageUrl($currentPage + 1); ?>" aria-label="Next page">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            <?php endif; ?>

            <!-- Last page link -->
            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo $getPageUrl($totalPages); ?>" aria-label="Last page">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">&raquo;&raquo;</span>
                </li>
            <?php endif; ?>
        </ul>

        <!-- Items per page selector -->
        <div class="items-per-page">
            <form method="GET" action="<?php echo $currentUrl; ?>" class="d-flex align-items-center gap-2">
                <!-- Preserve existing query parameters -->
                <?php foreach ($queryParams as $key => $value): ?>
                    <?php if ($key !== 'page' && $key !== 'per_page'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <label for="per-page" class="form-label mb-0">Items per page:</label>
                <select name="per_page" id="per-page" class="form-control form-control-sm" onchange="this.form.submit()">
                    <?php foreach ([10, 25, 50, 100] as $option): ?>
                        <option value="<?php echo $option; ?>" <?php echo $itemsPerPage == $option ? 'selected' : ''; ?>>
                            <?php echo $option; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </nav>
    <?php
}



