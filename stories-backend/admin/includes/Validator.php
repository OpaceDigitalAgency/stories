<?php
/**
 * Validator Utility Class
 * 
 * This class provides methods for validating and sanitizing input data.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class Validator {
    /**
     * @var array Validation errors
     */
    private static $errors = [];
    
    /**
     * Validate required fields
     * 
     * @param array $data Input data
     * @param array $fields Required fields
     * @return bool True if all required fields are present
     */
    public static function required($data, $fields) {
        $valid = true;
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                self::$errors[$field] = "The $field field is required";
                $valid = false;
            }
        }
        
        return $valid;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @param string $field Field name for error message
     * @return bool True if email is valid
     */
    public static function email($email, $field = 'email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$errors[$field] = "The $field must be a valid email address";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate string length
     * 
     * @param string $value Value to validate
     * @param string $field Field name for error message
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return bool True if length is valid
     */
    public static function length($value, $field, $min = null, $max = null) {
        $length = mb_strlen($value);
        
        if ($min !== null && $length < $min) {
            self::$errors[$field] = "The $field must be at least $min characters";
            return false;
        }
        
        if ($max !== null && $length > $max) {
            self::$errors[$field] = "The $field must be at most $max characters";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * @param string $field Field name for error message
     * @param float $min Minimum value
     * @param float $max Maximum value
     * @return bool True if value is valid
     */
    public static function numeric($value, $field, $min = null, $max = null) {
        if (!is_numeric($value)) {
            self::$errors[$field] = "The $field must be a number";
            return false;
        }
        
        $value = (float)$value;
        
        if ($min !== null && $value < $min) {
            self::$errors[$field] = "The $field must be at least $min";
            return false;
        }
        
        if ($max !== null && $value > $max) {
            self::$errors[$field] = "The $field must be at most $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date to validate
     * @param string $field Field name for error message
     * @param string $format Date format
     * @return bool True if date is valid
     */
    public static function date($date, $field, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        
        if (!$d || $d->format($format) !== $date) {
            self::$errors[$field] = "The $field must be a valid date in the format $format";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file File data from $_FILES
     * @param string $field Field name for error message
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return bool True if file is valid
     */
    public static function file($file, $field, $allowedTypes, $maxSize) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            self::$errors[$field] = "Error uploading file";
            return false;
        }
        
        if (!in_array($file['type'], $allowedTypes)) {
            self::$errors[$field] = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            self::$errors[$field] = "File size exceeds the maximum allowed size of " . self::formatBytes($maxSize);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate slug format
     * 
     * @param string $slug Slug to validate
     * @param string $field Field name for error message
     * @return bool True if slug is valid
     */
    public static function slug($slug, $field = 'slug') {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            self::$errors[$field] = "The $field must contain only lowercase letters, numbers, and hyphens";
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize string
     * 
     * @param string $value String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize HTML
     * 
     * @param string $value HTML to sanitize
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml($value) {
        // Allow basic HTML tags
        $allowedTags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code>';
        
        // Remove all HTML tags except allowed ones
        $value = strip_tags($value, $allowedTags);
        
        // Remove JavaScript events and inline styles
        $value = preg_replace('/(on\w+)=".*?"/i', '', $value);
        $value = preg_replace('/style=".*?"/i', '', $value);
        
        return $value;
    }
    
    /**
     * Generate a slug from a string
     * 
     * @param string $string String to convert to slug
     * @return string Generated slug
     */
    public static function generateSlug($string) {
        // Convert to lowercase
        $string = mb_strtolower($string);
        
        // Replace non-alphanumeric characters with hyphens
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);
        
        // Remove leading and trailing hyphens
        $string = trim($string, '-');
        
        return $string;
    }
    
    /**
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Clear validation errors
     */
    public static function clearErrors() {
        self::$errors = [];
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted bytes
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}