<?php
/**
 * Test page to debug Amazon data loading in data enrichment modal
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include admin functions
require_once '../includes/admin-functions.php';

// Include the data enrichment modal
require_once 'book-import-validate/modals/data-enrichment-modal.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Amazon Data Loading</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1>Test Amazon Data Loading in Data Enrichment Modal</h1>
        
        <div class="alert alert-info">
            <strong>Purpose:</strong> This page tests the Amazon data loading issue where fields show "Loading Amazon data..." 
            even after Amazon data has been successfully received.
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Test Books</h5>
            </div>
            <div class="card-body">
                <p>Click a button below to open the data enrichment modal for a test book:</p>
                
                <button class="btn btn-primary me-2" onclick="testCoraline()">
                    <i class="fas fa-book"></i> Test Coraline (Book ID: 2104)
                </button>
                
                <button class="btn btn-secondary me-2" onclick="testAnyBook()">
                    <i class="fas fa-random"></i> Test Any Book
                </button>
                
                <button class="btn btn-info" onclick="testAmazonDirectly()">
                    <i class="fas fa-amazon"></i> Test Amazon AJAX Directly
                </button>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Debug Output</h5>
            </div>
            <div class="card-body">
                <div id="debugOutput" style="background: #f8f9fa; padding: 1rem; border-radius: 0.375rem; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto;">
                    Debug output will appear here...
                </div>
            </div>
        </div>
    </div>

    <!-- Load data enrichment scripts -->
    <?php
    $cacheBuster = '?v=' . time() . '_' . rand(1000, 9999);
    ?>
    <script src="../assets/js/data-enrichment-helpers.js<?php echo $cacheBuster; ?>"></script>
    <script src="../assets/js/data-enrichment-utils.js<?php echo $cacheBuster; ?>"></script>
    <script src="../assets/js/data-enrichment-modal.js<?php echo $cacheBuster; ?>"></script>

    <script>
    // Override console.log to also show in our debug output
    const originalConsoleLog = console.log;
    console.log = function(...args) {
        originalConsoleLog.apply(console, args);
        
        const debugOutput = document.getElementById('debugOutput');
        const message = args.map(arg => 
            typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
        ).join(' ');
        
        debugOutput.textContent += new Date().toLocaleTimeString() + ': ' + message + '\n';
        debugOutput.scrollTop = debugOutput.scrollHeight;
    };

    function testCoraline() {
        console.log('🧪 Testing Coraline (Book ID: 2104)');
        console.log('📚 Opening data enrichment modal...');
        
        // Open modal with Coraline data
        openDataEnrichmentModal(2104, 'Coraline', 'Neil Gaiman', '9780380977789');
    }

    function testAnyBook() {
        console.log('🧪 Testing with any available book...');
        
        // Get first available book from database
        $.ajax({
            url: 'book-import-validate/ajax/data-enrichment-ajax.php',
            method: 'POST',
            data: { action: 'test' },
            dataType: 'json',
            success: function(response) {
                console.log('✅ AJAX connection test successful:', response);
                
                // Now try to get a book
                $.ajax({
                    url: 'book-validation-ajax.php',
                    method: 'POST',
                    data: { action: 'get_books_for_validation', limit: 1 },
                    success: function(bookResponse) {
                        if (bookResponse.books && bookResponse.books.length > 0) {
                            const book = bookResponse.books[0];
                            console.log('📚 Found test book:', book);
                            openDataEnrichmentModal(book.id, book.title, book.author, book.isbn13 || book.isbn || '');
                        } else {
                            console.log('❌ No books found for testing');
                            alert('No books found for testing');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('❌ Error getting books:', error);
                        alert('Error getting books: ' + error);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.log('❌ AJAX connection test failed:', error);
                alert('AJAX connection test failed: ' + error);
            }
        });
    }

    function testAmazonDirectly() {
        console.log('🧪 Testing Amazon AJAX directly...');
        
        const testISBN = '9780380977789'; // Coraline ISBN
        
        $.post('book-import-validate/ajax/data-enrichment-ajax.php', {
            action: 'get_amazon_data',
            isbn: testISBN,
            book_id: '2104'
        }, function(res) {
            console.log('📦 Direct Amazon AJAX response:', res);
            console.log('📦 Success:', res.success);
            console.log('📦 Data:', res.data);
            
            if (res.success && res.data) {
                console.log('✅ Amazon data received successfully');
                console.log('📦 Format field:', res.data.format);
                console.log('📦 Price range field:', res.data.price_range);
                console.log('📦 Purchase links field:', res.data.purchase_links);
            } else {
                console.log('❌ Amazon data request failed or returned no data');
            }
        }).fail(function(xhr, status, error) {
            console.log('❌ Amazon AJAX request failed:', error);
            console.log('❌ Response text:', xhr.responseText);
        });
    }

    $(document).ready(function() {
        console.log('🚀 Test page loaded');
        console.log('📋 Available functions:', {
            openDataEnrichmentModal: typeof openDataEnrichmentModal,
            fetchEnrichmentData: typeof fetchEnrichmentData,
            updateEnrichmentDataWithAmazon: typeof updateEnrichmentDataWithAmazon
        });
        
        // Check if modal exists
        if ($('#dataEnrichmentModal').length > 0) {
            console.log('✅ Data enrichment modal found in DOM');
        } else {
            console.log('❌ Data enrichment modal NOT found in DOM');
        }
    });
    </script>
</body>
</html>
