<?php
/**
 * Comprehensive Diagnostic Dashboard
 *
 * This dashboard provides easy access to all diagnostic and testing tools
 * for the Stories from the Web platform.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for the application
$baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$baseUrl .= "://" . $_SERVER['HTTP_HOST'];
$baseUrl .= dirname($_SERVER['PHP_SELF']);

// Function to check if a file exists
function fileExists($path) {
    return file_exists($path);
}

// Define diagnostic tools
$diagnosticTools = [
    'AI Tests' => [
        [
            'name' => 'OpenAI API Test',
            'description' => 'Test the connection to the OpenAI API and diagnose any issues with AI image generation',
            'path' => 'diagnostics/ai/openai_api_test.php',
            'icon' => 'fa-robot'
        ],
        [
            'name' => 'AI Image Debug',
            'description' => 'Debug issues with AI image generation by testing the API endpoint, CORS settings, and providing detailed error information',
            'path' => 'diagnostics/ai/ai_image_debug.php',
            'icon' => 'fa-image'
        ],
        [
            'name' => 'AI Debug Endpoint',
            'description' => 'Direct access to the AI debug API endpoint for diagnostic information',
            'path' => 'api/v1/ai/debug.php',
            'icon' => 'fa-code'
        ]
    ],
    'API Tests' => [
        [
            'name' => 'API Test Suite',
            'description' => 'Comprehensive API testing tool that checks endpoint availability, response format consistency, and validates data structures',
            'path' => 'diagnostics/api/api_test_suite.php',
            'icon' => 'fa-exchange-alt'
        ],
        [
            'name' => 'Test API Endpoints',
            'description' => 'Test API endpoints and functionality',
            'path' => 'diagnostics/api/test_api_endpoints.php',
            'icon' => 'fa-plug'
        ],
        [
            'name' => 'Verify API',
            'description' => 'Verify API connectivity and functionality',
            'path' => 'diagnostics/api/verify_api.php',
            'icon' => 'fa-check-circle'
        ]
    ],
    'Authentication Tests' => [
        [
            'name' => 'Authentication Diagnostic',
            'description' => 'Authentication diagnostic tool that tests login, token handling, and API authentication',
            'path' => 'auth_diagnostic.php',
            'icon' => 'fa-user-shield'
        ],
        [
            'name' => 'Check Auth Status',
            'description' => 'Check current authentication status',
            'path' => 'diagnostics/auth/check_auth_status.php',
            'icon' => 'fa-user-check'
        ],
        [
            'name' => 'Clear Session',
            'description' => 'Clear all session data and cookies to fix login issues',
            'path' => 'diagnostics/auth/clear_session.php',
            'icon' => 'fa-broom'
        ],
        [
            'name' => 'Emergency Login',
            'description' => 'Emergency login tool to bypass normal authentication in case of issues',
            'path' => 'diagnostics/auth/emergency_login.php',
            'icon' => 'fa-key'
        ]
    ],
    'Admin Tests' => [
        [
            'name' => 'Admin Diagnostic',
            'description' => 'Comprehensive admin interface diagnostic tool that tests authentication, form submission, API integration, and database connectivity',
            'path' => 'admin/diagnostic.php',
            'icon' => 'fa-tools'
        ],
        [
            'name' => 'Admin Test Tools',
            'description' => 'Admin interface test tools',
            'path' => 'admin/test_tools.php',
            'icon' => 'fa-wrench'
        ]
    ],
    'Database Tests' => [
        [
            'name' => 'Test Database',
            'description' => 'Test database connection and functionality',
            'path' => 'admin/test_database.php',
            'icon' => 'fa-database'
        ],
        [
            'name' => 'Check Database',
            'description' => 'Check database schema and data',
            'path' => 'diagnostics/database/check_database.php',
            'icon' => 'fa-table'
        ],
        [
            'name' => 'Verify DB Connection',
            'description' => 'Verify database connection',
            'path' => 'public/verify_db_connection.php',
            'icon' => 'fa-plug'
        ]
    ],
    'System Tests' => [
        [
            'name' => 'Test Connection',
            'description' => 'Test server connection and network functionality',
            'path' => 'admin/test_connection.php',
            'icon' => 'fa-network-wired'
        ],
        [
            'name' => 'Verify All Connections',
            'description' => 'Verify all connections (database, API, etc.)',
            'path' => 'public/verify_all_connections.php',
            'icon' => 'fa-sitemap'
        ],
        [
            'name' => 'Verify Structure',
            'description' => 'Verify file and directory structure',
            'path' => 'public/verify_structure.php',
            'icon' => 'fa-folder-open'
        ]
    ],
    'Media Tests' => [
        [
            'name' => 'Media Diagnostic',
            'description' => 'Diagnose and fix issues with media uploads and image optimization',
            'path' => 'diagnostics/media/media_diagnostic.php',
            'icon' => 'fa-images'
        ],
        [
            'name' => 'Fix Media Issues',
            'description' => 'Fix common issues with media uploads and image optimization',
            'path' => 'diagnostics/media/fix_media.php',
            'icon' => 'fa-wrench'
        ],
        [
            'name' => 'Admin Media Diagnostic',
            'description' => 'Admin-specific media diagnostic tool',
            'path' => 'admin/diagnostic.php',
            'icon' => 'fa-tools'
        ]
    ],
    'Book Import & Scraping Tests' => [
        [
            'name' => 'Amazon Scraper Test',
            'description' => 'Test Amazon book scraping functionality and data extraction',
            'path' => 'public/test-amazon-scraper.php',
            'icon' => 'fa-amazon'
        ],
        [
            'name' => 'Goodreads Scraper Test',
            'description' => 'Test Goodreads book scraping and review fetching',
            'path' => 'public/test-goodreads-scraper.php',
            'icon' => 'fa-book-reader'
        ],
        [
            'name' => 'Book Enrichment Test',
            'description' => 'Comprehensive test for book data enrichment from multiple sources',
            'path' => 'public/test-enrichment-comprehensive.php',
            'icon' => 'fa-magic'
        ],
        [
            'name' => 'Book Validation',
            'description' => 'Validate and enrich book data with ISBN lookup and scraping',
            'path' => 'admin/content/book-validation.php',
            'icon' => 'fa-check-double'
        ],
        [
            'name' => 'Publisher Relationship Test',
            'description' => 'Test publisher data relationships and validation',
            'path' => 'admin/content/test-publisher-relationship.php',
            'icon' => 'fa-building'
        ],
        [
            'name' => 'OpenLibrary Test',
            'description' => 'Test OpenLibrary API integration for book data',
            'path' => 'admin/content/test-openlibrary.php',
            'icon' => 'fa-book-open'
        ]
    ],
    'VPS & External Services' => [
        [
            'name' => 'VPS Status Check',
            'description' => 'Check VPS scraper service status and connectivity',
            'path' => 'public/check-vps-status.php',
            'icon' => 'fa-server'
        ],
        [
            'name' => 'VPS API Key Check',
            'description' => 'Verify VPS API key configuration',
            'path' => 'public/check-vps-api-key.php',
            'icon' => 'fa-key'
        ],
        [
            'name' => 'VPS Logs',
            'description' => 'View VPS scraper logs and debug information',
            'path' => 'public/check-vps-logs.php',
            'icon' => 'fa-file-alt'
        ],
        [
            'name' => 'Direct VPS Connection Test',
            'description' => 'Test direct connection to VPS scraper service',
            'path' => 'public/test-direct-vps-connection.php',
            'icon' => 'fa-plug'
        ]
    ],
    'Data Management' => [
        [
            'name' => 'Cleanup Duplicates',
            'description' => 'Find and remove duplicate entries in the database',
            'path' => 'admin/content/cleanup-duplicates.php',
            'icon' => 'fa-broom'
        ],
        [
            'name' => 'Comprehensive Cleanup',
            'description' => 'Comprehensive data cleanup and optimization tool',
            'path' => 'admin/content/comprehensive-cleanup.php',
            'icon' => 'fa-tools'
        ],
        [
            'name' => 'Check DB Structure',
            'description' => 'Verify database structure and schema integrity',
            'path' => 'admin/content/check-db-structure.php',
            'icon' => 'fa-database'
        ],
        [
            'name' => 'Review Settings',
            'description' => 'Configure review system settings and sources',
            'path' => 'admin/content/review-settings.php',
            'icon' => 'fa-cog'
        ]
    ],
    'Documentation' => [
        [
            'name' => 'Script Index',
            'description' => 'Comprehensive index of all scripts with their purposes and locations',
            'path' => 'SCRIPT_INDEX.md',
            'icon' => 'fa-list'
        ],
        [
            'name' => 'System Architecture',
            'description' => 'Visual representation of the system architecture with diagrams',
            'path' => 'docs/system-architecture-with-improvements.md',
            'icon' => 'fa-project-diagram'
        ],
        [
            'name' => 'Documentation Index',
            'description' => 'Central index for all documentation',
            'path' => 'docs/documentation-index.md',
            'icon' => 'fa-book'
        ]
    ]
];

// Include auth check
require_once 'admin/includes/auth-check.php';

// Include database connection
require_once 'admin/includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Diagnostic Dashboard';
$currentPage = 'diagnostics';
$pageDescription = '';

// Include header
require_once 'admin/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h2><i class="fas fa-chart-line"></i></h2>
                </div>
                <div class="card-body">
                    <?php foreach ($diagnosticTools as $category => $tools): ?>
                        <h2><?php echo $category; ?></h2>
                        <div class="row">
                            <?php foreach ($tools as $tool): ?>
                                <?php
                                $isAvailable = fileExists(__DIR__ . '/' . $tool['path']);
                                $categoryClass = strtolower(str_replace(' ', '-', $category));
                                ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card <?php echo $categoryClass; ?> <?php echo $isAvailable ? '' : 'unavailable'; ?>">
                                        <div class="card-body text-center">
                                            <div class="tool-icon">
                                                <i class="fas <?php echo $tool['icon']; ?>"></i>
                                            </div>
                                            <h5 class="card-title"><?php echo $tool['name']; ?></h5>
                                            <p class="card-text"><?php echo $tool['description']; ?></p>
                                            <?php if ($isAvailable): ?>
                                                <a href="<?php echo $tool['path']; ?>" class="btn btn-primary">
                                                    Launch Tool
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>Not Available</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="mt-5">
                        <h2>System Information</h2>
                        <div class="card">
                            <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>PHP Information</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    PHP Version
                                    <span class="badge bg-primary rounded-pill"><?php echo phpversion(); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Server Software
                                    <span class="badge bg-primary rounded-pill"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Document Root
                                    <span class="badge bg-primary rounded-pill"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Extensions</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    PDO
                                    <span class="badge <?php echo extension_loaded('pdo') ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                        <?php echo extension_loaded('pdo') ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    cURL
                                    <span class="badge <?php echo extension_loaded('curl') ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                        <?php echo extension_loaded('curl') ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    JSON
                                    <span class="badge <?php echo extension_loaded('json') ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                        <?php echo extension_loaded('json') ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
    </div>
</div>

<style>
    .tool-icon {
        font-size: 2rem;
        margin-bottom: 15px;
    }
    .ai-tests .tool-icon { color: #6610f2; }
    .api-tests .tool-icon { color: #28a745; }
    .authentication-tests .tool-icon { color: #007bff; }
    .admin-tests .tool-icon { color: #6f42c1; }
    .database-tests .tool-icon { color: #fd7e14; }
    .system-tests .tool-icon { color: #dc3545; }
    .media-tests .tool-icon { color: #e83e8c; }
    .book-import-scraping-tests .tool-icon { color: #ff6b6b; }
    .vps-external-services .tool-icon { color: #4ecdc4; }
    .data-management .tool-icon { color: #45b7d1; }
    .documentation .tool-icon { color: #17a2b8; }
    .unavailable {
        opacity: 0.5;
    }
    .unavailable .card-body {
        position: relative;
    }
    .unavailable .card-body::after {
        content: "Not Available";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        background-color: rgba(220, 53, 69, 0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
    }
    .card {
        transition: transform 0.3s;
    }
    .card:hover {
        transform: translateY(-5px);
    }
</style>

<?php
// Include footer
require_once 'admin/includes/footer.php';
?>