<?php
/**
 * Step-by-step AJAX endpoint debugging
 * This will help identify exactly where the issue occurs
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Starting script\n";

// Test session start
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "Step 2: Session started successfully\n";
} catch (Exception $e) {
    echo "Step 2 FAILED: Session error - " . $e->getMessage() . "\n";
    exit;
}

// Test auth include
try {
    require_once '../../admin/includes/auth-check.php';
    echo "Step 3: Auth check included successfully\n";
} catch (Exception $e) {
    echo "Step 3 FAILED: Auth include error - " . $e->getMessage() . "\n";
    exit;
}

// Test database include
try {
    require_once '../../admin/includes/db-connect.php';
    echo "Step 4: Database connection included successfully\n";
    if (isset($db) && $db) {
        echo "Step 4a: Database connection object exists\n";
    } else {
        echo "Step 4a: Database connection object is null or false\n";
    }
} catch (Exception $e) {
    echo "Step 4 FAILED: Database include error - " . $e->getMessage() . "\n";
    exit;
}

// Test discovery engine include
try {
    require_once 'book-discovery/BookDiscoveryEngine.php';
    echo "Step 5: BookDiscoveryEngine included successfully\n";
} catch (Exception $e) {
    echo "Step 5 FAILED: BookDiscoveryEngine include error - " . $e->getMessage() . "\n";
    exit;
}

// Test enrichment functions include
try {
    require_once 'book-import-validate/functions/data-enrichment-functions.php';
    echo "Step 6: Data enrichment functions included successfully\n";
} catch (Exception $e) {
    echo "Step 6 FAILED: Data enrichment include error - " . $e->getMessage() . "\n";
    exit;
}

// Test creating discovery engine
try {
    $discoveryEngine = new BookDiscoveryEngine($db);
    echo "Step 7: BookDiscoveryEngine created successfully\n";
} catch (Exception $e) {
    echo "Step 7 FAILED: BookDiscoveryEngine creation error - " . $e->getMessage() . "\n";
    exit;
}

// Test a simple discovery call
try {
    $testUrl = 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/';
    echo "Step 8: Testing discovery with URL: $testUrl\n";
    
    $books = $discoveryEngine->discoverFromURL($testUrl);
    echo "Step 8: Discovery completed successfully. Found " . count($books) . " books\n";
    
    // Show first book as sample
    if (!empty($books)) {
        $firstBook = $books[0];
        echo "Sample book: " . ($firstBook['title'] ?? 'No title') . "\n";
    }
    
} catch (Exception $e) {
    echo "Step 8 FAILED: Discovery error - " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit;
}

echo "ALL STEPS COMPLETED SUCCESSFULLY!\n";
?>