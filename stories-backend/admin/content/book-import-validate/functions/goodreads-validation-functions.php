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

        // Return null with detailed status on Python script failure
        $detailedStatus['status'] = 'error';
        $detailedStatus['message'] = 'Failed to extract data from Goodreads';
        $detailedStatus['method'] = 'python_script';
        
        // Create empty book data with status
        $bookData = [
            '_status' => $detailedStatus
        ];
        
        return $bookData;
    } catch (Exception $e) {
        error_log("Error fetching Goodreads data: " . $e->getMessage());
        return [
            '_status' => [
                'status' => 'error',
                'message' => 'Exception while fetching Goodreads data: ' . $e->getMessage(),
                'method' => 'python_script'
            ]
        ];
    }
}
