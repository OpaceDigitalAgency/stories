<!-- Data Enrichment Modal -->
<style>
/* Pale RAG color backgrounds for enrichment fields */
.bg-light-success {
    background-color: #d4edda !important; /* Pale green */
}
.bg-light-warning {
    background-color: #fff3cd !important; /* Pale amber */
}
.bg-light-danger {
    background-color: #f8d7da !important; /* Pale red */
}
.border-success {
    border: 2px solid #28a745 !important;
}
.border-warning {
    border: 2px solid #ffc107 !important;
}
.border-danger {
    border: 2px solid #dc3545 !important;
}
.enrichment-field {
    border-radius: 8px;
    transition: all 0.2s ease;
}
.enrichment-field:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="modal fade" id="dataEnrichmentModal" tabindex="-1" role="dialog" aria-labelledby="dataEnrichmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <div class="modal-title-container">
                    <h4 class="modal-title mb-1" id="dataEnrichmentModalLabel">
                        <i class="fas fa-database"></i> <span id="enrichment-book-title">Enrich Book Data</span>
                    </h4>
                    <div class="book-identifiers text-light" id="enrichment-book-identifiers" style="display: none;">
                        <small>
                            <strong>ISBN-13:</strong> <span id="enrichment-isbn13">-</span> |
                            <strong>ISBN-10:</strong> <span id="enrichment-isbn10">-</span>
                            <br>
                            <strong>Verified:</strong> <span class="text-muted" id="enrichment-isbn-converted" style="font-size: 0.85em;">-</span>
                        </small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="enrichment-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Searching for book data...</span>
                    </div>
                    <p class="mt-2">Searching Google Books and OpenLibrary...</p>
                    <div class="mt-3">
                        <span id="google-books-status-badge" class="badge badge-info mr-2">Google Books - Checking...</span>
                        <span id="open-library-status-badge" class="badge badge-info mr-2">OpenLibrary - Checking...</span>
                        <span id="goodreads-status-badge" class="badge badge-info mr-2">Goodreads - Checking...</span>
                        <span id="amazon-status-badge" class="badge badge-info mr-2">Amazon - Checking...</span>
                    </div>
                </div>

                <!-- Results Container -->
                <div id="enrichment-results" style="display: none;">
                    <!-- Match Confidence -->
                    <div class="alert alert-info mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <strong>Match Confidence:</strong>
                                <span id="confidence-score" class="badge badge-primary">0%</span>
                                <span id="confidence-details" class="small text-muted ml-2"></span>
                            </div>
                            <div class="col-md-4 text-right">
                                <span id="sources-checked" class="small text-muted"></span>
                            </div>
                        </div>

                        <!-- ISBN Validation Status -->
                        <div id="isbn-validation-status" class="mt-2" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>ISBN Validation:</strong>
                                    <span id="isbn-status-badge"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Goodreads Check:</strong>
                                    <span id="goodreads-status-badge"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrichment Fields -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-edit"></i> Available Data Enrichments
                                    <small class="text-muted ml-2">Select fields to update</small>
                                </span>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-fields">
                                        <i class="fas fa-check-square"></i> Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="deselect-all-fields">
                                        <i class="fas fa-square"></i> Deselect All
                                    </button>
                                </div>
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="enrichment-form">
                                <div class="row" id="enrichment-fields">
                                    <!-- Fields will be populated dynamically -->
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Amazon Buying Options -->
                    <div class="mt-4">
                        <h5>Amazon Buying Options</h5>
                        <div class="amazon-data-container text-muted">Click “Enrich” to load Amazon info.</div>
                    </div>

                    <!-- No Data Found -->
                    <div id="no-enrichment-data" style="display: none;" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No additional data found.</strong>
                        <p class="mb-0">We couldn't find enrichment data from Google Books or OpenLibrary for this book.</p>
                    </div>
                </div>

                <!-- Error State -->
                <div id="enrichment-error" style="display: none;" class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Error occurred while searching for data.</strong>
                    <p class="mb-0" id="error-message"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="fix-all-btn">
                    <i class="fas fa-magic"></i> Fix All
                </button>
                <button type="button" class="btn btn-primary" id="apply-enrichment-btn" disabled>
                    <i class="fas fa-save"></i> Apply Selected Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.enrichment-field {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #f8f9fa;
    min-height: 200px; /* Ensure consistent height */
    word-wrap: break-word; /* Handle long text */
}

