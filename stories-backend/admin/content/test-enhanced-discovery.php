<?php
/**
 * Test Enhanced Discovery Process
 * 
 * Simple test to verify the enhanced discovery process works correctly
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'Test Enhanced Discovery';
$currentPage = 'book-import-tool';

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?php echo $pageTitle; ?></h1>
            
            <div class="card">
                <div class="card-body">
                    <h3>Test Enhanced Book Discovery Process</h3>
                    <p>This page tests the enhanced discovery process with real-time progress updates and API enrichment.</p>
                    
                    <div class="alert alert-info">
                        <h5>Test URLs:</h5>
                        <ul>
                            <li><strong>BookTrust (working):</strong> https://www.booktrust.org.uk/booklists/0-5-years/</li>
                            <li><strong>BookTrust (working):</strong> https://www.booktrust.org.uk/booklists/5-8-years/</li>
                            <li><strong>BookTrust (working):</strong> https://www.booktrust.org.uk/booklists/8-12-years/</li>
                        </ul>
                    </div>
                    
                    <form method="post" action="book-discovery-process-enhanced.php">
                        <div class="form-group">
                            <label for="discovery_url">Test URL</label>
                            <select class="form-control" id="discovery_url" name="discovery_url">
                                <option value="https://www.booktrust.org.uk/booklists/0-5-years/">BookTrust 0-5 years</option>
                                <option value="https://www.booktrust.org.uk/booklists/5-8-years/">BookTrust 5-8 years</option>
                                <option value="https://www.booktrust.org.uk/booklists/8-12-years/">BookTrust 8-12 years</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="age_filter">Age Range Filter</label>
                            <select class="form-control" id="age_filter" name="age_filter">
                                <option value="">All ages</option>
                                <option value="0-2">0-2 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="5-8">5-8 years</option>
                                <option value="8-12">8-12 years</option>
                                <option value="12+">12+ years</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_enrich" name="auto_enrich">
                                <label class="form-check-label" for="auto_enrich">
                                    <strong>Auto-enrich with APIs (SLOW - adds 3-5 minutes)</strong>
                                </label>
                                <small class="form-text text-muted">
                                    Automatically fetch ISBNs, publishers, and other data from Google Books and Open Library. WARNING: This significantly slows down the process.
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="import_to_db" name="import_to_db">
                                <label class="form-check-label" for="import_to_db">
                                    <strong>Import directly to database</strong>
                                </label>
                                <small class="form-text text-muted">
                                    Automatically import discovered books to your library (skip preview)
                                </small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Test Enhanced Discovery
                        </button>
                    </form>
                    
                    <hr>
                    
                    <h4>Features to Test:</h4>
                    <ul>
                        <li><strong>Real-time Progress Updates:</strong> Watch the progress overlay with step-by-step updates</li>
                        <li><strong>API Enrichment:</strong> Books will be enriched with Google Books and Open Library data</li>
                        <li><strong>Enhanced Table:</strong> Results displayed in enhanced table with bulk actions and pagination</li>
                        <li><strong>Age Filtering:</strong> Filter books by age range if specified</li>
                        <li><strong>Direct Import:</strong> Option to import books directly to database</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>Note:</strong> The enhanced discovery process includes:
                        <ul>
                            <li>Progress overlay with animated spinner</li>
                            <li>Step-by-step progress tracking (Discovery → Enrichment → Complete)</li>
                            <li>Real-time status updates</li>
                            <li>Enhanced table with sorting, searching, and bulk actions</li>
                            <li>API enrichment with Google Books and Open Library</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>