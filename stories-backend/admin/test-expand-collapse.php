<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Expand/Collapse - No JavaScript</title>
    <link rel="stylesheet" href="assets/css/modern-admin.css">
    <style>
        /* Additional styles for testing */
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .test-title {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .test-description {
            margin-bottom: 30px;
            padding: 15px;
            background-color: var(--gray-100);
            border-radius: var(--radius-md);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="test-title">CSS-Only Expand/Collapse Test</h1>
        
        <div class="test-description">
            <p>This is a test of the CSS-only expand/collapse functionality. No JavaScript is used.</p>
            <p>Click on the section headers below to expand or collapse the content.</p>
        </div>
        
        <!-- Test Section 1 -->
        <input type="checkbox" id="toggle-section1" class="collapsible-toggle" checked>
        <div class="collapsible">
            <label for="toggle-section1" class="collapsible-header">
                Section 1 - Initially Expanded
            </label>
            <div class="collapsible-content">
                <div class="p-3">
                    <p>This is the content of section 1. It should be visible by default because the checkbox is checked.</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl.</p>
                </div>
            </div>
        </div>
        
        <!-- Test Section 2 -->
        <input type="checkbox" id="toggle-section2" class="collapsible-toggle">
        <div class="collapsible">
            <label for="toggle-section2" class="collapsible-header">
                Section 2 - Initially Collapsed
            </label>
            <div class="collapsible-content">
                <div class="p-3">
                    <p>This is the content of section 2. It should be hidden by default because the checkbox is not checked.</p>
                    <p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                </div>
            </div>
        </div>
        
        <!-- Test Section 3 -->
        <input type="checkbox" id="toggle-section3" class="collapsible-toggle">
        <div class="collapsible">
            <label for="toggle-section3" class="collapsible-header">
                Section 3 - With Table Content
            </label>
            <div class="collapsible-content">
                <div class="p-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>John Doe</td>
                                <td>john@example.com</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-info btn-sm">
                                            <span class="icon-view"></span> View
                                        </button>
                                        <button class="btn btn-primary btn-sm">
                                            <span class="icon-edit"></span> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm">
                                            <span class="icon-delete"></span> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Jane Smith</td>
                                <td>jane@example.com</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-info btn-sm">
                                            <span class="icon-view"></span> View
                                        </button>
                                        <button class="btn btn-primary btn-sm">
                                            <span class="icon-edit"></span> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm">
                                            <span class="icon-delete"></span> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center">
            <p>If the expand/collapse functionality works correctly, you should be able to click on the section headers to toggle the visibility of the content.</p>
            <p>This is all done with CSS, no JavaScript required!</p>
        </div>
    </div>
</body>
</html>