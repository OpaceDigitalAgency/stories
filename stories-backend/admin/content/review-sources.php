<?php
/**
 * Review Sources Admin Page
 *
 * This page provides an interface for managing review sources.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include admin functions
require_once '../includes/admin-functions.php';

// Include components
require_once '../includes/enhanced-table-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/pagination-component.php';

// Set page variables for header
$pageTitle = 'Review Sources';
$currentPage = 'review-sources';

// Process form submissions
$message = '';
$messageType = '';

try {
    // Sources tab pagination
    $sourcesPage = isset($_GET['sources_page']) ? max(1, intval($_GET['sources_page'])) : 1;
    $sourcesPerPage = isset($_GET['sources_per_page']) ? intval($_GET['sources_per_page']) : 10;
    
    // Log the parameters for debugging
    error_log("Sources Page: $sourcesPage, Sources Per Page: $sourcesPerPage");
    
    // Calculate offsets
    $sourcesOffset = ($sourcesPage - 1) * $sourcesPerPage;
    $sourcesOffset = max(0, $sourcesOffset);

    // Initialize standard per page values
    $validPerPageValues = [10, 25, 50, 100];

    // Get all review sources
    $sourcesStmt = $db->prepare("
        SELECT id, name, url, is_third_party
        FROM review_sources
        ORDER BY name ASC
    ");
    $sourcesStmt->execute();
    $reviewSources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

    // If no review sources exist, create default ones
    if (empty($reviewSources)) {
        $db->beginTransaction();

        // Create default review sources
        $defaultSources = [
            ['Stories from the Web', 'https://storiesfromtheweb.org', 0],
            ['Google Books', 'https://books.google.com', 1],
            ['Open Library', 'https://openlibrary.org', 1],
            ['Goodreads', 'https://goodreads.com', 1],
            ['Amazon', 'https://amazon.com', 1]
        ];

        $insertStmt = $db->prepare("
            INSERT INTO review_sources (name, url, is_third_party, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");

        foreach ($defaultSources as $source) {
            $insertStmt->execute($source);
        }

        $db->commit();

        // Fetch the newly created sources
        $sourcesStmt->execute();
        $reviewSources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

        $message = 'Default review sources created successfully.';
        $messageType = 'success';
    }

    // Get the total number of sources
    $sourcesCount = count($reviewSources);
    
    // Add total items as a valid per_page value
    if (!in_array($sourcesCount, $validPerPageValues)) {
        $validPerPageValues[] = $sourcesCount;
    }

    // Calculate pagination
    $totalSourcesPages = ceil($sourcesCount / $sourcesPerPage);

    // Paginate the sources
    $paginatedSources = array_slice($reviewSources, $sourcesOffset, $sourcesPerPage);

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $messageType = 'danger';
}

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Review Sources</h4>
                        <div>
                            <a href="book-import-tool.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Import Tool
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p>Manage sources for scraping book reviews.</p>

                    <?php
                    // Prepare table data for review sources
                    $tableData = [];
                    foreach ($paginatedSources as $source) {
                        $tableData[] = [
                            'id' => $source['id'],
                            'name' => htmlspecialchars($source['name']),
                            'url' => htmlspecialchars($source['url']),
                            'type' => $source['is_third_party'] ? 'Third-party' : 'Internal',
                            'actions' => '<button class="btn btn-sm btn-primary edit-source-btn" ' .
                                       'data-source-id="' . $source['id'] . '" ' .
                                       'data-source-name="' . htmlspecialchars($source['name']) . '" ' .
                                       'data-source-url="' . htmlspecialchars($source['url']) . '" ' .
                                       'data-source-type="' . $source['is_third_party'] . '">' .
                                       '<i class="fas fa-edit"></i> Edit</button>'
                        ];
                    }

                    // Define table columns - include actions in the columns
                    $columns = [
                        'name' => 'Name',
                        'url' => 'URL',
                        'type' => 'Type',
                        'actions' => 'Actions'
                    ];

                    // Render enhanced table
                    // Disable enhanced table's built-in pagination
                    renderEnhancedTable(
                        $tableData,
                        $columns,
                        'source',
                        'sources-table',
                        [
                            'showCheckboxes' => true,
                            'showActions' => false, // Don't show the last actions column
                            'actions' => ['edit', 'delete'],
                            'bulkActions' => ['delete', 'toggle'],
                            'htmlFields' => ['actions'],
                            'showPagination' => false,
                            'showItemsPerPage' => false
                        ]
                    );
                    ?>
                    <?php
                    // Make sure we have valid values
                    if ($sourcesCount > 0 && $sourcesPerPage > 0) {
                        // Render pagination for sources table
                        renderPagination($sourcesCount, $sourcesPerPage, $sourcesPage, 5, [
                            'pageParam' => 'sources_page',
                            'perPageParam' => 'sources_per_page',
                            'validPerPageValues' => [10, 25, 50, 100],
                            'perPageLabel' => 'per page',
                            'showAllLabel' => 'Show All'
                        ]);
                    }
                    ?>

                    <button class="btn btn-success" id="addSourceBtn">
                        <i class="fas fa-plus"></i> Add New Source
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Source Modal -->
<div class="modal fade" id="sourceModal" tabindex="-1" role="dialog" aria-labelledby="sourceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sourceModalLabel">Add/Edit Review Source</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="sourceForm" method="post" action="book-import-source.php">
                    <input type="hidden" id="sourceId" name="source_id" value="">

                    <div class="form-group">
                        <label for="sourceName">Source Name</label>
                        <input type="text" class="form-control" id="sourceName" name="source_name" required>
                    </div>

                    <div class="form-group">
                        <label for="sourceUrl">Source URL</label>
                        <input type="url" class="form-control" id="sourceUrl" name="source_url" required>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="isThirdParty" name="is_third_party" value="1">
                            <label class="custom-control-label" for="isThirdParty">Third-party Source</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSourceBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add Source Button
    $('#addSourceBtn').click(function() {
        $('#sourceModalLabel').text('Add Review Source');
        $('#sourceId').val('');
        $('#sourceName').val('');
        $('#sourceUrl').val('');
        $('#isThirdParty').prop('checked', true);
        $('#sourceModal').modal('show');
    });

    // Edit Source Button
    $('.edit-source-btn').click(function() {
        $('#sourceModalLabel').text('Edit Review Source');
        $('#sourceId').val($(this).data('source-id'));
        $('#sourceName').val($(this).data('source-name'));
        $('#sourceUrl').val($(this).data('source-url'));
        $('#isThirdParty').prop('checked', $(this).data('source-type') == 1);
        $('#sourceModal').modal('show');
    });

    // Save Source Button
    $('#saveSourceBtn').click(function() {
        $('#sourceForm').submit();
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>