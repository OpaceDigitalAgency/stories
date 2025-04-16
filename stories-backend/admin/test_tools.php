<?php
/**
 * Test Tools Page
 * 
 * This page provides links to various test scripts for debugging and troubleshooting.
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/AdminPage.php';

/**
 * Test Tools Page Class
 */
class TestToolsPage extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set page title
        $this->pageTitle = 'Test Tools';
        
        // Set active menu
        $this->activeMenu = 'test-tools';
    }
    
    /**
     * Get content template name
     * 
     * @return string Template name
     */
    protected function getContentTemplate() {
        // We'll render the content directly here instead of using a template
        return null;
    }
    
    /**
     * Render the page
     */
    protected function render() {
        // Set page title
        $this->data['pageTitle'] = $this->pageTitle;
        
        // Set active menu
        $this->data['activeMenu'] = $this->activeMenu;
        
        // Set errors
        $this->data['errors'] = $this->errors;
        
        // Set success messages
        $this->data['success'] = $this->success;
        
        // Extract data to variables
        extract($this->data);
        
        // Include header
        include __DIR__ . '/../admin/views/header.php';
        
        // Render content directly
        ?>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Test Tools</h5>
                            <p class="card-subtitle mb-2 text-muted">Use these tools to test and troubleshoot the system</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="card-title mb-0">Database Tests</h5>
                                        </div>
                                        <div class="card-body">
                                            <p>Test database connection and functionality.</p>
                                            <a href="test_database.php" class="btn btn-primary" target="_blank">
                                                <i class="fas fa-database"></i> Test Database
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="card-title mb-0">API Tests</h5>
                                        </div>
                                        <div class="card-body">
                                            <p>Test API connection and functionality.</p>
                                            <a href="test_api.php" class="btn btn-success" target="_blank">
                                                <i class="fas fa-plug"></i> Test API
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="card-title mb-0">Connection Tests</h5>
                                        </div>
                                        <div class="card-body">
                                            <p>Test server connection and network functionality.</p>
                                            <a href="test_connection.php" class="btn btn-info" target="_blank">
                                                <i class="fas fa-network-wired"></i> Test Connection
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="card-title mb-0">Endpoint Tests</h5>
                                        </div>
                                        <div class="card-body">
                                            <p>Test API endpoints and functionality.</p>
                                            <a href="test_endpoints.php" class="btn btn-warning" target="_blank">
                                                <i class="fas fa-exchange-alt"></i> Test Endpoints
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        // Include footer
        include __DIR__ . '/../admin/views/footer.php';
    }
}

// Create and process the page
$page = new TestToolsPage();
$page->process();
?>