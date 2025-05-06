<?php
/**
 * Debug Inline Editing
 * 
 * This page is used to debug the inline editing functionality.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set page variables for header
$pageTitle = 'Debug Inline Editing';
$currentPage = 'debug';
$pageDescription = 'This page is used to debug the inline editing functionality.';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
';

// Include header
require_once '../includes/header.php';
?>

<div class="premium-card">
    <div class="premium-card-header">
        <h2 class="premium-card-title">Debug Information</h2>
    </div>
    <div class="premium-card-body">
        <h3>Test Editable Fields</h3>
        <p>Click on any of the fields below to edit them:</p>
        
        <table class="premium-table" id="debug-table" data-item-type="test">
            <thead>
                <tr>
                    <th data-field="name">Name</th>
                    <th data-field="email">Email</th>
                    <th data-field="description">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr data-id="1">
                    <td>
                        <div class="premium-editable" data-field-name="name" data-field-type="text">
                            John Doe
                        </div>
                    </td>
                    <td>
                        <div class="premium-editable" data-field-name="email" data-field-type="email">
                            john@example.com
                        </div>
                    </td>
                    <td>
                        <div class="premium-editable" data-field-name="description" data-field-type="textarea">
                            This is a test description.
                        </div>
                    </td>
                </tr>
                <tr data-id="2">
                    <td>
                        <div class="premium-editable" data-field-name="name" data-field-type="text">
                            Jane Smith
                        </div>
                    </td>
                    <td>
                        <div class="premium-editable" data-field-name="email" data-field-type="email">
                            jane@example.com
                        </div>
                    </td>
                    <td>
                        <div class="premium-editable" data-field-name="description" data-field-type="textarea">
                            Another test description.
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h3>Console Output</h3>
        <p>Check the browser console for debug information.</p>
        
        <h3>Network Requests</h3>
        <p>Check the Network tab in the browser developer tools to see the AJAX requests.</p>
    </div>
</div>

<script>
    // Additional debugging
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Debug page loaded');
        
        // Log all tables
        const allTables = document.querySelectorAll('table');
        console.log(`Found ${allTables.length} tables on the page`);
        
        allTables.forEach((table, index) => {
            console.log(`Table ${index + 1}:`, table);
            console.log(`  ID: ${table.id}`);
            console.log(`  data-item-type: ${table.getAttribute('data-item-type')}`);
        });
        
        // Log all editable fields
        const allEditableFields = document.querySelectorAll('.premium-editable');
        console.log(`Found ${allEditableFields.length} editable fields on the page`);
        
        allEditableFields.forEach((field, index) => {
            console.log(`Editable field ${index + 1}:`, field);
            console.log(`  data-field-name: ${field.getAttribute('data-field-name')}`);
            console.log(`  data-field-type: ${field.getAttribute('data-field-type')}`);
            console.log(`  Parent TR data-id: ${field.closest('tr')?.getAttribute('data-id')}`);
            console.log(`  Parent table data-item-type: ${field.closest('table')?.getAttribute('data-item-type')}`);
        });
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