.enrichment-field.selected {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.field-confidence {
    font-size: 0.875rem;
}

.confidence-high { color: #28a745; }
.confidence-medium { color: #ffc107; }
.confidence-low { color: #dc3545; }

.current-value {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.new-value {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 0.25rem;
    padding: 0.5rem;
}

.field-checkbox {
    transform: scale(1.2);
    margin-right: 0.5rem;
}

.source-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

#confidence-score.badge-success { background-color: #28a745; }
#confidence-score.badge-warning { background-color: #ffc107; }
#confidence-score.badge-danger { background-color: #dc3545; }

/* Styling for fields where database values match suggestions exactly */
.enrichment-field.exact-match {
    background-color: #f8f9fa !important;
    opacity: 0.6;
    border-color: #dee2e6 !important;
}

.enrichment-field.exact-match .field-checkbox {
    opacity: 0.5;
}

.enrichment-field.exact-match .form-check-label {
    color: #6c757d !important;
}

.enrichment-field.exact-match .current-value,
.enrichment-field.exact-match .new-value {
    background-color: #e9ecef !important;
    border-color: #ced4da !important;
    color: #6c757d !important;
}

.enrichment-field.exact-match::before {
    content: "✓ Matches Database";
    position: absolute;
    top: 5px;
    right: 10px;
    font-size: 0.75rem;
    color: #28a745;
    font-weight: bold;
    background: white;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #28a745;
    z-index: 10;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Ensure enrichment fields have enough padding to avoid overlap */
.enrichment-field.exact-match {
    padding-top: 25px !important; /* Extra space for the label */
    padding-right: 140px !important; /* Space for the label on the right */
}

/* Styling for disabled fields to make them obviously non-interactive */
.enrichment-field.disabled-field {
    background-color: #f8f9fa !important;
    opacity: 0.5;
    border-color: #dee2e6 !important;
    cursor: not-allowed;
}

.enrichment-field.disabled-field .field-checkbox {
    opacity: 0.3;
    cursor: not-allowed;
}

.enrichment-field.disabled-field .form-check-label {
    color: #6c757d !important;
    cursor: not-allowed;
}

.enrichment-field.disabled-field .badge {
    opacity: 0.6;
}

.enrichment-field.disabled-field .mt-2 {
    background-color: #e9ecef !important;
    border-color: #ced4da !important;
    color: #6c757d !important;
}

.enrichment-field.disabled-field:hover {
    box-shadow: none !important;
}

/* CRITICAL CSS FIXES for width and text wrapping issues */
.enrichment-field .form-check-label {
    white-space: normal !important; /* Allow text wrapping */
    word-break: break-word; /* Break long words */
    line-height: 1.4; /* Better line spacing */
    max-width: 100%; /* Prevent overflow */
}

.enrichment-field .current-value,
.enrichment-field .new-value {
    white-space: normal !important;
    word-break: break-word;
    max-width: 100%;
    overflow-wrap: break-word;
}

/* Ensure proper spacing for field content */
.enrichment-field .mt-2 {
    margin-top: 0.75rem !important;
}

.enrichment-field .p-2 {
    padding: 0.75rem !important;
}

/* CRITICAL FIX: Override Bootstrap's 50% width constraint for 3-column layout */
@media (min-width: 768px) {
    #dataEnrichmentModal .col-md-6,
    #enrichment-fields .col-md-6,
    .modal-body .col-md-6 {
        -ms-flex: 0 0 33.333333% !important;
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
    }
}

@media (max-width: 991px) {
    .enrichment-field {
        min-height: 150px; /* Smaller height on mobile */
    }
}

/* Fix for source labels and confidence badges */
.enrichment-field .badge {
    white-space: nowrap; /* Keep badges on one line */
    margin: 0.125rem;
}

.enrichment-field .source-badge {
    display: inline-block;
    margin-bottom: 0.25rem;
}
</style>

<?php
// Only include the scripts once per page load using static variables
static $dataEnrichmentScriptsLoaded = false;
if (!$dataEnrichmentScriptsLoaded) {
    $dataEnrichmentScriptsLoaded = true;
    // Use absolute paths to avoid path resolution issues with cache busting
    $cacheBuster = '?v=' . filemtime(__DIR__ . '/../../assets/js/data-enrichment-modal.js');
    echo '<script src="/admin/assets/js/data-enrichment-modal.js' . $cacheBuster . '"></script>';
    echo '<script src="/admin/assets/js/data-enrichment-helpers.js' . $cacheBuster . '"></script>';
    echo '<script src="/admin/assets/js/data-enrichment-utils.js' . $cacheBuster . '"></script>';
}
?>

<script>
// Minimal page-specific initialization that can't be moved to external files

// All JavaScript functionality has been moved to external files for better performance

// Amazon integration is now working - debugging code removed
</script>
