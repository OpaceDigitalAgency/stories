<!DOCTYPE html>
<html>
<head>
    <title>Discovery Debug Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h1>Discovery Debug Test</h1>
    
    <!-- Discovery Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>URL-based Discovery</h5>
        </div>
        <div class="card-body">
            <form id="discoveryForm">
                <div class="form-group mb-3">
                    <label for="discovery_url">Website URL</label>
                    <input type="url" class="form-control" id="discovery_url" name="discovery_url"
                           value="https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/" required>
                </div>
                
                <div class="form-group mb-3">
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
                
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="auto_enrich" name="auto_enrich">
                        <label class="form-check-label" for="auto_enrich">
                            Auto-enrich with APIs
                        </label>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="import_to_db" name="import_to_db">
                        <label class="form-check-label" for="import_to_db">
                            Import directly to database
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" id="startDiscoveryBtn">
                    <i class="fas fa-search"></i> Start Discovery
                </button>
            </form>
        </div>
    </div>
    
    <!-- Progress Section -->
    <div id="progressSection" class="card mb-4" style="display: none;">
        <div class="card-header">
            <h5>Discovery Progress</h5>
        </div>
        <div class="card-body">
            <div class="progress mb-3">
                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    0%
                </div>
            </div>
            <p id="progressMessage">Initializing...</p>
            <div id="currentBookInfo" class="alert alert-info" style="display: none;">
                <strong>Processing:</strong> <span id="currentBookTitle"></span>
            </div>
            <div class="mt-3">
                <button id="cancelButton" class="btn btn-warning" style="display: none;" onclick="cancelDiscovery()">
                    <i class="fas fa-stop"></i> Cancel Process
                </button>
                <button id="showResultsButton" class="btn btn-info" style="display: none;" onclick="showPartialResults()">
                    <i class="fas fa-table"></i> Show Results So Far
                </button>
            </div>
        </div>
    </div>
    
    <!-- Results Section -->
    <div id="resultsSection" class="card" style="display: none;">
        <div class="card-header">
            <h5>Discovery Results</h5>
        </div>
        <div class="card-body">
            <div id="summaryInfo" class="alert alert-success mb-4"></div>
            <div id="tableContainer"></div>
        </div>
    </div>
    
    <!-- Debug Console -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Debug Console</h5>
        </div>
        <div class="card-body">
            <div id="debugConsole" style="background: #f8f9fa; padding: 10px; font-family: monospace; height: 200px; overflow-y: auto;"></div>
        </div>
    </div>
</div>

<script>
// Debug logging function
function debugLog(message) {
    console.log(message);
    const debugConsole = document.getElementById('debugConsole');
    const timestamp = new Date().toLocaleTimeString();
    debugConsole.innerHTML += `[${timestamp}] ${message}\n`;
    debugConsole.scrollTop = debugConsole.scrollHeight;
}

// Discovery variables
let discoveryBooks = [];
let discoveryCurrentIndex = 0;
let discoveryAutoEnrich = false;
let discoveryImportToDb = false;
let discoveryTotalBooks = 0;
let discoveryProcessedBooks = 0;
let discoveryImportedBooks = 0;
let discoveryErrorBooks = 0;
let discoveryCancelled = false;

$(document).ready(function() {
    debugLog('Document ready - initializing discovery system');
    
    $('#discoveryForm').on('submit', function(e) {
        e.preventDefault();
        debugLog('Form submitted - starting discovery');
        startDiscovery();
    });
});

