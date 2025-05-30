<?php
/**
 * Clear cache for books showing "12+" issue
 */

require_once '../includes/db-connect.php';

echo "<h1>🧹 Clear 12+ Cache Issue</h1>";

try {
    // 1. Clear all validation cache entries
    echo "<h2>🗑️ Clearing Validation Cache</h2>";
    
    $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%book_validation_%'");
    $result = $stmt->execute();
    $count = $stmt->rowCount();
    echo "<p>✅ Cleared $count validation cache entries</p>";
    
    // 2. Clear Google Books specific cache
    $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%google_books%'");
    $result = $stmt->execute();
    $count = $stmt->rowCount();
    echo "<p>✅ Cleared $count Google Books cache entries</p>";
    
    // 3. Clear Amazon cache
    $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%amazon%'");
    $result = $stmt->execute();
    $count = $stmt->rowCount();
    echo "<p>✅ Cleared $count Amazon cache entries</p>";
    
    // 4. Clear OpenLibrary cache
    $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%open_library%'");
    $result = $stmt->execute();
    $count = $stmt->rowCount();
    echo "<p>✅ Cleared $count OpenLibrary cache entries</p>";
    
    // 5. Clear all cache entries (nuclear option)
    $stmt = $db->prepare("TRUNCATE TABLE validation_cache");
    $result = $stmt->execute();
    echo "<p>✅ Cleared ALL validation cache entries (nuclear option)</p>";
    
    echo "<h2>🔄 Cache Cleared Successfully</h2>";
    echo "<p><strong>All validation caches have been cleared. The next data enrichment request should fetch fresh data.</strong></p>";
    echo "<p><a href='book-import-validate.php'>← Back to Book Validation</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
