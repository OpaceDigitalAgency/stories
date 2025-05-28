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
            <div class="modal-header">
                <h5 class="modal-title" id="dataEnrichmentModalLabel">
                    <i class="fas fa-database"></i> Enrich Book Data
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
                        <h5>Amazon Buying Options <span id="amazon-status-badge"></span></h5>
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
</style>

<?php
// Only include the scripts once per page load using static variables
static $dataEnrichmentScriptsLoaded = false;
if (!$dataEnrichmentScriptsLoaded) {
    $dataEnrichmentScriptsLoaded = true;
    // Determine the correct path based on current directory
    $isContentDir = strpos($_SERVER['SCRIPT_FILENAME'], '/content/') !== false;
    $scriptBasePath = $isContentDir ? '../../../admin/assets/js/' : '../assets/js/';

    echo '<script src="' . $scriptBasePath . 'data-enrichment-modal.js"></script>';
    echo '<script src="' . $scriptBasePath . 'data-enrichment-helpers.js"></script>';
    echo '<script src="' . $scriptBasePath . 'data-enrichment-utils.js"></script>';
}
?>

<script>
// Minimal page-specific initialization that can't be moved to external files

// All JavaScript functionality has been moved to external files for better performance














</script>
