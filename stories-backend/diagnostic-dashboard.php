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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Dashboard - Stories from the Web</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .card {
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            font-weight: bold;
        }
        .tool-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        .api-tests .tool-icon { color: #28a745; }
        .auth-tests .tool-icon { color: #007bff; }
        .admin-tests .tool-icon { color: #6f42c1; }
        .db-tests .tool-icon { color: #fd7e14; }
        .system-tests .tool-icon { color: #dc3545; }
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnostic Dashboard - Stories from the Web</h1>
        <p class="lead">This dashboard provides easy access to all diagnostic and testing tools for the Stories from the Web platform.</p>

        <?php foreach ($diagnosticTools as $category => $tools): ?>
            <h2><?php echo $category; ?></h2>
            <div class="row">
                <?php foreach ($tools as $tool): ?>
                    <?php
                    $isAvailable = fileExists(__DIR__ . '/' . $tool['path']);
                    $categoryClass = strtolower(str_replace(' ', '-', $category));
                    ?>
                    <div class="col-md-4">
                        <div class="card <?php echo $categoryClass; ?> <?php echo $isAvailable ? '' : 'unavailable'; ?>">
                            <div class="card-body text-center">
                                <div class="tool-icon">
                                    <i class="fas <?php echo $tool['icon']; ?>"></i>
                                </div>
                                <h5 class="card-title"><?php echo $tool['name']; ?></h5>
                                <p class="card-text"><?php echo $tool['description']; ?></p>
                                <?php if ($isAvailable): ?>
                                    <a href="<?php echo $tool['path']; ?>" class="btn btn-primary" target="_blank">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>