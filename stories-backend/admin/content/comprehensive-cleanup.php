<?php
/**
 * Comprehensive Duplicate Cleanup Tool
 * 3-Stage Process: Analyze → Reassign → Update
 * Covers all dropdown fields from directory-item-form.php
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Comprehensive Duplicate Cleanup';
$currentPage = 'comprehensive-cleanup';
$pageDescription = 'Analyze and clean up duplicate entries across all dropdown fields';

// Include header
require_once '../includes/header.php';

// Set execution time limit for large operations
set_time_limit(300);

// Removed stages - now a single comprehensive analysis and cleanup page

// Helper function to check if column exists
function columnExists($table, $column) {
    global $db;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Helper function to get table info
function getTableInfo($table) {
    global $db;
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

?>

<style>
    .debug-output {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        white-space: pre-wrap;
    }
    .success { color: #198754; }
    .error { color: #dc3545; }
    .info { color: #0dcaf0; }
    .duplicate-group {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 0.375rem;
        padding: 1rem;
        margin: 0.5rem 0;
    }
    .stage-nav .btn {
        margin-right: 0.5rem;
    }
</style>
    <div class="container-fluid">
        <!-- Main Header -->
        <div class="mb-4">
            <div class="alert alert-warning">
                <strong>⚠️ Warning:</strong> This tool will modify your database. Make sure you have a backup before proceeding.
            </div>
        </div>

            <?php
            // 1. AUTHORS (Publishers and Book Authors)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>👥 Authors & Publishers Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                // Check which column exists for type
                $hasTypeColumn = columnExists('authors', 'type');
                $hasAuthorTypeColumn = columnExists('authors', 'author_type');

                echo '<div class="debug-output">';
                echo "Authors table structure:\n";
                echo "- 'type' column exists: " . ($hasTypeColumn ? 'YES' : 'NO') . "\n";
                echo "- 'author_type' column exists: " . ($hasAuthorTypeColumn ? 'YES' : 'NO') . "\n";
                echo '</div>';

                // Find exact duplicates - fix GROUP BY issue
                $sql = "
                    SELECT
                        MIN(name) as name,
                        COUNT(*) as count,
                        GROUP_CONCAT(id) as ids
                    FROM authors
                    GROUP BY LOWER(TRIM(name))
                    HAVING count > 1
                    ORDER BY count DESC, MIN(name)
                ";
                $stmt = $db->query($sql);
                $duplicateAuthors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($duplicateAuthors)) {
                    echo '<div class="alert alert-success">✅ No exact duplicate authors found</div>';
                } else {
                    echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateAuthors) . ' groups of duplicate authors:</div>';

                    foreach ($duplicateAuthors as $group) {
                        echo '<div class="duplicate-group">';
                        echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                        echo 'IDs: ' . $group['ids'] . '<br>';

                        // Show usage
                        $ids = explode(',', $group['ids']);
                        foreach ($ids as $id) {
                            $id = trim($id);

                            // Check books.publisher_id
                            if (columnExists('books', 'publisher_id')) {
                                $stmt = $db->prepare("SELECT COUNT(*) FROM books WHERE publisher_id = ?");
                                $stmt->execute([$id]);
                                $publisherBooks = $stmt->fetchColumn();
                                if ($publisherBooks > 0) {
                                    echo "ID $id: $publisherBooks books (as publisher)<br>";
                                }
                            }

                            // Check story_authors
                            if (getTableInfo('story_authors')) {
                                $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                                $stmt->execute([$id]);
                                $storyCount = $stmt->fetchColumn();
                                if ($storyCount > 0) {
                                    echo "ID $id: $storyCount stories (as author)<br>";
                                }
                            }
                        }
                        echo '<button onclick="consolidateAuthors(\'' . $group['ids'] . '\', \'' . htmlspecialchars($group['name']) . '\')" class="btn btn-sm btn-warning mt-2">🔧 Consolidate Duplicates</button>';
                        echo '</div>';
                    }
                }

                // DYNAMIC SIMILARITY DETECTION - finds ALL similar publishers
                echo '<h5 class="mt-4">🔍 Dynamic Publisher Similarity Detection:</h5>';
                echo '<div class="alert alert-info">Using advanced algorithms to find ALL similar publishers without hard-coded rules</div>';

                // Get ACTUAL publishers - combine from books.publisher field AND authors table publishers
                // First get unique publishers from books table
                $stmt = $db->query("
                    SELECT DISTINCT publisher as name, COUNT(*) as book_count
                    FROM books
                    WHERE publisher IS NOT NULL AND publisher != ''
                    GROUP BY publisher
                    ORDER BY publisher
                ");
                $bookPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Then get publishers from authors table (those with publisher_id relationships)
                $stmt = $db->query("
                    SELECT DISTINCT a.id, a.name,
                           (SELECT COUNT(*) FROM books WHERE publisher_id = a.id) as book_count
                    FROM authors a
                    WHERE a.id IN (SELECT DISTINCT publisher_id FROM books WHERE publisher_id IS NOT NULL)
                    AND a.name IS NOT NULL AND a.name != ''
                    ORDER BY a.name
                ");
                $authorPublishers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Combine and deduplicate
                $allPublishers = [];
                $seenNames = [];

                // Add from books table (these are the actual publisher strings)
                // First, try to find or create authors table entries for these publishers
                foreach ($bookPublishers as $pub) {
                    $cleanName = trim($pub['name']);
                    if (!in_array(strtolower($cleanName), $seenNames)) {
                        // Try to find existing author record for this publisher
                        $stmt = $db->prepare("SELECT id FROM authors WHERE name = ?");
                        $stmt->execute([$cleanName]);
                        $existingAuthor = $stmt->fetch();

                        if ($existingAuthor) {
                            // Use existing author ID
                            $publisherId = $existingAuthor['id'];
                        } else {
                            // Create new author record for this publisher with slug
                            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cleanName)));
                            $stmt = $db->prepare("INSERT INTO authors (name, slug) VALUES (?, ?)");
                            $stmt->execute([$cleanName, $slug]);
                            $publisherId = $db->lastInsertId();
                        }

                        $allPublishers[] = [
                            'id' => $publisherId, // Real ID from authors table
                            'name' => $cleanName,
                            'book_count' => $pub['book_count'],
                            'source' => 'books_table_converted'
                        ];
                        $seenNames[] = strtolower($cleanName);
                    }
                }

                // Add from authors table (these have actual IDs)
                foreach ($authorPublishers as $pub) {
                    $cleanName = trim($pub['name']);
                    if (!in_array(strtolower($cleanName), $seenNames)) {
                        $allPublishers[] = [
                            'id' => $pub['id'],
                            'name' => $cleanName,
                            'book_count' => $pub['book_count'],
                            'source' => 'authors_table'
                        ];
                        $seenNames[] = strtolower($cleanName);
                    }
                }

                // DEBUG: Show all publishers for troubleshooting
                echo '<div class="alert alert-light border">';
                echo '<h6>🔍 DEBUG: Publisher Data Sources (' . count($allPublishers) . ' total combined):</h6>';

                // Show breakdown by source
                $bookSources = array_filter($allPublishers, function($pub) { return $pub['source'] === 'books_table'; });
                $authorSources = array_filter($allPublishers, function($pub) { return $pub['source'] === 'authors_table'; });

                echo '<div class="row mb-3">';
                echo '<div class="col-md-6">';
                echo '<strong>📚 From books.publisher field (' . count($bookSources) . '):</strong><br>';
                echo '<small class="text-muted">These are the actual publisher strings used in books</small><br>';
                foreach (array_slice($bookSources, 0, 10) as $pub) {
                    echo '• ' . htmlspecialchars($pub['name']) . ' (' . $pub['book_count'] . ' books)<br>';
                }
                if (count($bookSources) > 10) echo '<small>... and ' . (count($bookSources) - 10) . ' more</small><br>';
                echo '</div>';

                echo '<div class="col-md-6">';
                echo '<strong>👥 From authors table (' . count($authorSources) . '):</strong><br>';
                echo '<small class="text-muted">These have publisher_id relationships</small><br>';
                foreach (array_slice($authorSources, 0, 10) as $pub) {
                    echo '• ' . htmlspecialchars($pub['name']) . ' (ID: ' . $pub['id'] . ', ' . $pub['book_count'] . ' books)<br>';
                }
                if (count($authorSources) > 10) echo '<small>... and ' . (count($authorSources) - 10) . ' more</small><br>';
                echo '</div>';
                echo '</div>';

                // Show specific publishers we're looking for
                $publisherNames = array_column($allPublishers, 'name');
                $harperPublishers = array_filter($allPublishers, function($pub) {
                    return stripos($pub['name'], 'harper') !== false || stripos($pub['name'], 'collins') !== false;
                });
                $bloomsburyPublishers = array_filter($allPublishers, function($pub) {
                    return stripos($pub['name'], 'bloomsbury') !== false;
                });
                $simonPublishers = array_filter($allPublishers, function($pub) {
                    return stripos($pub['name'], 'simon') !== false || stripos($pub['name'], 'schuster') !== false;
                });

                echo '<div class="row">';
                echo '<div class="col-md-4">';
                echo '<strong>🔍 Harper/Collins Found (' . count($harperPublishers) . '):</strong><br>';
                foreach ($harperPublishers as $pub) {
                    echo '• ' . htmlspecialchars($pub['name']) . ' (' . $pub['source'] . ', ' . $pub['book_count'] . ' books)<br>';
                }
                echo '</div>';

                echo '<div class="col-md-4">';
                echo '<strong>🔍 Bloomsbury Found (' . count($bloomsburyPublishers) . '):</strong><br>';
                foreach ($bloomsburyPublishers as $pub) {
                    echo '• ' . htmlspecialchars($pub['name']) . ' (' . $pub['source'] . ', ' . $pub['book_count'] . ' books)<br>';
                }
                echo '</div>';

                echo '<div class="col-md-4">';
                echo '<strong>🔍 Simon & Schuster Found (' . count($simonPublishers) . '):</strong><br>';
                foreach ($simonPublishers as $pub) {
                    echo '• ' . htmlspecialchars($pub['name']) . ' (' . $pub['source'] . ', ' . $pub['book_count'] . ' books)<br>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';

                // Function to calculate similarity between two strings - ENHANCED for publisher matching
                function calculateSimilarity($str1, $str2) {
                    $original1 = $str1;
                    $original2 = $str2;

                    $str1 = strtolower(trim($str1));
                    $str2 = strtolower(trim($str2));

                    // If identical, return 100%
                    if ($str1 === $str2) return 100;

                    // Create normalized versions for better matching
                    $norm1 = $str1;
                    $norm2 = $str2;

                    // Remove common publisher suffixes/prefixes that don't affect identity
                    $commonWords = [
                        'ltd', 'limited', 'plc', 'inc', 'books', 'publishing', 'publishers', 'press',
                        'children\'s', 'childrens', 'uk', 'usa', 'group', 'imprint', 'young', 'readers',
                        'an', 'of', 'for', '&', 'and', 'the'
                    ];

                    foreach ($commonWords as $word) {
                        $norm1 = preg_replace('/\b' . preg_quote($word) . '\b/', '', $norm1);
                        $norm2 = preg_replace('/\b' . preg_quote($word) . '\b/', '', $norm2);
                    }

                    // Clean up extra spaces and punctuation
                    $norm1 = preg_replace('/[^\w\s]/', '', $norm1);
                    $norm2 = preg_replace('/[^\w\s]/', '', $norm2);
                    $norm1 = preg_replace('/\s+/', ' ', trim($norm1));
                    $norm2 = preg_replace('/\s+/', ' ', trim($norm2));

                    // If normalized versions are identical, high score
                    if ($norm1 === $norm2 && strlen($norm1) > 2) return 95;

                    // Special cases for known publisher patterns - ENHANCED
                    $specialCases = [
                        // Harper Collins variations
                        ['harper collins', 'harpercollins'],
                        ['harper collins', 'harper collins children'],
                        ['harpercollins', 'harpercollins children'],
                        ['harper collins', 'harpercollins children'],
                        ['harper', 'harpercollins'],
                        ['collins', 'harpercollins'],

                        // Bloomsbury variations
                        ['bloomsbury', 'bloomsbury publishing'],
                        ['bloomsbury publishing', 'bloomsbury publishing plc'],
                        ['bloomsbury', 'bloomsbury plc'],
                        ['bloomsbury publishing', 'bloomsbury'],

                        // Simon & Schuster variations
                        ['simon schuster', 'simon schuster children'],
                        ['simon schuster', 'simon schuster young readers'],
                        ['simon schuster', 'simon schuster books young readers'],
                        ['simon', 'simon schuster'],
                        ['schuster', 'simon schuster'],

                        // Penguin variations
                        ['penguin', 'penguin random house'],
                        ['penguin books', 'penguin random house'],
                        ['random house', 'penguin random house'],

                        // Oxford variations
                        ['oxford', 'oxford university press'],
                        ['oxford university', 'oxford university press'],

                        // Cambridge variations
                        ['cambridge', 'cambridge university press'],
                        ['cambridge university', 'cambridge university press'],
                    ];

                    foreach ($specialCases as $case) {
                        if ((strpos($norm1, $case[0]) !== false && strpos($norm2, $case[1]) !== false) ||
                            (strpos($norm1, $case[1]) !== false && strpos($norm2, $case[0]) !== false)) {
                            return 90;
                        }
                    }

                    // Calculate various similarity metrics
                    $scores = [];

                    // 1. Levenshtein on normalized strings
                    if (strlen($norm1) > 0 && strlen($norm2) > 0) {
                        $levenshtein = levenshtein($norm1, $norm2);
                        $maxLen = max(strlen($norm1), strlen($norm2));
                        $scores[] = $maxLen > 0 ? (1 - $levenshtein / $maxLen) * 100 : 0;
                    }

                    // 2. Substring matching on original strings
                    if (strlen($str1) > 3 && strlen($str2) > 3) {
                        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
                            $scores[] = 85;
                        }
                    }

                    // 3. Word overlap on normalized strings
                    $words1 = array_filter(explode(' ', $norm1));
                    $words2 = array_filter(explode(' ', $norm2));
                    if (count($words1) > 0 && count($words2) > 0) {
                        $commonWords = array_intersect($words1, $words2);
                        $wordOverlap = (count($commonWords) / max(count($words1), count($words2))) * 100;
                        $scores[] = $wordOverlap;
                    }

                    // 4. Core publisher name matching (first significant word)
                    $core1 = '';
                    $core2 = '';
                    if (count($words1) > 0) $core1 = $words1[0];
                    if (count($words2) > 0) $core2 = $words2[0];

                    if (strlen($core1) > 3 && strlen($core2) > 3) {
                        if ($core1 === $core2) {
                            $scores[] = 80;
                        } elseif (strpos($core1, $core2) !== false || strpos($core2, $core1) !== false) {
                            $scores[] = 70;
                        }
                    }

                    // Return the highest similarity score
                    return count($scores) > 0 ? max($scores) : 0;
                }

                // Find similar groups using clustering approach - finds ALL variations
                $similarGroups = [];
                $processed = [];

                // First, create a similarity matrix for all publishers
                $similarityMatrix = [];
                $debugSimilarities = [];

                for ($i = 0; $i < count($allPublishers); $i++) {
                    for ($j = $i + 1; $j < count($allPublishers); $j++) {
                        $similarity = calculateSimilarity($allPublishers[$i]['name'], $allPublishers[$j]['name']);

                        // Debug: collect interesting similarities
                        if ($similarity >= 50) {
                            $debugSimilarities[] = [
                                'name1' => $allPublishers[$i]['name'],
                                'name2' => $allPublishers[$j]['name'],
                                'similarity' => $similarity
                            ];
                        }

                        if ($similarity >= 50) { // Lowered threshold from 60 to 50 to catch more matches
                            $similarityMatrix[] = [
                                'pub1' => $i,
                                'pub2' => $j,
                                'similarity' => $similarity
                            ];
                        }
                    }
                }

                // DEBUG: Show similarity calculations
                echo '<div class="alert alert-light border">';
                echo '<h6>🔍 DEBUG: Similarity Calculations (50%+ matches):</h6>';
                echo '<div class="table-responsive">';
                echo '<table class="table table-sm">';
                echo '<thead><tr><th>Publisher 1</th><th>Publisher 2</th><th>Similarity</th><th>Threshold Met</th></tr></thead>';
                echo '<tbody>';
                usort($debugSimilarities, function($a, $b) { return $b['similarity'] - $a['similarity']; });
                foreach (array_slice($debugSimilarities, 0, 20) as $sim) {
                    $thresholdMet = $sim['similarity'] >= 50 ? '✅ YES' : '❌ NO';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($sim['name1']) . '</td>';
                    echo '<td>' . htmlspecialchars($sim['name2']) . '</td>';
                    echo '<td><span class="badge bg-info">' . round($sim['similarity'], 1) . '%</span></td>';
                    echo '<td>' . $thresholdMet . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
                echo '<small class="text-muted">Showing top 20 similarities. Threshold for grouping: 60%</small>';
                echo '</div>';
                echo '</div>';

                // Group publishers using connected components approach
                $groups = [];
                $publisherToGroup = [];

                foreach ($similarityMatrix as $match) {
                    $pub1 = $match['pub1'];
                    $pub2 = $match['pub2'];

                    $group1 = $publisherToGroup[$pub1] ?? null;
                    $group2 = $publisherToGroup[$pub2] ?? null;

                    if ($group1 === null && $group2 === null) {
                        // Create new group
                        $newGroupId = count($groups);
                        $groups[$newGroupId] = [$pub1, $pub2];
                        $publisherToGroup[$pub1] = $newGroupId;
                        $publisherToGroup[$pub2] = $newGroupId;
                    } elseif ($group1 !== null && $group2 === null) {
                        // Add pub2 to group1
                        $groups[$group1][] = $pub2;
                        $publisherToGroup[$pub2] = $group1;
                    } elseif ($group1 === null && $group2 !== null) {
                        // Add pub1 to group2
                        $groups[$group2][] = $pub1;
                        $publisherToGroup[$pub1] = $group2;
                    } elseif ($group1 !== $group2) {
                        // Merge groups
                        $groups[$group1] = array_merge($groups[$group1], $groups[$group2]);
                        foreach ($groups[$group2] as $pubIndex) {
                            $publisherToGroup[$pubIndex] = $group1;
                        }
                        unset($groups[$group2]);
                    }
                }

                // Convert groups to publisher data and sort
                foreach ($groups as $groupIndices) {
                    if (count($groupIndices) > 1) {
                        $group = [];
                        foreach ($groupIndices as $index) {
                            $group[] = $allPublishers[$index];
                        }

                        // Sort by book count descending to suggest best master
                        usort($group, function($a, $b) {
                            return $b['book_count'] - $a['book_count'];
                        });
                        $similarGroups[] = $group;
                    }
                }

                // Sort groups by total book count
                usort($similarGroups, function($a, $b) {
                    $totalA = array_sum(array_column($a, 'book_count'));
                    $totalB = array_sum(array_column($b, 'book_count'));
                    return $totalB - $totalA;
                });

                if (empty($similarGroups)) {
                    echo '<div class="alert alert-success">✅ No similar publisher groups found</div>';
                } else {
                    echo '<div class="alert alert-info">🔍 Found ' . count($similarGroups) . ' groups of similar publishers using dynamic detection:</div>';

                    foreach ($similarGroups as $groupIndex => $group) {
                        $groupId = 'group_' . $groupIndex;
                        echo '<div class="card mb-3">';
                        echo '<div class="card-header">';
                        echo '<h6>📚 Similar Publisher Group ' . ($groupIndex + 1) . ' (' . count($group) . ' variations)</h6>';
                        echo '<small class="text-muted">Total books: ' . array_sum(array_column($group, 'book_count')) . '</small>';
                        echo '</div>';
                        echo '<div class="card-body">';

                        echo '<div class="alert alert-warning">';
                        echo '<strong>👤 SELECT YOUR MASTER:</strong> Choose which publisher name to keep as the master record:';
                        echo '</div>';

                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Select Master</th><th>Publisher Name</th><th>ID</th><th>Books Using</th><th>Similarity Score</th></tr></thead>';
                        echo '<tbody>';

                        foreach ($group as $index => $publisher) {
                            $isRecommended = $index === 0; // First one (highest book count) is recommended
                            echo '<tr' . ($isRecommended ? ' class="table-warning" title="Recommended based on book usage"' : '') . '>';
                            echo '<td>';
                            echo '<input type="radio" name="master_' . $groupId . '" value="' . $publisher['id'] . '"' . ($isRecommended ? ' checked' : '') . ' onchange="updateMasterSelection(\'' . $groupId . '\', ' . $publisher['id'] . ', \'' . htmlspecialchars($publisher['name']) . '\')">';
                            echo '</td>';
                            echo '<td>';
                            echo htmlspecialchars($publisher['name']);
                            if ($isRecommended) echo ' <span class="badge bg-warning">RECOMMENDED</span>';
                            echo '</td>';
                            echo '<td>' . $publisher['id'] . '</td>';
                            echo '<td><span class="badge bg-info">' . $publisher['book_count'] . '</span></td>';
                            echo '<td>';
                            if ($index > 0) {
                                $similarity = calculateSimilarity($group[0]['name'], $publisher['name']);
                                echo '<span class="badge bg-secondary">' . round($similarity, 1) . '%</span>';
                            } else {
                                echo '<span class="text-muted">Base</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';

                        echo '<div class="mt-3">';
                        echo '<div class="alert alert-light border" id="master_preview_' . $groupId . '">';
                        echo '<strong>Selected Master:</strong> <span id="master_name_' . $groupId . '">' . htmlspecialchars($group[0]['name']) . '</span> (ID: <span id="master_id_' . $groupId . '">' . $group[0]['id'] . '</span>)';
                        echo '</div>';
                        echo '<button onclick="mergeGroupIntoMaster(\'' . $groupId . '\')" class="btn btn-success">🔧 Merge All into Selected Master</button>';
                        echo '<button onclick="previewMergeChanges(\'' . $groupId . '\')" class="btn btn-outline-info ms-2">👁️ Preview Changes</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 2. PUBLISHER RELATIONSHIPS TEST & CLEANUP
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>🔗 Publisher Relationships Analysis & Cleanup</h3></div>';
            echo '<div class="card-body">';

            try {
                // Test a specific book (Demon Dentist)
                $testBookId = 2105;
                $stmt = $db->prepare("
                    SELECT b.*, di.title as item_title
                    FROM books b
                    JOIN directory_items di ON b.directory_item_id = di.id
                    WHERE b.directory_item_id = ?
                ");
                $stmt->execute([$testBookId]);
                $testBook = $stmt->fetch();

                if ($testBook) {
                    echo '<h5>📋 Test Case: ' . htmlspecialchars($testBook['item_title']) . ' (ID: ' . $testBookId . ')</h5>';
                    echo '<div class="debug-output">';
                    echo "Publisher Field: " . ($testBook['publisher'] ?: 'NULL') . "\n";
                    echo "Publisher ID: " . ($testBook['publisher_id'] ?: 'NULL') . "\n";
                    echo '</div>';

                    // Check if publisher exists in authors table
                    if ($testBook['publisher']) {
                        $publisherName = trim($testBook['publisher']);

                        // Check for exact match
                        if ($hasAuthorTypeColumn) {
                            $stmt = $db->prepare("SELECT id, name, author_type FROM authors WHERE name = ?");
                        } else {
                            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name = ?");
                        }
                        $stmt->execute([$publisherName]);
                        $exactMatch = $stmt->fetch();

                        if ($exactMatch) {
                            echo '<div class="alert alert-success">';
                            echo "✅ Exact publisher match found: {$exactMatch['name']} (ID: {$exactMatch['id']})";
                            if (isset($exactMatch['author_type'])) {
                                echo " - Type: {$exactMatch['author_type']}";
                            }
                            echo '</div>';

                            // Check if relationship needs updating
                            if ($testBook['publisher_id'] != $exactMatch['id']) {
                                echo '<div class="alert alert-warning">';
                                echo "⚠️ Publisher relationship needs updating: Current ID ({$testBook['publisher_id']}) → Should be ({$exactMatch['id']})";
                                echo '<br><button onclick="updatePublisherRelationship(' . $testBookId . ', ' . $exactMatch['id'] . ')" class="btn btn-sm btn-primary mt-2">Fix Relationship</button>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-success">✅ Publisher relationship is correct</div>';
                            }
                        } else {
                            // Check for similar matches
                            $stmt = $db->prepare("SELECT id, name FROM authors WHERE name LIKE ?");
                            $stmt->execute(['%' . $publisherName . '%']);
                            $similarMatches = $stmt->fetchAll();

                            if (!empty($similarMatches)) {
                                echo '<div class="alert alert-info">';
                                echo "🔍 Similar publisher matches found:";
                                echo '<ul>';
                                foreach ($similarMatches as $match) {
                                    echo '<li>' . htmlspecialchars($match['name']) . ' (ID: ' . $match['id'] . ')';
                                    echo ' <button onclick="updatePublisherRelationship(' . $testBookId . ', ' . $match['id'] . ')" class="btn btn-xs btn-outline-primary">Use This</button></li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo "⚠️ No publisher match found for: " . htmlspecialchars($publisherName);
                                echo '<br><button onclick="createPublisher(\'' . htmlspecialchars($publisherName) . '\', ' . $testBookId . ')" class="btn btn-sm btn-success mt-2">Create Publisher</button>';
                                echo '</div>';
                            }
                        }
                    }
                } else {
                    echo '<div class="alert alert-warning">Test book (ID: ' . $testBookId . ') not found</div>';
                }

                // Show books with missing publisher relationships
                echo '<h5>📚 Books with Missing Publisher Relationships:</h5>';

                // First get the total count
                $stmt = $db->query("
                    SELECT COUNT(*) as total
                    FROM books b
                    JOIN directory_items di ON b.directory_item_id = di.id
                    WHERE b.publisher IS NOT NULL
                    AND b.publisher != ''
                    AND b.publisher_id IS NULL
                ");
                $totalMissing = $stmt->fetchColumn();

                if ($totalMissing == 0) {
                    echo '<div class="alert alert-success">✅ All books with publishers have relationships set</div>';
                } else {
                    echo '<div class="alert alert-warning">⚠️ Found ' . $totalMissing . ' books with missing publisher relationships:</div>';

                    // Show pagination controls
                    $page = $_GET['page'] ?? 1;
                    $perPage = 20;
                    $offset = ($page - 1) * $perPage;
                    $totalPages = ceil($totalMissing / $perPage);

                    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
                    echo '<div>';
                    echo '<button onclick="fixAllPublisherRelationships()" class="btn btn-success">🔧 Fix All Relationships</button>';
                    echo '<button onclick="bulkFixSelected()" class="btn btn-primary ms-2">🔧 Fix Selected</button>';
                    echo '</div>';
                    echo '<div>Page ' . $page . ' of ' . $totalPages . ' (Total: ' . $totalMissing . ')</div>';
                    echo '</div>';

                    // Get the actual data
                    $stmt = $db->prepare("
                        SELECT b.directory_item_id, di.title, b.publisher
                        FROM books b
                        JOIN directory_items di ON b.directory_item_id = di.id
                        WHERE b.publisher IS NOT NULL
                        AND b.publisher != ''
                        AND b.publisher_id IS NULL
                        ORDER BY di.title
                        LIMIT ? OFFSET ?
                    ");
                    $stmt->execute([$perPage, $offset]);
                    $missingRelationships = $stmt->fetchAll();

                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr>';
                    echo '<th><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>';
                    echo '<th>Book</th><th>Publisher</th><th>Action</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    foreach ($missingRelationships as $book) {
                        echo '<tr>';
                        echo '<td><input type="checkbox" class="book-checkbox" value="' . $book['directory_item_id'] . '"></td>';
                        echo '<td>' . htmlspecialchars($book['title']) . '</td>';
                        echo '<td>' . htmlspecialchars($book['publisher']) . '</td>';
                        echo '<td><button onclick="fixPublisherRelationship(' . $book['directory_item_id'] . ')" class="btn btn-xs btn-primary">Fix</button></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';

                    // Pagination links
                    if ($totalPages > 1) {
                        echo '<nav><ul class="pagination">';
                        for ($i = 1; $i <= $totalPages; $i++) {
                            $active = $i == $page ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        echo '</ul></nav>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 3. TAGS (Genres)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>🏷️ Tags (Genres) Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('tags')) {
                    $sql = "
                        SELECT
                            MIN(name) as name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM tags
                        GROUP BY LOWER(TRIM(name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateTags)) {
                        echo '<div class="alert alert-success">✅ No exact duplicate tags found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateTags) . ' groups of duplicate tags:</div>';

                        foreach ($duplicateTags as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';

                            // Show usage
                            $ids = explode(',', $group['ids']);
                            foreach ($ids as $id) {
                                $id = trim($id);

                                // Check directory_item_tags
                                if (getTableInfo('directory_item_tags')) {
                                    $stmt = $db->prepare("SELECT COUNT(*) FROM directory_item_tags WHERE tag_id = ?");
                                    $stmt->execute([$id]);
                                    $itemCount = $stmt->fetchColumn();
                                    if ($itemCount > 0) {
                                        echo "ID $id: $itemCount directory items<br>";
                                    }
                                }

                                // Check story_tags
                                if (getTableInfo('story_tags')) {
                                    $stmt = $db->prepare("SELECT COUNT(*) FROM story_tags WHERE tag_id = ?");
                                    $stmt->execute([$id]);
                                    $storyCount = $stmt->fetchColumn();
                                    if ($storyCount > 0) {
                                        echo "ID $id: $storyCount stories<br>";
                                    }
                                }
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Tags table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 3. PRICE RANGES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>💰 Price Ranges Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('price_ranges')) {
                    $sql = "
                        SELECT
                            MIN(range_name) as range_name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM price_ranges
                        GROUP BY LOWER(TRIM(range_name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(range_name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicatePriceRanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicatePriceRanges)) {
                        echo '<div class="alert alert-success">✅ No duplicate price ranges found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicatePriceRanges) . ' groups of duplicate price ranges:</div>';

                        foreach ($duplicatePriceRanges as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['range_name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Price ranges table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 4. AGE RANGES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>👶 Age Ranges Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                // Check both age_ranges table and books.age_range field
                $hasAgeRangesTable = getTableInfo('age_ranges');
                $hasBooksAgeRange = columnExists('books', 'age_range');

                echo '<div class="debug-output">';
                echo "Age range sources:\n";
                echo "- age_ranges table exists: " . ($hasAgeRangesTable ? 'YES' : 'NO') . "\n";
                echo "- books.age_range column exists: " . ($hasBooksAgeRange ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasAgeRangesTable) {
                    $sql = "
                        SELECT
                            MIN(range_name) as range_name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM age_ranges
                        GROUP BY LOWER(TRIM(range_name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(range_name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateAgeRanges = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateAgeRanges)) {
                        echo '<div class="alert alert-success">✅ No duplicate age ranges in table</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateAgeRanges) . ' groups of duplicate age ranges:</div>';

                        foreach ($duplicateAgeRanges as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['range_name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';
                            echo '</div>';
                        }
                    }
                }

                if ($hasBooksAgeRange) {
                    // Analyze text values in books.age_range
                    $sql = "
                        SELECT
                            MIN(age_range) as age_range,
                            COUNT(*) as count
                        FROM books
                        WHERE age_range IS NOT NULL AND age_range != ''
                        GROUP BY LOWER(TRIM(age_range))
                        ORDER BY count DESC, MIN(age_range)
                    ";
                    $stmt = $db->query($sql);
                    $ageRangeValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo '<h5>Age Range Values in Books:</h5>';
                    if (empty($ageRangeValues)) {
                        echo '<div class="alert alert-info">No age range values found in books</div>';
                    } else {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Age Range</th><th>Book Count</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($ageRangeValues as $value) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($value['age_range']) . '</td>';
                            echo '<td><span class="badge bg-info">' . $value['count'] . '</span></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 5. READING LEVELS
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📚 Reading Levels Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                $hasReadingLevel = columnExists('books', 'reading_level');

                echo '<div class="debug-output">';
                echo "Reading level sources:\n";
                echo "- books.reading_level column exists: " . ($hasReadingLevel ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasReadingLevel) {
                    $sql = "
                        SELECT
                            MIN(reading_level) as reading_level,
                            COUNT(*) as count
                        FROM books
                        WHERE reading_level IS NOT NULL AND reading_level != ''
                        GROUP BY LOWER(TRIM(reading_level))
                        ORDER BY count DESC, MIN(reading_level)
                    ";
                    $stmt = $db->query($sql);
                    $readingLevelValues = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($readingLevelValues)) {
                        echo '<div class="alert alert-info">No reading level values found in books</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Current reading levels need standardization. Found inconsistent values:</div>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Current Reading Level</th><th>Book Count</th><th>Suggested Standard</th><th>Action</th></tr></thead>';
                        echo '<tbody>';

                        // Show synchronization warning
                        echo '<div class="alert alert-info">';
                        echo '<strong>📋 SYNCHRONIZED SYSTEM:</strong> Reading levels and age ranges are linked. ';
                        echo 'Changes to reading levels will automatically update corresponding age ranges to maintain consistency.';
                        echo '</div>';

                        // Define standard reading levels mapping (SYNCHRONIZED with age ranges)
                        $standardLevels = [
                            'middle-grade' => 'Transitional Reader (7-8 years)',
                            'Middle Grade' => 'Transitional Reader (7-8 years)',
                            'chapter-book' => 'Fluent Reader (8-11 years)',
                            'early reader' => 'Early Reader (5-6 years)',
                            'picture book' => 'Beginning Reader (4-5 years)',
                            'young adult' => 'Advanced Reader (14-16 years)',
                            'adult' => 'Proficient Reader (18+ years)',
                            'beginner' => 'Beginning Reader (4-5 years)',
                            'intermediate' => 'Developing Reader (6-7 years)',
                            'advanced' => 'Advanced Reader (11-14 years)'
                        ];

                        foreach ($readingLevelValues as $value) {
                            $current = $value['reading_level'];
                            $suggested = $standardLevels[strtolower($current)] ?? 'Needs Manual Review';

                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($current) . '</td>';
                            echo '<td><span class="badge bg-info">' . $value['count'] . '</span></td>';
                            echo '<td>' . htmlspecialchars($suggested) . '</td>';
                            echo '<td>';
                            if ($suggested !== 'Needs Manual Review') {
                                echo '<button onclick="standardizeReadingLevel(\'' . htmlspecialchars($current) . '\', \'' . htmlspecialchars($suggested) . '\')" class="btn btn-xs btn-success">Standardize</button>';
                            } else {
                                echo '<span class="text-muted">Manual review needed</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';

                        echo '<div class="mt-3">';
                        echo '<div class="alert alert-light border">';
                        echo '<h6>🔧 Standardization Tools:</h6>';
                        echo '<p><strong>📚 Create Standard Reading Levels System:</strong> Creates lookup tables with UK education system standards (Reception to A-levels) with synchronized age ranges and Lexile mappings.</p>';
                        echo '<p><strong>🔄 Migrate All to Standards:</strong> Automatically converts all existing reading level values to the standardized system and updates corresponding age ranges to maintain synchronization.</p>';
                        echo '<p><strong>🔗 Synchronize Age Ranges:</strong> Maps all existing age range values in books to match the standard reading level age groups, ensuring consistency between reading levels and age ranges.</p>';
                        echo '<button onclick="fixAgeRangeSync()" class="btn btn-warning me-2">🔧 Fix Age Range Sync Issues</button>';
                        echo '</div>';
                        echo '<button onclick="createStandardReadingLevels()" class="btn btn-primary">📚 Create Standard Reading Levels System</button>';
                        echo '<button onclick="migrateAllReadingLevels()" class="btn btn-warning ms-2">🔄 Migrate All to Standards</button>';
                        echo '<button onclick="synchronizeAgeRanges()" class="btn btn-info ms-2">🔗 Synchronize Age Ranges</button>';
                        echo '</div>';

                        // Show the proposed standard system
                        echo '<div class="mt-4">';
                        echo '<h6>📋 Proposed Standard Reading Levels System:</h6>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm table-striped">';
                        echo '<thead><tr><th>Age Group</th><th>School Year</th><th>Reading Stage</th><th>Lexile Range</th><th>Typical Skills</th></tr></thead>';
                        echo '<tbody>';
                        echo '<tr><td>0-12 months</td><td>-</td><td>Pre-literacy (Sensory)</td><td>N/A</td><td>Listening to voices, looking at pictures</td></tr>';
                        echo '<tr><td>12-24 months</td><td>-</td><td>Pre-literacy (Naming)</td><td>N/A</td><td>Responding to stories, pointing at objects</td></tr>';
                        echo '<tr><td>2-3 years</td><td>-</td><td>Pre-literacy (Mimicry)</td><td>BR</td><td>Repeating phrases, "reading" from memory</td></tr>';
                        echo '<tr><td>3-4 years</td><td>-</td><td>Early Pre-reader</td><td>BR</td><td>Identifying letters, understanding sequences</td></tr>';
                        echo '<tr><td>4-5 years</td><td>Reception</td><td>Beginning Reader</td><td>BR-120L</td><td>Introduction to phonics, basic sentences</td></tr>';
                        echo '<tr><td>5-6 years</td><td>Year 1</td><td>Early Reader</td><td>120L-220L</td><td>Development of decoding skills</td></tr>';
                        echo '<tr><td>6-7 years</td><td>Year 2</td><td>Developing Reader</td><td>220L-420L</td><td>Enhancement of fluency and comprehension</td></tr>';
                        echo '<tr><td>7-8 years</td><td>Year 3</td><td>Transitional Reader</td><td>420L-620L</td><td>Transition from learning to read to reading to learn</td></tr>';
                        echo '<tr><td>8-9 years</td><td>Year 4</td><td>Fluent Reader</td><td>620L-820L</td><td>Exposure to variety of genres</td></tr>';
                        echo '<tr><td>9-10 years</td><td>Year 5</td><td>Fluent Reader</td><td>820L-940L</td><td>More complex texts and analysis</td></tr>';
                        echo '<tr><td>10-11 years</td><td>Year 6</td><td>Fluent Reader</td><td>940L-1000L+</td><td>Advanced comprehension skills</td></tr>';
                        echo '<tr><td>11-14 years</td><td>Years 7-9</td><td>Advanced Reader</td><td>1000L-1100L+</td><td>Critical reading and text analysis</td></tr>';
                        echo '<tr><td>14-16 years</td><td>Years 10-11</td><td>Advanced Reader</td><td>1100L-1200L+</td><td>GCSE preparation, literature study</td></tr>';
                        echo '<tr><td>16-18 years</td><td>Years 12-13</td><td>Advanced Reader</td><td>1200L-1300L+</td><td>A-level analytical skills</td></tr>';
                        echo '<tr><td>18+ years</td><td>Adult</td><td>Proficient Reader</td><td>1300L-1600L+</td><td>Professional and academic reading</td></tr>';
                        echo '</tbody></table>';
                        echo '</div>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 6. DIRECTORY CATEGORIES
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📁 Directory Categories Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                if (getTableInfo('directory_categories')) {
                    $sql = "
                        SELECT
                            MIN(name) as name,
                            COUNT(*) as count,
                            GROUP_CONCAT(id) as ids
                        FROM directory_categories
                        GROUP BY LOWER(TRIM(name))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(name)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateCategories)) {
                        echo '<div class="alert alert-success">✅ No duplicate directory categories found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateCategories) . ' groups of duplicate categories:</div>';

                        foreach ($duplicateCategories as $group) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($group['name']) . '</strong> (' . $group['count'] . ' duplicates)<br>';
                            echo 'IDs: ' . $group['ids'] . '<br>';

                            // Show usage
                            $ids = explode(',', $group['ids']);
                            foreach ($ids as $id) {
                                $id = trim($id);
                                $stmt = $db->prepare("SELECT COUNT(*) FROM directory_items WHERE category_id = ?");
                                $stmt->execute([$id]);
                                $itemCount = $stmt->fetchColumn();
                                if ($itemCount > 0) {
                                    echo "ID $id: $itemCount directory items<br>";
                                }
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    echo '<div class="alert alert-info">Directory categories table not found</div>';
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 7. SERIES (Text field analysis)
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>📖 Series Analysis</h3></div>';
            echo '<div class="card-body">';

            try {
                $hasSeries = columnExists('books', 'series');

                echo '<div class="debug-output">';
                echo "Series sources:\n";
                echo "- books.series column exists: " . ($hasSeries ? 'YES' : 'NO') . "\n";
                echo '</div>';

                if ($hasSeries) {
                    $sql = "
                        SELECT
                            MIN(series) as series,
                            COUNT(*) as count
                        FROM books
                        WHERE series IS NOT NULL AND series != ''
                        GROUP BY LOWER(TRIM(series))
                        HAVING count > 1
                        ORDER BY count DESC, MIN(series)
                    ";
                    $stmt = $db->query($sql);
                    $duplicateSeries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($duplicateSeries)) {
                        echo '<div class="alert alert-success">✅ No duplicate series found</div>';
                    } else {
                        echo '<div class="alert alert-warning">⚠️ Found ' . count($duplicateSeries) . ' potential series duplicates:</div>';

                        foreach ($duplicateSeries as $series) {
                            echo '<div class="duplicate-group">';
                            echo '<strong>' . htmlspecialchars($series['series']) . '</strong> (' . $series['count'] . ' books)<br>';
                            echo '</div>';
                        }
                    }

                    // Show all unique series for reference and detect erroneous data
                    $sql = "
                        SELECT
                            series,
                            COUNT(*) as count,
                            LENGTH(series) as length
                        FROM books
                        WHERE series IS NOT NULL AND series != ''
                        GROUP BY series
                        ORDER BY length DESC, count DESC, series
                        LIMIT 50
                    ";
                    $stmt = $db->query($sql);
                    $allSeries = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($allSeries)) {
                        echo '<h5>Series Data Analysis (Top 50 by length):</h5>';
                        echo '<div class="alert alert-warning">⚠️ Long series values may contain erroneous data (descriptions, author bios, etc.)</div>';
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead><tr><th>Series Name</th><th>Length</th><th>Book Count</th><th>Action</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($allSeries as $series) {
                            $isErroneous = strlen($series['series']) > 100 ||
                                          strpos(strtolower($series['series']), 'studied') !== false ||
                                          strpos(strtolower($series['series']), 'oxford') !== false ||
                                          strpos(strtolower($series['series']), 'author') !== false ||
                                          strpos(strtolower($series['series']), 'writing') !== false ||
                                          strpos(strtolower($series['series']), 'publisher') !== false ||
                                          strpos(strtolower($series['series']), 'novel') !== false;

                            echo '<tr' . ($isErroneous ? ' class="table-danger"' : '') . '>';
                            echo '<td>';
                            if (strlen($series['series']) > 100) {
                                echo htmlspecialchars(substr($series['series'], 0, 100)) . '...';
                            } else {
                                echo htmlspecialchars($series['series']);
                            }
                            if ($isErroneous) echo ' <span class="badge bg-danger">ERRONEOUS</span>';
                            echo '</td>';
                            echo '<td><span class="badge bg-info">' . $series['length'] . '</span></td>';
                            echo '<td><span class="badge bg-info">' . $series['count'] . '</span></td>';
                            echo '<td>';
                            if ($isErroneous) {
                                echo '<button onclick="cleanErroneousData(\'series\', ' . htmlspecialchars(json_encode($series['series'])) . ')" class="btn btn-xs btn-danger">Delete</button> ';
                                echo '<button onclick="editErroneousData(\'series\', ' . htmlspecialchars(json_encode($series['series'])) . ')" class="btn btn-xs btn-warning">Edit</button>';
                            } else {
                                echo '<span class="text-muted">OK</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';

                        // Show erroneous data cleanup tools
                        echo '<div class="mt-3">';
                        echo '<button onclick="cleanAllErroneousSeriesData()" class="btn btn-danger">🗑️ Clean All Erroneous Series Data</button>';
                        echo '<button onclick="showErroneousDataModal()" class="btn btn-warning ms-2">🔍 Review All Erroneous Data</button>';
                        echo '</div>';
                    }
                }

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            // 8. COMPREHENSIVE DATABASE SANITY CHECK
            echo '<div class="card mb-4">';
            echo '<div class="card-header"><h3>🔍 Comprehensive Database Sanity Check</h3></div>';
            echo '<div class="card-body">';

            try {
                $sanityIssues = [];
                $sanityStats = [];

                // Check directory_items table
                echo '<h5>📁 Directory Items Analysis</h5>';

                $stmt = $db->query("SELECT COUNT(*) FROM directory_items");
                $totalItems = $stmt->fetchColumn();
                $sanityStats['directory_items_total'] = $totalItems;

                $stmt = $db->query("SELECT COUNT(*) FROM directory_items WHERE title IS NULL OR title = ''");
                $nullTitles = $stmt->fetchColumn();
                if ($nullTitles > 0) {
                    $sanityIssues[] = "❌ $nullTitles directory items have NULL/empty titles";
                }
                $sanityStats['directory_items_null_titles'] = $nullTitles;

                $stmt = $db->query("SELECT COUNT(*) FROM directory_items WHERE slug IS NULL OR slug = ''");
                $nullSlugs = $stmt->fetchColumn();
                if ($nullSlugs > 0) {
                    $sanityIssues[] = "❌ $nullSlugs directory items have NULL/empty slugs";
                }
                $sanityStats['directory_items_null_slugs'] = $nullSlugs;

                $stmt = $db->query("SELECT COUNT(*) FROM directory_items WHERE type IS NULL OR type = ''");
                $nullTypes = $stmt->fetchColumn();
                if ($nullTypes > 0) {
                    $sanityIssues[] = "❌ $nullTypes directory items have NULL/empty types";
                }
                $sanityStats['directory_items_null_types'] = $nullTypes;

                // Check for duplicate slugs
                $stmt = $db->query("SELECT slug, COUNT(*) as count FROM directory_items WHERE slug IS NOT NULL GROUP BY slug HAVING COUNT(*) > 1");
                $duplicateSlugs = $stmt->fetchAll();
                if (!empty($duplicateSlugs)) {
                    $sanityIssues[] = "❌ " . count($duplicateSlugs) . " duplicate slugs found in directory_items";
                }
                $sanityStats['directory_items_duplicate_slugs'] = count($duplicateSlugs);

                echo '<div class="alert alert-light border">';
                echo "<p><strong>Directory Items:</strong> $totalItems total, $nullTitles null titles, $nullSlugs null slugs, $nullTypes null types</p>";
                if (!empty($duplicateSlugs)) {
                    echo "<p><strong>Duplicate Slugs:</strong></p><ul>";
                    foreach ($duplicateSlugs as $dup) {
                        echo "<li>{$dup['slug']} ({$dup['count']} times)</li>";
                    }
                    echo "</ul>";
                }
                echo '</div>';

                // Check books table
                echo '<h5>📚 Books Table Analysis</h5>';

                $stmt = $db->query("SELECT COUNT(*) FROM books");
                $totalBooks = $stmt->fetchColumn();
                $sanityStats['books_total'] = $totalBooks;

                $stmt = $db->query("SELECT COUNT(*) FROM books WHERE directory_item_id IS NULL");
                $booksNullDirId = $stmt->fetchColumn();
                if ($booksNullDirId > 0) {
                    $sanityIssues[] = "❌ $booksNullDirId books have NULL directory_item_id";
                }
                $sanityStats['books_null_directory_item_id'] = $booksNullDirId;

                $stmt = $db->query("SELECT COUNT(*) FROM books WHERE title IS NULL OR title = ''");
                $booksNullTitles = $stmt->fetchColumn();
                if ($booksNullTitles > 0) {
                    $sanityIssues[] = "❌ $booksNullTitles books have NULL/empty titles";
                }
                $sanityStats['books_null_titles'] = $booksNullTitles;

                // Check for orphaned books (directory_item doesn't exist)
                $stmt = $db->query("SELECT COUNT(*) FROM books b LEFT JOIN directory_items di ON b.directory_item_id = di.id WHERE di.id IS NULL AND b.directory_item_id IS NOT NULL");
                $orphanedBooks = $stmt->fetchColumn();
                if ($orphanedBooks > 0) {
                    $sanityIssues[] = "❌ $orphanedBooks books reference non-existent directory items";
                }
                $sanityStats['books_orphaned'] = $orphanedBooks;

                // Check for duplicate books
                $stmt = $db->query("SELECT directory_item_id, COUNT(*) as count FROM books WHERE directory_item_id IS NOT NULL GROUP BY directory_item_id HAVING COUNT(*) > 1");
                $duplicateBooks = $stmt->fetchAll();
                if (!empty($duplicateBooks)) {
                    $sanityIssues[] = "❌ " . count($duplicateBooks) . " directory items have multiple book records";
                }
                $sanityStats['books_duplicates'] = count($duplicateBooks);

                echo '<div class="alert alert-light border">';
                echo "<p><strong>Books:</strong> $totalBooks total, $booksNullDirId null dir_id, $booksNullTitles null titles, $orphanedBooks orphaned</p>";
                if (!empty($duplicateBooks)) {
                    echo "<p><strong>Duplicate Books:</strong></p><ul>";
                    foreach (array_slice($duplicateBooks, 0, 5) as $dup) {
                        echo "<li>Directory Item {$dup['directory_item_id']} has {$dup['count']} book records</li>";
                    }
                    if (count($duplicateBooks) > 5) {
                        echo "<li>... and " . (count($duplicateBooks) - 5) . " more</li>";
                    }
                    echo "</ul>";
                }
                echo '</div>';

                // Check authors table
                echo '<h5>👤 Authors Table Analysis</h5>';

                $stmt = $db->query("SELECT COUNT(*) FROM authors");
                $totalAuthors = $stmt->fetchColumn();
                $sanityStats['authors_total'] = $totalAuthors;

                $stmt = $db->query("SELECT COUNT(*) FROM authors WHERE name IS NULL OR name = ''");
                $authorsNullNames = $stmt->fetchColumn();
                if ($authorsNullNames > 0) {
                    $sanityIssues[] = "❌ $authorsNullNames authors have NULL/empty names";
                }
                $sanityStats['authors_null_names'] = $authorsNullNames;

                $stmt = $db->query("SELECT COUNT(*) FROM authors WHERE slug IS NULL OR slug = ''");
                $authorsNullSlugs = $stmt->fetchColumn();
                if ($authorsNullSlugs > 0) {
                    $sanityIssues[] = "❌ $authorsNullSlugs authors have NULL/empty slugs";
                }
                $sanityStats['authors_null_slugs'] = $authorsNullSlugs;

                // Check for duplicate author names
                $stmt = $db->query("SELECT name, COUNT(*) as count FROM authors WHERE name IS NOT NULL GROUP BY name HAVING COUNT(*) > 1");
                $duplicateAuthors = $stmt->fetchAll();
                if (!empty($duplicateAuthors)) {
                    $sanityIssues[] = "❌ " . count($duplicateAuthors) . " duplicate author names found";
                }
                $sanityStats['authors_duplicates'] = count($duplicateAuthors);

                echo '<div class="alert alert-light border">';
                echo "<p><strong>Authors:</strong> $totalAuthors total, $authorsNullNames null names, $authorsNullSlugs null slugs</p>";
                if (!empty($duplicateAuthors)) {
                    echo "<p><strong>Duplicate Authors:</strong></p><ul>";
                    foreach (array_slice($duplicateAuthors, 0, 5) as $dup) {
                        echo "<li>{$dup['name']} ({$dup['count']} times)</li>";
                    }
                    if (count($duplicateAuthors) > 5) {
                        echo "<li>... and " . (count($duplicateAuthors) - 5) . " more</li>";
                    }
                    echo "</ul>";
                }
                echo '</div>';

                // Check tags table
                echo '<h5>🏷️ Tags Table Analysis</h5>';

                $stmt = $db->query("SELECT COUNT(*) FROM tags");
                $totalTags = $stmt->fetchColumn();
                $sanityStats['tags_total'] = $totalTags;

                $stmt = $db->query("SELECT COUNT(*) FROM tags WHERE name IS NULL OR name = ''");
                $tagsNullNames = $stmt->fetchColumn();
                if ($tagsNullNames > 0) {
                    $sanityIssues[] = "❌ $tagsNullNames tags have NULL/empty names";
                }
                $sanityStats['tags_null_names'] = $tagsNullNames;

                $stmt = $db->query("SELECT COUNT(*) FROM tags WHERE slug IS NULL OR slug = ''");
                $tagsNullSlugs = $stmt->fetchColumn();
                if ($tagsNullSlugs > 0) {
                    $sanityIssues[] = "❌ $tagsNullSlugs tags have NULL/empty slugs";
                }
                $sanityStats['tags_null_slugs'] = $tagsNullSlugs;

                echo '<div class="alert alert-light border">';
                echo "<p><strong>Tags:</strong> $totalTags total, $tagsNullNames null names, $tagsNullSlugs null slugs</p>";
                echo '</div>';

                // Check relationship tables
                echo '<h5>🔗 Relationship Tables Analysis</h5>';

                // Check directory_item_tags
                $stmt = $db->query("SELECT COUNT(*) FROM directory_item_tags");
                $totalDirTags = $stmt->fetchColumn();
                $sanityStats['directory_item_tags_total'] = $totalDirTags;

                $stmt = $db->query("SELECT COUNT(*) FROM directory_item_tags dit LEFT JOIN directory_items di ON dit.directory_item_id = di.id WHERE di.id IS NULL");
                $orphanedDirTags = $stmt->fetchColumn();
                if ($orphanedDirTags > 0) {
                    $sanityIssues[] = "❌ $orphanedDirTags directory_item_tags reference non-existent directory items";
                }
                $sanityStats['directory_item_tags_orphaned_items'] = $orphanedDirTags;

                $stmt = $db->query("SELECT COUNT(*) FROM directory_item_tags dit LEFT JOIN tags t ON dit.tag_id = t.id WHERE t.id IS NULL");
                $orphanedTagRefs = $stmt->fetchColumn();
                if ($orphanedTagRefs > 0) {
                    $sanityIssues[] = "❌ $orphanedTagRefs directory_item_tags reference non-existent tags";
                }
                $sanityStats['directory_item_tags_orphaned_tags'] = $orphanedTagRefs;

                // Check story_authors if it exists
                $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
                if ($stmt->fetch()) {
                    $stmt = $db->query("SELECT COUNT(*) FROM story_authors");
                    $totalStoryAuthors = $stmt->fetchColumn();
                    $sanityStats['story_authors_total'] = $totalStoryAuthors;

                    $stmt = $db->query("SELECT COUNT(*) FROM story_authors sa LEFT JOIN directory_items di ON sa.story_id = di.id WHERE di.id IS NULL");
                    $orphanedStoryAuthors = $stmt->fetchColumn();
                    if ($orphanedStoryAuthors > 0) {
                        $sanityIssues[] = "❌ $orphanedStoryAuthors story_authors reference non-existent stories";
                    }
                    $sanityStats['story_authors_orphaned_stories'] = $orphanedStoryAuthors;

                    $stmt = $db->query("SELECT COUNT(*) FROM story_authors sa LEFT JOIN authors a ON sa.author_id = a.id WHERE a.id IS NULL");
                    $orphanedAuthorRefs = $stmt->fetchColumn();
                    if ($orphanedAuthorRefs > 0) {
                        $sanityIssues[] = "❌ $orphanedAuthorRefs story_authors reference non-existent authors";
                    }
                    $sanityStats['story_authors_orphaned_authors'] = $orphanedAuthorRefs;

                    echo '<div class="alert alert-light border">';
                    echo "<p><strong>Relationships:</strong> $totalDirTags dir-tag links, $totalStoryAuthors story-author links</p>";
                    echo "<p><strong>Orphaned:</strong> $orphanedDirTags dir-tags, $orphanedTagRefs tag-refs, $orphanedStoryAuthors story-authors, $orphanedAuthorRefs author-refs</p>";
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-light border">';
                    echo "<p><strong>Relationships:</strong> $totalDirTags dir-tag links</p>";
                    echo "<p><strong>Orphaned:</strong> $orphanedDirTags dir-tags, $orphanedTagRefs tag-refs</p>";
                    echo '</div>';
                }

                // Overall sanity summary
                echo '<h5>📊 Overall Database Health</h5>';

                if (empty($sanityIssues)) {
                    echo '<div class="alert alert-success">';
                    echo '<h6>🎉 Database is HEALTHY!</h6>';
                    echo '<p>✅ No critical issues found</p>';
                    echo '<p>✅ All relationships intact</p>';
                    echo '<p>✅ No orphaned records</p>';
                    echo '<p>✅ No NULL values in critical fields</p>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning">';
                    echo '<h6>⚠️ Database Issues Found</h6>';
                    echo '<ul>';
                    foreach ($sanityIssues as $issue) {
                        echo "<li>$issue</li>";
                    }
                    echo '</ul>';
                    echo '<p><strong>Total Issues: ' . count($sanityIssues) . '</strong></p>';
                    echo '</div>';
                }

                // Quick stats table
                echo '<h6>📈 Database Statistics</h6>';
                echo '<table class="table table-sm table-bordered">';
                echo '<thead><tr><th>Table/Metric</th><th>Count</th><th>Status</th></tr></thead>';
                echo '<tbody>';

                $healthyColor = 'table-success';
                $warningColor = 'table-warning';
                $dangerColor = 'table-danger';

                foreach ($sanityStats as $metric => $value) {
                    $status = '✅ OK';
                    $color = $healthyColor;

                    if (strpos($metric, 'null') !== false && $value > 0) {
                        $status = '⚠️ Issues';
                        $color = $warningColor;
                    }
                    if (strpos($metric, 'orphaned') !== false && $value > 0) {
                        $status = '❌ Critical';
                        $color = $dangerColor;
                    }
                    if (strpos($metric, 'duplicate') !== false && $value > 0) {
                        $status = '⚠️ Review';
                        $color = $warningColor;
                    }

                    echo "<tr class='$color'>";
                    echo "<td>" . str_replace('_', ' ', ucwords($metric, '_')) . "</td>";
                    echo "<td>$value</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                echo '</tbody></table>';

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            echo '</div></div>';

            ?>
    </div>

    <script>
    function updatePublisherRelationship(bookId, publisherId) {
        if (!confirm('Update publisher relationship for book ID ' + bookId + ' to publisher ID ' + publisherId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Updating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_publisher_relationship&book_id=' + bookId + '&publisher_id=' + publisherId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher relationship updated successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function createPublisher(publisherName, bookId) {
        if (!confirm('Create new publisher "' + publisherName + '" and link to book ID ' + bookId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Creating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_publisher&publisher_name=' + encodeURIComponent(publisherName) + '&book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher created and linked successfully!\nPublisher ID: ' + data.publisher_id);
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function fixPublisherRelationship(bookId) {
        if (!confirm('Automatically fix publisher relationship for book ID ' + bookId + '?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Fixing...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_publisher_relationship&book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher relationship fixed!\n' + (data.message || ''));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.book-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }

    function bulkFixSelected() {
        const checkboxes = document.querySelectorAll('.book-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one book to fix.');
            return;
        }

        if (!confirm('Fix publisher relationships for ' + checkboxes.length + ' selected books?')) {
            return;
        }

        const bookIds = Array.from(checkboxes).map(cb => cb.value);

        // Show progress
        const progressDiv = document.createElement('div');
        progressDiv.className = 'alert alert-info';
        progressDiv.innerHTML = 'Processing ' + bookIds.length + ' books... <div class="progress"><div class="progress-bar" style="width: 0%"></div></div>';
        document.querySelector('.table-responsive').before(progressDiv);

        // Process books one by one
        let processed = 0;
        const processNext = () => {
            if (processed >= bookIds.length) {
                progressDiv.className = 'alert alert-success';
                progressDiv.innerHTML = '✅ Completed processing ' + bookIds.length + ' books!';
                setTimeout(() => location.reload(), 2000);
                return;
            }

            const bookId = bookIds[processed];
            const progress = ((processed + 1) / bookIds.length) * 100;
            progressDiv.querySelector('.progress-bar').style.width = progress + '%';

            fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fix_publisher_relationship&book_id=' + bookId
            })
            .then(response => response.json())
            .then(data => {
                processed++;
                processNext();
            })
            .catch(error => {
                console.error('Error processing book ' + bookId + ':', error);
                processed++;
                processNext();
            });
        };

        processNext();
    }

    function fixAllPublisherRelationships() {
        if (!confirm('This will attempt to fix ALL missing publisher relationships. This may take several minutes. Continue?')) {
            return;
        }

        // Show progress
        const progressDiv = document.createElement('div');
        progressDiv.className = 'alert alert-info';
        progressDiv.innerHTML = 'Processing all missing relationships... <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>';
        document.querySelector('.table-responsive').before(progressDiv);

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_all_publisher_relationships'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                progressDiv.className = 'alert alert-success';
                progressDiv.innerHTML = '✅ ' + (data.message || 'All publisher relationships processed!');
                setTimeout(() => location.reload(), 3000);
            } else {
                progressDiv.className = 'alert alert-danger';
                progressDiv.innerHTML = '❌ Error: ' + (data.message || 'Unknown error');
            }
        })
        .catch(error => {
            progressDiv.className = 'alert alert-danger';
            progressDiv.innerHTML = '❌ Network error: ' + error.message;
        });
    }

    // Publisher consolidation functions
    function consolidateAuthors(ids, name) {
        if (!confirm('Consolidate duplicate authors/publishers for "' + name + '"?\nIDs: ' + ids)) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Consolidating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=consolidate_authors&ids=' + encodeURIComponent(ids) + '&name=' + encodeURIComponent(name)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Authors/publishers consolidated successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    // Dynamic master selection functions
    function updateMasterSelection(groupId, masterId, masterName) {
        document.getElementById('master_name_' + groupId).textContent = masterName;
        document.getElementById('master_id_' + groupId).textContent = masterId;
    }

    function previewMergeChanges(groupId) {
        const selectedRadio = document.querySelector('input[name="master_' + groupId + '"]:checked');
        if (!selectedRadio) {
            alert('Please select a master publisher first');
            return;
        }

        const masterId = selectedRadio.value;
        const masterName = document.getElementById('master_name_' + groupId).textContent;

        // Get all publishers in this group
        const allRadios = document.querySelectorAll('input[name="master_' + groupId + '"]');
        const publisherIds = Array.from(allRadios).map(radio => radio.value);
        const otherIds = publisherIds.filter(id => id !== masterId);

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=preview_merge_changes&master_id=' + masterId + '&other_ids=' + encodeURIComponent(otherIds.join(','))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let preview = 'MERGE PREVIEW:\n\n';
                preview += 'Master Publisher: ' + masterName + ' (ID: ' + masterId + ')\n\n';
                preview += 'Changes that will be made:\n';
                preview += '• Books to update: ' + (data.books_to_update || 0) + '\n';
                preview += '• Publishers to remove: ' + (data.publishers_to_remove || 0) + '\n\n';
                if (data.affected_books && data.affected_books.length > 0) {
                    preview += 'Sample affected books:\n';
                    data.affected_books.slice(0, 5).forEach(book => {
                        preview += '  - ' + book.title + '\n';
                    });
                    if (data.affected_books.length > 5) {
                        preview += '  ... and ' + (data.affected_books.length - 5) + ' more\n';
                    }
                }
                alert(preview);
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
        });
    }

    function mergeGroupIntoMaster(groupId) {
        const selectedRadio = document.querySelector('input[name="master_' + groupId + '"]:checked');
        if (!selectedRadio) {
            alert('Please select a master publisher first');
            return;
        }

        const masterId = selectedRadio.value;
        const masterName = document.getElementById('master_name_' + groupId).textContent;

        // Get all publishers in this group
        const allRadios = document.querySelectorAll('input[name="master_' + groupId + '"]');
        const publisherIds = Array.from(allRadios).map(radio => radio.value);
        const otherIds = publisherIds.filter(id => id !== masterId);

        if (!confirm('Merge all publishers in this group into "' + masterName + '"?\n\nThis will:\n• Update all books to use the master publisher\n• Remove the other publisher records\n• Cannot be undone\n\nContinue?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Merging...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=merge_group_into_master&master_id=' + masterId + '&other_ids=' + encodeURIComponent(otherIds.join(',')) + '&master_name=' + encodeURIComponent(masterName)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publishers merged successfully!\n\nMaster: ' + masterName + '\nBooks updated: ' + (data.books_updated || 0) + '\nPublishers removed: ' + (data.publishers_removed || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function mergeIntoMaster(sourceId, masterId, sourceName, masterName) {
        if (!confirm('Merge "' + sourceName + '" (ID: ' + sourceId + ') into master "' + masterName + '" (ID: ' + masterId + ')?\n\nThis will:\n- Update all books using source publisher to use master\n- Delete the source publisher record\n- Keep the master publisher name')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Merging...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=merge_into_master&source_id=' + sourceId + '&master_id=' + masterId + '&source_name=' + encodeURIComponent(sourceName) + '&master_name=' + encodeURIComponent(masterName)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Publisher merged successfully!\nMerged: "' + sourceName + '" → "' + masterName + '"\nBooks updated: ' + (data.books_updated || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function mergeAllInGroup(groupName, masterId) {
        if (!confirm('Merge ALL variations in "' + groupName + '" group into the master record (ID: ' + masterId + ')?\n\nThis will consolidate all publisher variations into one master record.')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Merging All...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=merge_all_in_group&group_name=' + encodeURIComponent(groupName) + '&master_id=' + masterId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ All publishers in "' + groupName + '" group merged successfully!\nTotal merged: ' + (data.merged_count || 0) + '\nBooks updated: ' + (data.books_updated || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    // Erroneous data cleanup functions
    function cleanErroneousData(field, value) {
        if (!confirm('Delete erroneous data from ' + field + ' field?\nValue: "' + value.substring(0, 100) + (value.length > 100 ? '...' : '') + '"')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Deleting...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clean_erroneous_data&field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Erroneous data cleaned successfully!\nBooks updated: ' + (data.books_updated || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function editErroneousData(field, value) {
        const newValue = prompt('Edit ' + field + ' value:\n\nCurrent value:\n' + value, value);
        if (newValue === null || newValue === value) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Updating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=edit_erroneous_data&field=' + encodeURIComponent(field) + '&old_value=' + encodeURIComponent(value) + '&new_value=' + encodeURIComponent(newValue)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Data updated successfully!\nBooks updated: ' + (data.books_updated || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function cleanAllErroneousSeriesData() {
        if (!confirm('Clean ALL erroneous series data?\n\nThis will delete series values that appear to be author bios, descriptions, or other non-series content.')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Cleaning...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clean_all_erroneous_series'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ All erroneous series data cleaned!\nBooks updated: ' + (data.books_updated || 0) + '\nSeries cleaned: ' + (data.series_cleaned || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    // Reading level standardization functions
    function standardizeReadingLevel(current, suggested) {
        if (!confirm('Standardize reading level "' + current + '" to "' + suggested + '"?')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Updating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=standardize_reading_level&current=' + encodeURIComponent(current) + '&suggested=' + encodeURIComponent(suggested)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Reading level standardized successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function createStandardReadingLevels() {
        if (!confirm('Create standard reading levels lookup table?\nThis will create a new table with the UK education system standards.')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Creating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_standard_reading_levels'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Standard reading levels table created successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function migrateAllReadingLevels() {
        if (!confirm('Migrate ALL reading levels to the standard system?\nThis will update all books with standardized reading levels.')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Migrating...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=migrate_all_reading_levels'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ All reading levels migrated successfully!\n' + (data.message || ''));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function synchronizeAgeRanges() {
        if (!confirm('Synchronize ALL age ranges with reading level age groups?\nThis will update all books to use consistent age range values that match the reading level system.')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Synchronizing...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=synchronize_age_ranges'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Age ranges synchronized successfully!\n' +
                      'Books updated: ' + (data.books_updated || 0) + '\n' +
                      'Standard age groups: ' + (data.standard_groups ? data.standard_groups.length : 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }

    function fixAgeRangeSync() {
        if (!confirm('Fix ALL age range synchronization issues?\nThis will:\n- Convert 12+ to 11-14 years\n- Standardize all reading levels\n- Synchronize age ranges with reading levels\n- Handle whitespace and formatting issues')) {
            return;
        }

        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Fixing...';
        button.disabled = true;

        fetch('book-import-validate/ajax/data-enrichment-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_age_range_sync'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Age range sync issues fixed successfully!\n' +
                      'Age range updates: ' + (data.age_updates || 0) + '\n' +
                      'Reading level updates: ' + (data.reading_updates || 0) + '\n' +
                      'Sync updates: ' + (data.sync_updates || 0) + '\n' +
                      'Total changes: ' + (data.total_changes || 0));
                location.reload();
            } else {
                alert('❌ Error: ' + (data.message || 'Unknown error'));
                button.textContent = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('❌ Network error: ' + error.message);
            button.textContent = originalText;
            button.disabled = false;
        });
    }
    </script>

<?php require_once '../includes/footer.php'; ?>
