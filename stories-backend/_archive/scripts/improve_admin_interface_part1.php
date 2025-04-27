<?php
/**
 * Improve Admin Interface (Part 1)
 * 
 * This script improves the admin interface with:
 * 1. A better navigation system with top menu and side panel
 * 2. Fixed author and tag dropdowns
 * 3. Fixed delete warnings
 * 4. An improved dashboard with recent content
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Improve Admin Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
        .button { display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Improve Admin Interface</h1>
', true);
}

output("Improve Admin Interface");
output("======================");
output("");

// Create a CSS file for the improved admin interface
$adminCssContent = '/* Improved Admin Interface CSS */

/* Layout */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
}

/* Top Navigation */
.top-nav {
    background-color: #4a6cf7;
    color: white;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
    height: 60px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.top-nav-brand {
    display: flex;
    align-items: center;
    padding: 0 20px;
    font-size: 20px;
    font-weight: bold;
    text-decoration: none;
    color: white;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.1);
}

.top-nav-menu {
    display: flex;
    height: 100%;
}

.top-nav-item {
    position: relative;
    height: 100%;
}

.top-nav-link {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 20px;
    color: white;
    text-decoration: none;
    transition: background-color 0.2s;
}

.top-nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.top-nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Main Container */
.main-container {
    display: flex;
    min-height: calc(100vh - 60px);
}

/* Side Navigation */
.side-nav {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    padding: 20px 0;
}

.side-nav-section {
    margin-bottom: 20px;
}

.side-nav-title {
    padding: 10px 20px;
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.side-nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.side-nav-item {
    margin: 0;
}

.side-nav-link {
    display: block;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s;
}

.side-nav-link:hover {
    background-color: #f5f5f5;
}

.side-nav-link.active {
    background-color: #e9ecef;
    border-left: 3px solid #4a6cf7;
    padding-left: 17px;
}

/* Content Area */
.content-area {
    flex: 1;
    padding: 20px;
}

/* Dashboard Cards */
.dashboard-section {
    margin-bottom: 30px;
}

.dashboard-title {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.dashboard-card-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.dashboard-card-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-card-item {
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.dashboard-card-item:last-child {
    border-bottom: none;
}

.dashboard-card-link {
    display: block;
    color: #4a6cf7;
    text-decoration: none;
}

.dashboard-card-link:hover {
    text-decoration: underline;
}

.dashboard-card-footer {
    margin-top: 15px;
    text-align: center;
}

.view-more-link {
    display: inline-block;
    padding: 5px 15px;
    background-color: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-radius: 3px;
    font-size: 14px;
}

.view-more-link:hover {
    background-color: #e9ecef;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-select {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

/* Buttons */
.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    border-radius: 4px;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    text-decoration: none;
}

.btn-primary {
    color: #fff;
    background-color: #4a6cf7;
    border-color: #4a6cf7;
}

.btn-primary:hover {
    color: #fff;
    background-color: #3a5bd7;
    border-color: #3a5bd7;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    color: #fff;
    background-color: #5a6268;
    border-color: #545b62;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    color: #fff;
    background-color: #c82333;
    border-color: #bd2130;
}

/* Tables */
.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table tbody + tbody {
    border-top: 2px solid #dee2e6;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

/* Alerts */
.alert {
    position: relative;
    padding: 12px 20px;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

/* Hide loading overlay */
.loading-overlay {
    display: none !important;
}

/* Hide spinner */
.spinner-border {
    display: none !important;
}

/* Show button text */
.button-text {
    display: inline !important;
}
';

$adminCssPath = __DIR__ . '/admin/assets/css/improved-admin.css';
if (file_put_contents($adminCssPath, $adminCssContent)) {
    if ($isWeb) output("<div class='success'>Created improved admin CSS file: $adminCssPath</div>", true);
    else output("Created improved admin CSS file: $adminCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create improved admin CSS file</div>", true);
    else output("Error: Failed to create improved admin CSS file");
}