async function startDiscovery() {
    debugLog('startDiscovery() called');
    
    const form = document.getElementById('discoveryForm');
    const formData = new FormData(form);
    
    discoveryAutoEnrich = document.getElementById('auto_enrich').checked;
    discoveryImportToDb = document.getElementById('import_to_db').checked;
    
    debugLog(`Auto-enrich: ${discoveryAutoEnrich}, Import to DB: ${discoveryImportToDb}`);
    
    // Show progress section
    debugLog('Showing progress section');
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('resultsSection').style.display = 'none';
    
    // Reset counters
    discoveryCurrentIndex = 0;
    discoveryProcessedBooks = 0;
    discoveryImportedBooks = 0;
    discoveryErrorBooks = 0;
    discoveryCancelled = false;
    
    // Show cancel button
    document.getElementById('cancelButton').style.display = 'inline-block';
    document.getElementById('showResultsButton').style.display = 'none';
    
    // Disable form
    $('#startDiscoveryBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Discovering...');
    
    try {
        // Step 1: Discover all books
        updateDiscoveryProgress(0, 'Discovering books from website...');
        
        const discoverData = new FormData();
        discoverData.append('action', 'discover_all');
        discoverData.append('url', formData.get('discovery_url'));
        discoverData.append('age_filter', formData.get('age_filter'));
        
        debugLog(`Making AJAX request to book-discovery-ajax.php with URL: ${formData.get('discovery_url')}`);
        
        const response = await fetch('book-discovery-ajax.php', {
            method: 'POST',
            body: discoverData
        });
        
        debugLog(`Response status: ${response.status}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        debugLog(`Response received: ${JSON.stringify(result)}`);
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        discoveryBooks = result.books;
        discoveryTotalBooks = result.total;
        
        debugLog(`Found ${discoveryTotalBooks} books`);
        
        if (discoveryTotalBooks === 0) {
            updateDiscoveryProgress(100, 'No books found matching your criteria.');
            $('#startDiscoveryBtn').prop('disabled', false).html('<i class="fas fa-search"></i> Start Discovery');
            return;
        }
        
        updateDiscoveryProgress(10, `Found ${discoveryTotalBooks} books. Starting processing...`);
        
        // Step 2: Process books one by one
        await processDiscoveryBooks();
        
        // Step 3: Show results
        showDiscoveryResults();
        
    } catch (error) {
        debugLog(`Discovery error: ${error.message}`);
        console.error('Discovery error:', error);
        updateDiscoveryProgress(0, `Error: ${error.message}`, 'danger');
        $('#startDiscoveryBtn').prop('disabled', false).html('<i class="fas fa-search"></i> Start Discovery');
    }
}

async function processDiscoveryBooks() {
    debugLog(`Processing ${discoveryBooks.length} books`);
    
    for (let i = 0; i < discoveryBooks.length; i++) {
        if (discoveryCancelled) {
            debugLog('Process cancelled by user');
            updateDiscoveryProgress(getCurrentDiscoveryProgress(i), 'Process cancelled by user', 'warning');
            document.getElementById('currentBookInfo').style.display = 'none';
            document.getElementById('cancelButton').style.display = 'none';
            document.getElementById('showResultsButton').style.display = 'inline-block';
            $('#startDiscoveryBtn').prop('disabled', false).html('<i class="fas fa-search"></i> Start Discovery');
            return;
        }
        
        const book = discoveryBooks[i];
        const progress = 10 + ((i / discoveryBooks.length) * 80);
        
        debugLog(`Processing book ${i + 1}/${discoveryBooks.length}: ${book.title}`);
        
        // Show current book being processed
        document.getElementById('currentBookInfo').style.display = 'block';
        document.getElementById('currentBookTitle').textContent = book.title || 'Unknown Title';
        
        updateDiscoveryProgress(progress, `Processing book ${i + 1} of ${discoveryBooks.length}: ${book.title}`);
        
        discoveryProcessedBooks++;
        
        // Small delay to show progress
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    // Hide current book info and cancel button
    document.getElementById('currentBookInfo').style.display = 'none';
    document.getElementById('cancelButton').style.display = 'none';
    updateDiscoveryProgress(100, 'Processing complete!', 'success');
    $('#startDiscoveryBtn').prop('disabled', false).html('<i class="fas fa-search"></i> Start Discovery');
}

function updateDiscoveryProgress(percentage, message, type = 'info') {
    debugLog(`Progress: ${percentage}% - ${message}`);
    
    const progressBar = document.getElementById('progressBar');
    const progressMessage = document.getElementById('progressMessage');
    
    progressBar.style.width = percentage + '%';
    progressBar.setAttribute('aria-valuenow', percentage);
    progressBar.textContent = Math.round(percentage) + '%';
    
    progressMessage.textContent = message;
    progressMessage.className = `alert alert-${type}`;
}

function getCurrentDiscoveryProgress(currentIndex) {
    return 10 + ((currentIndex / discoveryBooks.length) * 80);
}

function cancelDiscovery() {
    debugLog('Cancel button clicked');
    discoveryCancelled = true;
    updateDiscoveryProgress(getCurrentDiscoveryProgress(discoveryProcessedBooks), 'Cancelling process...', 'warning');
}

function showPartialResults() {
    debugLog('Showing partial results');
    // Implementation for partial results
}

function showDiscoveryResults() {
    debugLog('Showing final results');
    
    // Show results section
    document.getElementById('resultsSection').style.display = 'block';
    
    // Update summary
    const summaryInfo = document.getElementById('summaryInfo');
    let summaryHtml = `
        <h6>Summary:</h6>
        <ul class="mb-0">
            <li>Total books discovered: ${discoveryTotalBooks}</li>
            <li>Successfully processed: ${discoveryProcessedBooks}</li>
            <li>Errors: ${discoveryErrorBooks}</li>
        </ul>
    `;
    summaryInfo.innerHTML = summaryHtml;
    
    // Simple table for results
    let tableHtml = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Age Range</th>
                        <th>Year</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    discoveryBooks.forEach(book => {
        tableHtml += `
            <tr>
                <td>${book.title || ''}</td>
                <td>${book.author || ''}</td>
                <td>${book.age_range || ''}</td>
                <td>${book.year || ''}</td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
    `;
    
    document.getElementById('tableContainer').innerHTML = tableHtml;
}
</script>
</body>
</html>