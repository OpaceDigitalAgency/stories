<?php
/**
 * Cache Functions
 *
 * This file contains functions for caching validation results and managing validation history.
 */

// Include auth check
require_once __DIR__ . '/../../../../includes/auth-check.php';

// Include database connection
require_once __DIR__ . '/../../../../includes/db_connect.php';

/**
 * Get validation results from cache
 *
 * @param string $cacheKey The cache key
 * @param PDO $db Database connection
 * @return array|null Cached validation results or null if not found
 */
function getValidationCacheNew($cacheKey, $db) {
    try {
        // Check if we have a validation_cache table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_cache'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            // Create the cache table if it doesn't exist
            $db->exec("
                CREATE TABLE validation_cache (
                    cache_key VARCHAR(255) PRIMARY KEY,
                    cache_data LONGTEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            return null;
        }

        // Get cache data
        $stmt = $db->prepare("
            SELECT cache_data, created_at
            FROM validation_cache
            WHERE cache_key = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$cacheKey]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cache) {
            return json_decode($cache['cache_data'], true);
        }

        return null;
    } catch (Exception $e) {
        error_log("Cache error: " . $e->getMessage());
        return null;
    }
}

/**
 * Save validation results to cache
 *
 * @param string $cacheKey The cache key
 * @param array $data The data to cache
 * @param PDO $db Database connection
 * @return bool True if successful, false otherwise
 */
function saveValidationCacheNew($cacheKey, $data, $db) {
    try {
        // Check if we have a validation_cache table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_cache'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            // Create the cache table if it doesn't exist
            $db->exec("
                CREATE TABLE validation_cache (
                    cache_key VARCHAR(255) PRIMARY KEY,
                    cache_data LONGTEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        // Insert or update cache data
        $stmt = $db->prepare("
            INSERT INTO validation_cache (cache_key, cache_data, created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            cache_data = VALUES(cache_data),
            created_at = NOW()
        ");
        $stmt->execute([$cacheKey, json_encode($data)]);

        return true;
    } catch (Exception $e) {
        error_log("Cache save error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear validation cache for a book
 *
 * @param int $bookId The book ID
 * @param string $isbn The ISBN
 * @param string $title The book title
 * @param PDO $db Database connection
 * @return bool True if successful, false otherwise
 */
function clearValidationCacheNew($bookId, $isbn, $title, $db) {
    try {
        // Check if we have a validation_cache table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_cache'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            return true; // No cache table, nothing to clear
        }

        // Generate cache keys
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        $cacheKeys = [
            md5("book_validation_{$bookId}_{$cleanIsbn}"),
            md5("isbn_validation_{$cleanIsbn}_{$title}")
        ];

        // Delete cache entries
        $placeholders = implode(',', array_fill(0, count($cacheKeys), '?'));
        $stmt = $db->prepare("
            DELETE FROM validation_cache
            WHERE cache_key IN ($placeholders)
        ");
        $stmt->execute($cacheKeys);

        return true;
    } catch (Exception $e) {
        error_log("Cache clear error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear all validation cache entries
 *
 * @param PDO $db Database connection
 * @return bool True if successful, false otherwise
 */
function clearAllValidationCache($db) {
    try {
        // Check if we have a validation_cache table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_cache'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            return true; // No cache table, nothing to clear
        }

        // Delete all cache entries
        $stmt = $db->prepare("TRUNCATE TABLE validation_cache");
        $stmt->execute();

        return true;
    } catch (Exception $e) {
        error_log("Cache clear all error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear validation cache for a specific source
 *
 * @param string $source The source name (e.g., 'google_books', 'open_library', 'goodreads')
 * @param PDO $db Database connection
 * @return bool True if successful, false otherwise
 */
function clearSourceValidationCache($source, $db) {
    try {
        // Check if we have a validation_cache table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_cache'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            return true; // No cache table, nothing to clear
        }

        // Get all cache entries
        $stmt = $db->prepare("SELECT cache_key, cache_data FROM validation_cache");
        $stmt->execute();
        $cacheEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Keys to delete
        $keysToDelete = [];

        // Loop through cache entries and check if they contain the source
        foreach ($cacheEntries as $entry) {
            $cacheData = json_decode($entry['cache_data'], true);
            if (isset($cacheData['sourceData'][$source])) {
                $keysToDelete[] = $entry['cache_key'];
            }
        }

        if (!empty($keysToDelete)) {
            // Delete matching cache entries
            $placeholders = implode(',', array_fill(0, count($keysToDelete), '?'));
            $stmt = $db->prepare("
                DELETE FROM validation_cache
                WHERE cache_key IN ($placeholders)
            ");
            $stmt->execute($keysToDelete);
        }

        return true;
    } catch (Exception $e) {
        error_log("Cache clear source error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get validation history for a book
 *
 * @param int $bookId The book ID
 * @param PDO $db Database connection
 * @return array Validation history
 */
function getValidationHistory($bookId, $db) {
    try {
        // Check if we have a validation_history table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_history'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            // Create the history table if it doesn't exist
            $db->exec("
                CREATE TABLE validation_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    book_id INT NOT NULL,
                    field VARCHAR(50) NOT NULL,
                    old_value TEXT,
                    new_value TEXT,
                    source VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_book_id (book_id)
                )
            ");
            return [];
        }

        // Get history data
        $stmt = $db->prepare("
            SELECT field, old_value, new_value, source, created_at
            FROM validation_history
            WHERE book_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$bookId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format history for display
        $formattedHistory = [];
        foreach ($history as $entry) {
            $formattedHistory[] = [
                'timestamp' => $entry['created_at'],
                'action' => "Updated {$entry['field']} from {$entry['source']}"
            ];
        }

        return $formattedHistory;
    } catch (Exception $e) {
        error_log("History error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add entry to validation history
 *
 * @param int $bookId The book ID
 * @param string $field The field that was updated
 * @param string $oldValue The old value
 * @param string $newValue The new value
 * @param string $source The source of the update
 * @param PDO $db Database connection
 * @return bool True if successful, false otherwise
 */
function addValidationHistoryEntry($bookId, $field, $oldValue, $newValue, $source, $db) {
    try {
        // Check if we have a validation_history table
        $stmt = $db->prepare("
            SELECT COUNT(*) as table_exists
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'validation_history'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['table_exists'] == 0) {
            // Create the history table if it doesn't exist
            $db->exec("
                CREATE TABLE validation_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    book_id INT NOT NULL,
                    field VARCHAR(50) NOT NULL,
                    old_value TEXT,
                    new_value TEXT,
                    source VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_book_id (book_id)
                )
            ");
        }

        // Insert history entry
        $stmt = $db->prepare("
            INSERT INTO validation_history (book_id, field, old_value, new_value, source, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$bookId, $field, $oldValue, $newValue, $source]);

        return true;
    } catch (Exception $e) {
        error_log("History save error: " . $e->getMessage());
        return false;
    }
}
