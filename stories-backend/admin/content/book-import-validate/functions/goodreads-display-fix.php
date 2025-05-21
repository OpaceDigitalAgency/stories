<?php
/**
 * Goodreads Display Fix
 * 
 * This file contains functions to clean up Goodreads data for display in the validation interface.
 * It fixes HTML artifacts and other formatting issues in the Goodreads data.
 */

/**
 * Clean Goodreads field value for display
 * 
 * @param string $value The value to clean
 * @return string The cleaned value
 */
function cleanGoodreadsFieldValue($value) {
    if (empty($value)) {
        return $value;
    }
    
    // If it's not a string, return as is
    if (!is_string($value)) {
        return $value;
    }
    
    // Remove HTML tags
    $value = strip_tags($value);
    
    // Remove ", link, opens in new tab" text
    $value = preg_replace('/, link, opens in new tab.*$/i', '', $value);
    
    // Remove role="link" and other attributes
    $value = preg_replace('/\s+role="[^"]*"/i', '', $value);
    
    // Clean up series format like "The Worst Witch (#1)"
    if (preg_match('/^(.*?)\s*\(#\d+\)$/', $value, $matches)) {
        $value = $matches[1];
    }
    
    // Trim whitespace
    $value = trim($value);
    
    return $value;
}

/**
 * Clean Goodreads data for display
 * 
 * @param array $data The Goodreads data to clean
 * @return array The cleaned data
 */
function cleanGoodreadsData($data) {
    if (empty($data) || !is_array($data)) {
        return $data;
    }
    
    // Fields that need special cleaning
    $fieldsToClean = [
        'title',
        'author',
        'series',
        'characters',
        'settings',
        'format',
        'publisher',
        'language',
        'description'
    ];
    
    // Clean each field
    foreach ($fieldsToClean as $field) {
        if (isset($data[$field])) {
            $data[$field] = cleanGoodreadsFieldValue($data[$field]);
        }
    }
    
    return $data;
}

/**
 * Apply Goodreads display fix to validation data
 * 
 * @param array $validationData The validation data to fix
 * @return array The fixed validation data
 */
function applyGoodreadsDisplayFix($validationData) {
    if (empty($validationData) || !is_array($validationData)) {
        return $validationData;
    }
    
    // Check if we have Goodreads data
    if (isset($validationData['sourceData']['goodreads'])) {
        // Check if we have data to clean
        if (isset($validationData['sourceData']['goodreads']['data'])) {
            // Clean the data
            $validationData['sourceData']['goodreads']['data'] = cleanGoodreadsData($validationData['sourceData']['goodreads']['data']);
        }
    }
    
    return $validationData;
}
