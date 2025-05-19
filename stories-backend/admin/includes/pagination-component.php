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
function renderPagination($totalItems, $itemsPerPage, $currentPage = 1, $visiblePages = 5, $options = []) {
    // Set default options
    $defaultOptions = [
        'pageParam' => 'page',
        'perPageParam' => 'per_page'
    ];
    $options = array_merge($defaultOptions, $options);
    // Calculate total pages
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Always show pagination for the dropdown, even if there's only one page
    // This ensures users can switch back from "Show All" to paginated view

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

    // Get current URL and build base query parameters
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $queryParams = $_GET;

    // Remove old pagination parameters
    unset($queryParams['page'], $queryParams[$options['pageParam']], $queryParams[$options['perPageParam']]);

    // Set required parameters
    $queryParams['tab'] = $options['tab'] ?? $queryParams['tab'] ?? 'existing';
    $queryParams[$options['perPageParam']] = $itemsPerPage;

    // Function to generate page URL
    $getPageUrl = function($page) use ($currentUrl, $queryParams, $options) {
        $queryParams[$options['pageParam']] = $page;

        // Always ensure tab parameter is included
        if (!isset($queryParams['tab']) && isset($options['tab'])) {
            $queryParams['tab'] = $options['tab'];
        }

        return $currentUrl . '?' . http_build_query($queryParams);
    };

    // Render pagination
    ?>
    <div class="pagination-container">
        <div class="pagination-info">
            <?php if ($itemsPerPage >= $totalItems): ?>
                Showing all <?php echo $totalItems; ?> items
            <?php else: ?>
                Showing <?php echo ($currentPage - 1) * $itemsPerPage + 1; ?> to
                <?php echo min($currentPage * $itemsPerPage, $totalItems); ?> of
                <?php echo $totalItems; ?> items
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between" style="margin-top: 10px;">
            <!-- Pagination links -->
            <div class="d-flex align-items-center">
                <ul class="pagination mb-0">
                    <!-- First page link -->
                    <?php if ($currentPage > 1 && $itemsPerPage < $totalItems): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $getPageUrl(1); ?>" aria-label="First page">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $currentUrl; ?>?page=1&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>" aria-label="First page">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Previous page link -->
                    <?php if ($currentPage > 1 && $itemsPerPage < $totalItems): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $getPageUrl($currentPage - 1); ?>" aria-label="Previous page">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $currentUrl; ?>?page=1&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>" aria-label="Previous page">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Page number links -->
                    <?php if ($itemsPerPage < $totalItems): ?>
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
                    <?php else: ?>
                        <!-- When showing all items, show first few pages -->
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $currentUrl; ?>?page=1&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>">1</a>
                        </li>
                        <?php if ($totalPages > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $currentUrl; ?>?page=2&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>">2</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($totalPages > 2): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $currentUrl; ?>?page=3&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>">3</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Next page link -->
                    <?php if ($currentPage < $totalPages && $itemsPerPage < $totalItems): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $getPageUrl($currentPage + 1); ?>" aria-label="Next page">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $currentUrl; ?>?page=2&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>" aria-label="Next page">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Last page link -->
                    <?php if ($currentPage < $totalPages && $itemsPerPage < $totalItems): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $getPageUrl($totalPages); ?>" aria-label="Last page">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $currentUrl; ?>?page=<?php echo max(1, $totalPages); ?>&per_page=10&tab=<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>" aria-label="Last page">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Items per page selector -->
            <div class="items-per-page" style="margin-left: 10px;">
                <form method="GET" action="<?php echo $currentUrl; ?>" class="d-flex align-items-center pagination-form">
                    <!-- Preserve existing query parameters -->
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key !== $options['pageParam'] && $key !== $options['perPageParam']): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Always include the tab parameter -->
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($options['tab'] ?? ($_GET['tab'] ?? 'existing')); ?>">

                    <select name="<?php echo $options['perPageParam']; ?>" id="per-page" class="form-control form-control-sm per-page-select" aria-label="Items per page">
                        <?php
                        // Use validPerPageValues from options if provided, otherwise use default values
                        $perPageValues = isset($options['validPerPageValues']) ? $options['validPerPageValues'] : [10, 25, 50, 100];

                        // Remove the total items value from the array to add it separately at the end
                        if (($key = array_search($totalItems, $perPageValues)) !== false) {
                            unset($perPageValues[$key]);
                        }

                        // Sort the values
                        sort($perPageValues);

                        // Add options for each per page value
                        foreach ($perPageValues as $option):
                            if ($option < $totalItems): // Only show options less than total items
                        ?>
                            <option value="<?php echo $option; ?>" <?php echo $itemsPerPage == $option ? 'selected' : ''; ?>>
                                <?php echo $option; ?> <?php echo isset($options['perPageLabel']) ? $options['perPageLabel'] : 'per page'; ?>
                            </option>
                        <?php
                            endif;
                        endforeach;
                        ?>
                        <option value="<?php echo $totalItems; ?>" <?php echo $itemsPerPage == $totalItems ? 'selected' : ''; ?>>
                            <?php echo isset($options['showAllLabel']) ? $options['showAllLabel'] : 'Show All'; ?> (<?php echo $totalItems; ?>)
                        </option>
                    </select>
                </form>
            </div>
            <!-- JavaScript for this is handled by tab-state-handler.js -->
        </div>
    </div>

    <style>
    /* Custom styles for pagination */
    .pagination-container {
        display: flex;
        flex-direction: column;
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        margin-bottom: 0 !important;
    }

    /* Override the form padding-bottom that's causing alignment issues */
    form .pagination-container {
        padding-bottom: 1rem !important;
        margin-bottom: 0 !important;
    }

    /* Specifically target the pagination form to override the global form padding-bottom: 70px !important rule */
    form.pagination-form {
        padding-bottom: 0 !important;
        margin-bottom: 0 !important;
    }

    .pagination-info {
        margin-bottom: 0.5rem;
    }

    .pagination {
        display: flex;
        align-items: center;
        margin-bottom: 0 !important;
    }

    .pagination .page-link {
        padding: 0.375rem 0.75rem;
    }

    .items-per-page {
        display: flex;
        align-items: center;
    }

    .items-per-page select {
        min-width: 120px;
    }

    /* Fix for the pagination container to ensure it's always visible */
    .d-flex.flex-wrap.align-items-center.justify-content-between {
        display: flex !important;
    }

    @media (min-width: 768px) {
        .pagination-container > div {
            flex-direction: row;
            justify-content: space-between;
        }
    }
    </style>
    <?php
}



