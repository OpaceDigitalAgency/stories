<?php
/**
 * Test Book Form Enhancements
 * 
 * This script tests the book form enhancements to make sure they work correctly.
 */

// Include auth check
require_once 'includes/auth-check.php';

// Include database connection
require_once 'includes/db-connect.php';

// Page variables
$pageTitle = 'Test Book Form Enhancements';
$currentPage = 'test';

// Add custom CSS and JS
$extraHeadContent = '
<!-- Include book form enhancements -->
<link rel="stylesheet" href="assets/css/book-form-enhancements.css">
<script src="assets/js/book-form-enhancements.js"></script>
<style>
    .test-section {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .test-section h3 {
        margin-top: 0;
    }
    
    .test-result {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
    
    .success {
        color: #28a745;
    }
    
    .error {
        color: #dc3545;
    }
</style>
';

// Include header
require_once 'includes/header.php';
?>

<div class="content-section">
    <div class="section-header">
        <h2>Test Book Form Enhancements</h2>
        <p>This page tests the book form enhancements to make sure they work correctly.</p>
    </div>
    
    <div class="section-body">
        <div class="test-section">
            <h3>Tag Selection</h3>
            <p>Test if the tag selection works correctly.</p>
            
            <div class="form-group">
                <label for="tag-select">Tags</label>
                <select id="tag-select" class="form-control">
                    <option value="">Select a tag to add</option>
                    <option value="1">Fiction</option>
                    <option value="2">**Non-Fiction</option>
                    <option value="3">7-10</option>
                    <option value="4">12+</option>
                    <option value="5">Adventure</option>
                </select>
                
                <div class="tag-container" id="tag-container">
                    <span class="tag-badge" data-tag-id="1">
                        Fiction
                        <i class="fas fa-times remove-tag"></i>
                        <input type="hidden" name="tags[]" value="1">
                    </span>
                </div>
            </div>
            
            <div class="test-result">
                <p>Expected: The tag selection should filter out age-related tags and remove ** prefix from tag names.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Author Dropdown</h3>
            <p>Test if the author dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="author">Author</label>
                <select id="author" name="book_author" class="form-control">
                    <option value="">Select Author</option>
                    <option value="J.K. Rowling">J.K. Rowling</option>
                    <option value="**Roald Dahl">**Roald Dahl</option>
                    <option value="C.S. Lewis">C.S. Lewis</option>
                </select>
            </div>
            
            <div class="test-result">
                <p>Expected: The author dropdown should remove ** prefix from author names.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Series Dropdown</h3>
            <p>Test if the series dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="series">Series</label>
                <input type="text" id="series" name="book_series" class="form-control" value="Harry Potter">
            </div>
            
            <div class="test-result">
                <p>Expected: The series input should be replaced with a dropdown with common series options.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Purchase Links Manager</h3>
            <p>Test if the purchase links manager works correctly.</p>
            
            <div class="form-group">
                <label for="purchase_links">Purchase Links</label>
                <textarea id="purchase_links" name="book_purchase_links" class="form-control" rows="3">{"amazon":"https://amazon.com/dp/1234567890","goodreads":"https://goodreads.com/book/show/1234567890"}</textarea>
            </div>
            
            <div class="test-result">
                <p>Expected: The purchase links textarea should be replaced with a user-friendly interface for managing purchase links.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Age Range Dropdown</h3>
            <p>Test if the age range dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="age_range">Age Range</label>
                <input type="text" id="age_range" name="book_age_range" class="form-control" value="7-10">
            </div>
            
            <div class="test-result">
                <p>Expected: The age range input should be replaced with a dropdown with common age range options.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Genre Dropdown</h3>
            <p>Test if the genre dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="genre">Genre</label>
                <input type="text" name="book_genre" class="form-control" value="Fantasy">
            </div>
            
            <div class="test-result">
                <p>Expected: The genre input should be replaced with a dropdown with common genre options.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Reading Level Dropdown</h3>
            <p>Test if the reading level dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="reading_level">Reading Level</label>
                <input type="text" id="reading_level" name="book_reading_level" class="form-control" value="Middle Grade">
            </div>
            
            <div class="test-result">
                <p>Expected: The reading level input should be replaced with a dropdown with common reading level options.</p>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Publisher Dropdown</h3>
            <p>Test if the publisher dropdown works correctly.</p>
            
            <div class="form-group">
                <label for="publisher">Publisher</label>
                <input type="text" name="book_publisher" class="form-control" value="Penguin Random House">
            </div>
            
            <div class="test-result">
                <p>Expected: The publisher input should be replaced with a dropdown with common publisher options.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
