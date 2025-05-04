<?php

// Include header
include 'includes/header.php';


// Page variables
$pageTitle = 'Index';
$currentPage = 'index';

/**
 * Admin Redirect
 * 
 * This file redirects to the new admin interface.
 */

// Redirect to the new admin interface
header("Location: dashboard.php");
exit;

// Include footer
include 'includes/footer.php';
