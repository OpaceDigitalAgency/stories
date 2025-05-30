<?php
/**
 * Modern AJAX Book Discovery Process Page
 * 
 * Features:
 * - AJAX-based processing with real-time progress for each book
 * - Enhanced table component matching book-validation.php
 * - One-by-one book processing with live updates
 * - Modern UX with proper progress indicators
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include components
require_once '../includes/enhanced-table-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/pagination-component.php';

// Set page variables
$pageTitle = 'Modern Book Discovery';
$currentPage = 'book-import-tool';

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1><?php echo $pageTitle; ?></h1>
            
            <!-- Discovery Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Book Discovery</h5>
                </div>
                <div class="card-body">
                    <form id="discoveryForm">
                        <div class="form-group">
                            <label for="discovery_url">Website URL</label>
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
                                    <strong>Auto-enrich with APIs (slower but more data)</strong>
                                </label>
                                <small class="form-text text-muted">
                                    Automatically fetch ISBNs, publishers, and other data from Google Books and Open Library
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
                                    Automatically import discovered books to your library
                                </small>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search"></i> Start Modern Discovery
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
                </div>
            </div>
            
            <!-- Results Section -->
            <div id="resultsSection" class="card" style="display: none;">
                <div class="card-header">
                    <h5>Discovery Results</h5>
                </div>
                <div class="card-body">
                    <div id="summaryInfo" class="alert alert-success mb-4"></div>
                    
                    <!-- Enhanced Table Container -->
                    <div id="tableContainer">
                        <!-- Table will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
class ModernBookDiscovery {
    constructor() {
        this.books = [];
        this.currentIndex = 0;
        this.autoEnrich = false;
        this.importToDb = false;
        this.totalBooks = 0;
        this.processedBooks = 0;
        this.importedBooks = 0;
        this.errorBooks = 0;
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        document.getElementById('discoveryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.startDiscovery();
        });
    }
    
    async startDiscovery() {
        const form = document.getElementById('discoveryForm');
        const formData = new FormData(form);
        
        this.autoEnrich = document.getElementById('auto_enrich').checked;
        this.importToDb = document.getElementById('import_to_db').checked;
        
        // Show progress section
        document.getElementById('progressSection').style.display = 'block';
        document.getElementById('resultsSection').style.display = 'none';
        
        // Reset counters
        this.currentIndex = 0;
        this.processedBooks = 0;
        this.importedBooks = 0;
        this.errorBooks = 0;
        
        try {
            // Step 1: Discover all books
            this.updateProgress(0, 'Discovering books from website...');
            
            const discoverData = new FormData();
            discoverData.append('action', 'discover_all');
            discoverData.append('url', formData.get('discovery_url'));
            discoverData.append('age_filter', formData.get('age_filter'));
            
            const response = await fetch('book-discovery-ajax.php', {
                method: 'POST',
                body: discoverData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            this.books = result.books;
            this.totalBooks = result.total;
            
            if (this.totalBooks === 0) {
                this.updateProgress(100, 'No books found matching your criteria.');
                return;
            }
            
            this.updateProgress(10, `Found ${this.totalBooks} books. Starting processing...`);
            
            // Step 2: Process books one by one
            await this.processBooks();
            
            // Step 3: Show results
            this.showResults();
            
        } catch (error) {
            console.error('Discovery error:', error);
            this.updateProgress(0, `Error: ${error.message}`, 'danger');
        }
    }
    
    async processBooks() {
        for (let i = 0; i < this.books.length; i++) {
            const book = this.books[i];
            const progress = 10 + ((i / this.books.length) * 80); // 10-90%
            
            // Show current book being processed
            document.getElementById('currentBookInfo').style.display = 'block';
            document.getElementById('currentBookTitle').textContent = book.title || 'Unknown Title';
            
            this.updateProgress(progress, `Processing book ${i + 1} of ${this.books.length}: ${book.title}`);
            
            try {
                // Enrich if requested
                if (this.autoEnrich) {
                    const enrichData = new FormData();
                    enrichData.append('action', 'enrich_book');
                    enrichData.append('book', JSON.stringify(book));
                    
                    const enrichResponse = await fetch('book-discovery-ajax.php', {
                        method: 'POST',
                        body: enrichData
                    });
                    
                    const enrichResult = await enrichResponse.json();
                    
                    if (enrichResult.success) {
                        this.books[i] = enrichResult.book;
                    }
                }
                
                // Import if requested
                if (this.importToDb) {
                    const importData = new FormData();
                    importData.append('action', 'import_book');
                    importData.append('book', JSON.stringify(this.books[i]));
                    
                    const importResponse = await fetch('book-discovery-ajax.php', {
                        method: 'POST',
                        body: importData
                    });
                    
                    const importResult = await importResponse.json();
                    
                    if (importResult.success) {
                        this.importedBooks++;
                        this.books[i].imported = true;
                    } else {
                        this.books[i].import_error = importResult.message;
                    }
                }
                
                this.processedBooks++;
                
            } catch (error) {
                console.error(`Error processing book ${i + 1}:`, error);
                this.errorBooks++;
                this.books[i].processing_error = error.message;
            }
            
            // Small delay to show progress
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        // Hide current book info
        document.getElementById('currentBookInfo').style.display = 'none';
        this.updateProgress(100, 'Processing complete!', 'success');
    }
    
    updateProgress(percentage, message, type = 'info') {
        const progressBar = document.getElementById('progressBar');
        const progressMessage = document.getElementById('progressMessage');
        
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.textContent = Math.round(percentage) + '%';
        
        progressMessage.textContent = message;
        progressMessage.className = `alert alert-${type}`;
    }
    
    showResults() {
        // Show results section
        document.getElementById('resultsSection').style.display = 'block';
        
        // Update summary
        const summaryInfo = document.getElementById('summaryInfo');
        let summaryHtml = `
            <h6>Summary:</h6>
            <ul class="mb-0">
                <li>Total books discovered: ${this.totalBooks}</li>
                <li>Successfully processed: ${this.processedBooks}</li>
<li>Errors: ${this.errorBooks}</li>
        `;
        
        if (this.autoEnrich) {
            summaryHtml += `<li>Books enriched with API data: ${this.processedBooks}</li>`;
        }
        
        if (this.importToDb) {
            summaryHtml += `<li>Successfully imported: ${this.importedBooks}</li>`;
        }
        
        summaryHtml += '</ul>';
        summaryInfo.innerHTML = summaryHtml;
        
        // Prepare data for enhanced table
        const tableData = this.books.map((book, index) => ({
            id: index + 1,
            title: book.title || '',
            author: book.author || '',
            age_range: book.age_range || '',
            year: book.year || '',
            isbn: book.isbn || '',
            isbn13: book.isbn13 || '',
            publisher: book.publisher || '',
            tags: Array.isArray(book.tags) ? book.tags.join(', ') : (book.tags || ''),
            source: book.source || 'booktrust',
            enriched: this.autoEnrich ? 'Yes' : 'No',
            imported: book.imported ? 'Yes' : 'No',
            status: book.processing_error ? 'Error' : (book.imported ? 'Imported' : 'Ready')
        }));
        
        // Create enhanced table HTML
        this.renderEnhancedTable(tableData);
    }
    
    renderEnhancedTable(tableData) {
        const columns = {
            'title': 'Title',
            'author': 'Author',
            'age_range': 'Age Range',
            'year': 'Year',
            'isbn': 'ISBN',
            'isbn13': 'ISBN-13',
            'publisher': 'Publisher',
            'tags': 'Tags/Genres',
            'source': 'Source',
            'enriched': 'API Enriched',
            'imported': 'Imported',
            'status': 'Status'
        };
        
        let tableHtml = `
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="discoveredBooksTable">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
        `;
        
        Object.values(columns).forEach(label => {
            tableHtml += `<th>${label}</th>`;
        });
        
        tableHtml += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        tableData.forEach((row, index) => {
            tableHtml += `
                <tr>
                    <td><input type="checkbox" class="row-checkbox" data-id="${row.id}"></td>
            `;
            
            Object.keys(columns).forEach(field => {
                const value = row[field] || '';
                if (field === 'title' && row.detail_url) {
                    tableHtml += `<td><a href="${row.detail_url}" target="_blank">${value}</a></td>`;
                } else if (field === 'status') {
                    const statusClass = value === 'Error' ? 'text-danger' : (value === 'Imported' ? 'text-success' : 'text-info');
                    tableHtml += `<td><span class="${statusClass}">${value}</span></td>`;
                } else {
                    tableHtml += `<td>${value}</td>`;
                }
            });
            
            tableHtml += '</tr>';
        });
        
        tableHtml += `
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <button class="btn btn-success me-2" onclick="discovery.exportToCSV()">
                    <i class="fas fa-download"></i> Export as CSV
                </button>
                <button class="btn btn-info me-2" onclick="discovery.exportToJSON()">
                    <i class="fas fa-download"></i> Export as JSON
                </button>
                <button class="btn btn-primary" onclick="discovery.importSelected()" ${this.importToDb ? 'disabled' : ''}>
                    <i class="fas fa-upload"></i> Import Selected
                </button>
            </div>
        `;
        
        document.getElementById('tableContainer').innerHTML = tableHtml;
        
        // Add select all functionality
        document.getElementById('selectAll').addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
        });
    }
    
    exportToCSV() {
        const table = document.getElementById('discoveredBooksTable');
        if (!table) return;
        
        let csv = '';
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('th:not(:first-child), td:not(:first-child)');
            const rowData = Array.from(cols).map(col => {
                return '"' + col.textContent.replace(/"/g, '""') + '"';
            });
            csv += rowData.join(',') + '\n';
        });
        
        this.downloadFile(csv, 'discovered-books.csv', 'text/csv');
    }
    
    exportToJSON() {
        const data = this.books.map(book => ({
            title: book.title || '',
            author: book.author || '',
            age_range: book.age_range || '',
            year: book.year || '',
            isbn: book.isbn || '',
            isbn13: book.isbn13 || '',
            publisher: book.publisher || '',
            tags: book.tags || '',
            source: book.source || 'booktrust',
            enriched: this.autoEnrich,
            imported: book.imported || false
        }));
        
        this.downloadFile(JSON.stringify(data, null, 2), 'discovered-books.json', 'application/json');
    }
    
    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    async importSelected() {
        const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Please select books to import');
            return;
        }
        
        let imported = 0;
        let errors = 0;
        
        for (const checkbox of selectedCheckboxes) {
            const bookId = parseInt(checkbox.dataset.id) - 1;
            const book = this.books[bookId];
            
            if (book.imported) continue; // Skip already imported
            
            try {
                const importData = new FormData();
                importData.append('action', 'import_book');
                importData.append('book', JSON.stringify(book));
                
                const response = await fetch('book-discovery-ajax.php', {
                    method: 'POST',
                    body: importData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    imported++;
                    book.imported = true;
                } else {
                    errors++;
                }
            } catch (error) {
                errors++;
            }
        }
        
        alert(`Import complete: ${imported} imported, ${errors} errors`);
        this.showResults(); // Refresh table
    }
}

// Initialize the discovery system
const discovery = new ModernBookDiscovery();
</script>

<style>
.progress {
    height: 25px;
}

.progress-bar {
    font-weight: bold;
    line-height: 25px;
}

#currentBookInfo {
    margin-top: 1rem;
}

.table th {
    background-color: var(--bs-dark);
    color: white;
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.table td {
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: var(--bs-light);
}

.btn-group .btn {
    margin-right: 0.5rem;
}
</style>

<?php include_once '../includes/footer.php'; ?>