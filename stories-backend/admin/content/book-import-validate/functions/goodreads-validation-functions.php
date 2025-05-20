<?php
/**
 * Goodreads Validation Functions
 *
 * This file contains functions for fetching and validating book data from Goodreads.
 */

/**
 * Fetch data from Goodreads
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataNew($isbn, $title, $author) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize detailed status tracking
        $detailedStatus = [
            'status' => 'initializing',
            'message' => 'Starting Goodreads data fetch',
            'method' => 'api',
            'processing_time' => 0,
            'steps' => []
        ];

        // Add initialization step with detailed parameters
        $detailedStatus['steps'][] = [
            'name' => 'initialization',
            'status' => 'success',
            'message' => "Parameters: ISBN: '$isbn', Title: '$title', Author: '$author'"
        ];

        // Try to build a direct book URL if possible
        $searchUrl = "";
        if (!empty($title) && !empty($author)) {
            // Format the URL with title and author for better results
            $titleSlug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($title));
            $titleSlug = trim($titleSlug, '_');
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($title . " " . $author);

            $detailedStatus['steps'][] = [
                'name' => 'url_generation',
                'status' => 'success',
                'message' => "Generated URL from title/author: $searchUrl"
            ];
        } else {
            // Fallback to search URL with ISBN
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($isbn);

            $detailedStatus['steps'][] = [
                'name' => 'url_generation',
                'status' => 'info',
                'message' => "Generated URL from ISBN: $searchUrl"
            ];
        }

        // First try using the Python script (most reliable method)
        $pythonScript = __DIR__ . '/goodreads/goodreads_book_info.py';
        $setupScript = __DIR__ . '/goodreads/setup.sh';
        $requirementsFile = __DIR__ . '/goodreads/requirements.txt';

        // Run setup script if it exists
        if (file_exists($setupScript)) {
            $detailedStatus['steps'][] = [
                'name' => 'setup_check',
                'status' => 'in_progress',
                'message' => "Running setup script to ensure dependencies"
            ];

            $output = [];
            $returnCode = 0;
            exec('bash ' . escapeshellarg($setupScript) . ' 2>&1', $output, $returnCode);

            if ($returnCode === 0) {
                $detailedStatus['steps'][] = [
                    'name' => 'setup_check',
                    'status' => 'success',
                    'message' => "Setup completed successfully"
                ];
            } else {
                $detailedStatus['steps'][] = [
                    'name' => 'setup_check',
                    'status' => 'error',
                    'message' => "Setup failed: " . implode(" | ", $output)
                ];
            }
        }

        // Check if Python script exists
        if (file_exists($pythonScript)) {
            // Make script executable
            chmod($pythonScript, 0755);

            $detailedStatus['steps'][] = [
                'name' => 'python_script_check',
                'status' => 'success',
                'message' => "Python script found at: $pythonScript"
            ];

            // Check Python dependencies
            $detailedStatus['steps'][] = [
                'name' => 'dependency_check',
                'status' => 'in_progress',
                'message' => "Checking Python dependencies"
            ];

            $output = [];
            $returnCode = 0;
            exec('pip3 list 2>&1', $output, $returnCode);
            $installedPackages = implode("\n", $output);

            if (strpos($installedPackages, 'beautifulsoup4') === false) {
                $detailedStatus['steps'][] = [
                    'name' => 'dependency_install',
                    'status' => 'in_progress',
                    'message' => "Installing required Python packages"
                ];

                exec('pip3 install -r ' . escapeshellarg($requirementsFile) . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    $detailedStatus['steps'][] = [
                        'name' => 'dependency_install',
                        'status' => 'success',
                        'message' => "Successfully installed Python dependencies"
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'dependency_install',
                        'status' => 'error',
                        'message' => "Failed to install Python dependencies: " . implode(" | ", $output)
                    ];
                }
            } else {
                $detailedStatus['steps'][] = [
                    'name' => 'dependency_check',
                    'status' => 'success',
                    'message' => "Required Python packages already installed"
                ];
            }

            // Set method to Python script
            $detailedStatus['method'] = 'python_script';

            // Execute Python script with a longer timeout
            $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($searchUrl) . " 2>&1";
            $output = [];
            $returnCode = 0;

            $detailedStatus['steps'][] = [
                'name' => 'python_execution',
                'status' => 'in_progress',
                'message' => "Executing command: $command"
            ];

            // Execute with a longer timeout
            exec($command, $output, $returnCode);

            // Update execution status
            if ($returnCode === 0) {
                $detailedStatus['steps'][] = [
                    'name' => 'python_execution',
                    'status' => 'success',
                    'message' => "Python script executed successfully"
                ];
            } else {
                $detailedStatus['steps'][] = [
                    'name' => 'python_execution',
                    'status' => 'error',
                    'message' => "Python script execution failed with code: $returnCode"
                ];
            }

            // Initialize status tracking
            $statusData = null;
            $jsonData = null;
            $jsonFile = null;
            $processingTime = microtime(true) - $startTime;

            // First look for structured status output
            $detailedStatus['steps'][] = [
                'name' => 'parse_output',
                'status' => 'in_progress',
                'message' => "Parsing Python script output (" . count($output) . " lines)"
            ];

            foreach ($output as $line) {
                if (strpos($line, 'STATUS_JSON: ') === 0) {
                    $statusJson = substr($line, strlen('STATUS_JSON: '));
                    $statusData = json_decode($statusJson, true);
                    if ($statusData) {
                        // Add processing time to status data
                        $statusData['processing_time'] = round($processingTime, 2);

                        $detailedStatus['steps'][] = [
                            'name' => 'parse_output',
                            'status' => 'success',
                            'message' => "Found structured status output: " . $statusData['status']
                        ];

                        // Merge Python script steps into our detailed status
                        if (isset($statusData['steps']) && is_array($statusData['steps'])) {
                            foreach ($statusData['steps'] as $step) {
                                $detailedStatus['steps'][] = $step;
                            }
                        }

                        break;
                    }
                }
            }

            // Check if Python script executed successfully
            if ($returnCode === 0) {
                // If we have status data with book data, use it directly
                if ($statusData && isset($statusData['data']) && !empty($statusData['data'])) {
                    $jsonData = $statusData['data'];
                } else {
                    // Look for JSON file reference in the output
                    foreach ($output as $line) {
                        if (strpos($line, 'Saved book information to ') !== false) {
                            $jsonFile = trim(str_replace('Saved book information to ', '', $line));
                            if (file_exists($jsonFile)) {
                                $jsonContent = file_get_contents($jsonFile);
                                $jsonData = json_decode($jsonContent, true);
                                break;
                            }
                        }
                    }

                    // If no file found, look for JSON in the output
                    if (!$jsonData) {
                        foreach ($output as $line) {
                            if (strpos($line, '{') === 0) {
                                $jsonData = json_decode($line, true);
                                if ($jsonData) {
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($jsonData) {
                    // Log the full JSON data for debugging
                    error_log("Goodreads JSON data: " . json_encode($jsonData));

                    // Extract book details from JSON data
                    // Make sure to clean any HTML tags from all fields
                    $bookData = [
                        'title' => strip_tags($jsonData['title'] ?? ''),
                        'author' => strip_tags($jsonData['author'] ?? ''),
                        'publisher' => strip_tags($jsonData['publisher'] ?? ''),
                        'publication_date' => strip_tags($jsonData['published_date'] ?? ($jsonData['publication_date'] ?? '')),
                        'page_count' => strip_tags($jsonData['pages'] ?? ($jsonData['page_count'] ?? '')),
                        'isbn' => strip_tags($jsonData['isbn'] ?? ''),
                        'isbn13' => strip_tags($jsonData['isbn13'] ?? ''),
                        'language' => strip_tags($jsonData['language'] ?? ''),
                        'format' => strip_tags($jsonData['format'] ?? ''),
                        'series' => strip_tags($jsonData['series'] ?? ''),
                        'awards' => strip_tags($jsonData['awards'] ?? ''),
                        'characters' => is_array($jsonData['characters'] ?? null) ? array_map('strip_tags', $jsonData['characters']) : [],
                        'settings' => is_array($jsonData['settings'] ?? null) ? array_map('strip_tags', $jsonData['settings']) : [],
                        'preview_link' => $jsonData['preview_link'] ?? '',
                        'cover_url' => $jsonData['cover_image'] ?? '',
                        'rating' => strip_tags($jsonData['rating'] ?? ''),
                        'rating_count' => strip_tags($jsonData['rating_count'] ?? ''),
                        'review_count' => strip_tags($jsonData['review_count'] ?? ''),
                        'maturity_rating' => strip_tags($jsonData['maturity_rating'] ?? '')
                    ];

                    // Log success for debugging
                    $endTime = microtime(true);
                    $totalTime = round($endTime - $startTime, 2);

                    // Update detailed status with final information
                    $detailedStatus['status'] = $statusData['status'] ?? 'success';
                    $detailedStatus['message'] = $statusData['message'] ?? 'Successfully extracted data from Goodreads via Python script';
                    $detailedStatus['processing_time'] = $totalTime;
                    $detailedStatus['method'] = 'python_script';

                    // Add detailed status to book data
                    $bookData['_status'] = $detailedStatus;

                    return $bookData;
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'parse_output',
                        'status' => 'error',
                        'message' => "Python script executed but no valid JSON data found"
                    ];

                    // Add sample of the output for debugging
                    $outputSample = array_slice($output, 0, min(5, count($output)));
                    $detailedStatus['steps'][] = [
                        'name' => 'output_sample',
                        'status' => 'info',
                        'message' => "First few lines of output: " . implode(" | ", $outputSample)
                    ];
                }
            } else {
                $detailedStatus['steps'][] = [
                    'name' => 'python_error',
                    'status' => 'error',
                    'message' => "Failed to execute Python script with return code: $returnCode"
                ];

                // Add sample of the error output for debugging
                if (!empty($output)) {
                    $errorSample = array_slice($output, 0, min(5, count($output)));
                    $detailedStatus['steps'][] = [
                        'name' => 'error_sample',
                        'status' => 'info',
                        'message' => "Error output: " . implode(" | ", $errorSample)
                    ];
                }
            }
        } else {
            $detailedStatus['steps'][] = [
                'name' => 'python_script_check',
                'status' => 'error',
                'message' => "Python script not found at: $pythonScript"
            ];
        }

        // Fallback to direct curl request if Python script didn't work
        $detailedStatus['steps'][] = [
            'name' => 'fallback',
            'status' => 'in_progress',
            'message' => "Falling back to direct curl request method"
        ];

        $detailedStatus['method'] = 'curl_fallback';

        // Pass the detailed status to the curl fallback method
        return fetchGoodreadsDataWithCurlNew($isbn, $title, $author, $searchUrl, $detailedStatus);
    } catch (Exception $e) {
        error_log("Error fetching Goodreads data: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch Goodreads data using direct curl request
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @param string $searchUrl The search URL to use
 * @param array $detailedStatus Optional detailed status from previous attempts
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataWithCurlNew($isbn, $title, $author, $searchUrl, $detailedStatus = null) {
    try {
        $startTime = microtime(true);

        // Initialize detailed status if not provided
        if (!$detailedStatus) {
            $detailedStatus = [
                'status' => 'initializing',
                'message' => 'Starting Goodreads data fetch via curl',
                'method' => 'curl_direct',
                'processing_time' => 0,
                'steps' => [
                    [
                        'name' => 'initialization',
                        'status' => 'success',
                        'message' => "Parameters: ISBN: '$isbn', Title: '$title', Author: '$author'"
                    ],
                    [
                        'name' => 'url_generation',
                        'status' => 'info',
                        'message' => "Using URL: $searchUrl"
                    ]
                ]
            ];
        }

        $detailedStatus['steps'][] = [
            'name' => 'curl_request',
            'status' => 'in_progress',
            'message' => "Sending curl request to: $searchUrl"
        ];

        $ch = curl_init($searchUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Longer timeout for fallback
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $detailedStatus['steps'][] = [
                'name' => 'curl_request',
                'status' => 'error',
                'message' => "cURL error: $error"
            ];
        } else {
            $detailedStatus['steps'][] = [
                'name' => 'curl_request',
                'status' => 'success',
                'message' => "Received response with HTTP code: $httpCode"
            ];
        }

        if ($response && $httpCode == 200) {
            $detailedStatus['steps'][] = [
                'name' => 'response_check',
                'status' => 'in_progress',
                'message' => "Checking response for book data"
            ];

            // Check if we found a book
            $bookFound = strpos($response, 'No results') === false &&
                (strpos($response, 'class="bookTitle"') !== false ||
                 strpos($response, 'class="bookCover"') !== false ||
                 strpos($response, 'data-testid="bookTitle"') !== false);

            if ($bookFound) {
                $detailedStatus['steps'][] = [
                    'name' => 'response_check',
                    'status' => 'success',
                    'message' => "Found book data in response"
                ];

                $detailedStatus['steps'][] = [
                    'name' => 'extract_title',
                    'status' => 'in_progress',
                    'message' => "Extracting title"
                ];

                // Extract book details using current Goodreads selectors
                preg_match('/<h1[^>]*class="Text__title1"[^>]*>(.*?)<\/h1>/s', $response, $titleMatches);

                if (!empty($titleMatches)) {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_title',
                        'status' => 'success',
                        'message' => "Found title: " . strip_tags($titleMatches[1])
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_title',
                        'status' => 'error',
                        'message' => "Failed to extract title"
                    ];
                }

                $detailedStatus['steps'][] = [
                    'name' => 'extract_author',
                    'status' => 'in_progress',
                    'message' => "Extracting author"
                ];

                preg_match('/<span[^>]*class="ContributorLink__name"[^>]*>(.*?)<\/span>/s', $response, $authorMatches);

                if (!empty($authorMatches)) {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_author',
                        'status' => 'success',
                        'message' => "Found author: " . strip_tags($authorMatches[1])
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_author',
                        'status' => 'error',
                        'message' => "Failed to extract author"
                    ];
                }

                $detailedStatus['steps'][] = [
                    'name' => 'extract_isbn',
                    'status' => 'in_progress',
                    'message' => "Extracting ISBN"
                ];

                preg_match('/ISBN.*?([0-9X]{10})/i', $response, $isbnMatches);
                preg_match('/ISBN.*?([0-9]{13})/i', $response, $isbn13Matches);

                if (!empty($isbnMatches) || !empty($isbn13Matches)) {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_isbn',
                        'status' => 'success',
                        'message' => "Found ISBNs: " .
                            (!empty($isbnMatches) ? "ISBN-10: " . $isbnMatches[1] : "") .
                            (!empty($isbnMatches) && !empty($isbn13Matches) ? ", " : "") .
                            (!empty($isbn13Matches) ? "ISBN-13: " . $isbn13Matches[1] : "")
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_isbn',
                        'status' => 'warning',
                        'message' => "No ISBNs found in response"
                    ];
                }

                $detailedStatus['steps'][] = [
                    'name' => 'extract_publisher',
                    'status' => 'in_progress',
                    'message' => "Extracting publisher and publication date"
                ];

                preg_match('/Published.*?(\d{4}).*?by\s+(.*?)(<|\n)/is', $response, $publisherMatches);
                preg_match('/(\d+)\s+pages/i', $response, $pageCountMatches);

                if (!empty($publisherMatches)) {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_publisher',
                        'status' => 'success',
                        'message' => "Found publisher: " . strip_tags($publisherMatches[2] ?? '') .
                                    ", Year: " . ($publisherMatches[1] ?? '')
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_publisher',
                        'status' => 'warning',
                        'message' => "No publisher information found"
                    ];
                }

                $detailedStatus['steps'][] = [
                    'name' => 'extract_cover',
                    'status' => 'in_progress',
                    'message' => "Extracting cover image"
                ];

                // Extract cover image using current selector
                preg_match('/<img[^>]*class="ResponsiveImage"[^>]*src="([^"]+)"/s', $response, $coverMatches);

                if (!empty($coverMatches)) {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_cover',
                        'status' => 'success',
                        'message' => "Found cover image URL"
                    ];
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'extract_cover',
                        'status' => 'warning',
                        'message' => "No cover image found"
                    ];
                }

                // Extract description using current selector
                preg_match('/<div[^>]*class="DetailsLayoutRightParagraph__widthConstrained"[^>]*>(.*?)<\/div>/s', $response, $descMatches);

                // Extract rating
                preg_match('/<div[^>]*class="RatingStatistics__rating"[^>]*>(.*?)<\/div>/s', $response, $ratingMatches);

                // Extract genres
                preg_match_all('/<span[^>]*class="BookPageMetadataSection__genreButton"[^>]*>(.*?)<\/span>/s', $response, $genreMatches);

                // Extract JSON-LD data for additional fields
                preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response, $jsonMatches);
                $jsonData = null;
                if (!empty($jsonMatches[1])) {
                    $jsonData = json_decode($jsonMatches[1], true);
                }

                // Extract data from JSON-LD if available
                $pages = '';
                $format = '';
                $language = '';
                $ratingCount = '';
                $reviewCount = '';
                if ($jsonData && isset($jsonData['@type']) && $jsonData['@type'] === 'Book') {
                    $pages = $jsonData['numberOfPages'] ?? '';
                    $format = $jsonData['bookFormat'] ?? '';
                    $language = $jsonData['inLanguage'] ?? '';
                    if (isset($jsonData['aggregateRating'])) {
                        $ratingCount = $jsonData['aggregateRating']['ratingCount'] ?? '';
                        $reviewCount = $jsonData['aggregateRating']['reviewCount'] ?? '';
                    }
                }

                // Process all the extracted data
                if (!empty($titleMatches) || !empty($authorMatches) || !empty($coverMatches) || 
                    !empty($descMatches) || !empty($ratingMatches) || !empty($genreMatches)) {
                    
                    $detailedStatus['steps'][] = [
                        'name' => 'data_extraction',
                        'status' => 'success',
                        'message' => "Successfully extracted book data from HTML"
                    ];

                    // Build book data array
                    $bookData = [
                        'title' => strip_tags($titleMatches[1] ?? ''),
                        'author' => strip_tags($authorMatches[1] ?? ''),
                        'publisher' => strip_tags($publisherMatches[2] ?? ''),
                        'publication_date' => strip_tags($publisherMatches[1] ?? ''),
                        'page_count' => $pages ?: strip_tags($pageCountMatches[1] ?? ''),
                        'isbn' => strip_tags($isbnMatches[1] ?? ''),
                        'isbn13' => strip_tags($isbn13Matches[1] ?? ''),
                        'language' => $language,
                        'format' => $format,
                        'series' => '',
                        'awards' => '',
                        'characters' => [],
                        'settings' => [],
                        'preview_link' => '',
                        'cover_url' => $coverMatches[1] ?? '',
                        'rating' => strip_tags($ratingMatches[1] ?? ''),
                        'rating_count' => $ratingCount,
                        'review_count' => $reviewCount,
                        'maturity_rating' => '',
                        'genres' => !empty($genreMatches[1]) ? array_map('strip_tags', $genreMatches[1]) : []
                    ];

                    // Add status information
                    $endTime = microtime(true);
                    $totalTime = round($endTime - $startTime, 2);

                    $detailedStatus['status'] = 'success';
                    $detailedStatus['message'] = 'Successfully extracted data from Goodreads via curl';
                    $detailedStatus['processing_time'] = $totalTime;
                    $detailedStatus['method'] = 'curl_direct';

                    $bookData['_status'] = $detailedStatus;

                    return $bookData;
                } else {
                    $detailedStatus['steps'][] = [
                        'name' => 'data_extraction',
                        'status' => 'error',
                        'message' => "Failed to extract required book data from HTML"
                    ];
                }
            } else {
                $detailedStatus['steps'][] = [
                    'name' => 'response_check',
                    'status' => 'error',
                    'message' => "No book data found in response"
                ];
            }
        } else {
            $detailedStatus['steps'][] = [
                'name' => 'response_check',
                'status' => 'error',
                'message' => "Invalid response from Goodreads (HTTP $httpCode)"
            ];
        }

        return null;
    } catch (Exception $e) {
        error_log("Error in curl fallback: " . $e->getMessage());
        return null;
    }
}
