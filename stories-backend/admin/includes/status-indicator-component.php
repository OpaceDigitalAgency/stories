<?php

// Include header
include 'header.php';


// Page variables
$pageTitle = 'Status Indicator Component';
$currentPage = 'status-indicator-component';

/**
 * Status Indicator Component
 * 
 * A reusable status indicator component for content items.
 * 
 * Usage:
 * include '../includes/status-indicator-component.php';
 * echo getStatusIndicator('published');
 */

/**
 * Returns HTML for a status indicator
 * 
 * @param string $status The status to display (e.g., 'published', 'draft', 'pending')
 * @param array $customStatuses Optional custom statuses with their classes
 * @return string HTML for the status indicator
 */
function getStatusIndicator($status, $customStatuses = []) {
    // Default statuses and their classes
    $statuses = [
        'published' => 'status-published',
        'draft' => 'status-draft',
        'pending' => 'status-pending',
        'featured' => 'status-featured',
        'archived' => 'status-archived',
        'needs_moderation' => 'status-needs-moderation',
        'rejected' => 'status-rejected'
    ];
    
    // Merge custom statuses
    if (!empty($customStatuses)) {
        $statuses = array_merge($statuses, $customStatuses);
    }
    
    // Default to 'unknown' if status not found
    $statusClass = isset($statuses[$status]) ? $statuses[$status] : 'status-unknown';
    
    // Format the status text (replace underscores with spaces and capitalize)
    $statusText = ucwords(str_replace('_', ' ', $status));
    
    // Return the HTML
    return '<span class="status-indicator ' . $statusClass . '">' . $statusText . '</span>';
}

/**
 * Renders a status indicator for a boolean field
 * 
 * @param bool $value The boolean value
 * @param string $trueLabel Label for true value
 * @param string $falseLabel Label for false value
 * @param string $trueClass CSS class for true value
 * @param string $falseClass CSS class for false value
 * @return string HTML for the status indicator
 */
function getBooleanStatusIndicator($value, $trueLabel = 'Yes', $falseLabel = 'No', $trueClass = 'status-published', $falseClass = 'status-draft') {
    if ($value) {
        return '<span class="status-indicator ' . $trueClass . '">' . $trueLabel . '</span>';
    } else {
        return '<span class="status-indicator ' . $falseClass . '">' . $falseLabel . '</span>';
    }
}

/**
 * Renders a featured status indicator
 * 
 * @param bool $isFeatured Whether the item is featured
 * @return string HTML for the featured status indicator
 */
function getFeaturedStatusIndicator($isFeatured) {
    return getBooleanStatusIndicator($isFeatured, 'Featured', 'Not Featured', 'status-featured', 'status-draft');
}

/**
 * Renders a published status indicator
 * 
 * @param bool $isPublished Whether the item is published
 * @return string HTML for the published status indicator
 */
function getPublishedStatusIndicator($isPublished) {
    return getBooleanStatusIndicator($isPublished, 'Published', 'Draft', 'status-published', 'status-draft');
}

/**
 * Renders a moderation status indicator
 * 
 * @param bool $needsModeration Whether the item needs moderation
 * @return string HTML for the moderation status indicator
 */
function getModerationStatusIndicator($needsModeration) {
    return getBooleanStatusIndicator($needsModeration, 'Needs Review', 'Approved', 'status-pending', 'status-published');
}


// Include footer
include 'footer.php';
