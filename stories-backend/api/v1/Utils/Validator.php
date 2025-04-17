<?php
/**
 * Input Validator Utility Class
 * 
 * This class provides methods for validating and sanitizing input data
 * to prevent security vulnerabilities like SQL injection and XSS attacks.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Utils;

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
        self::$errors = [];
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
     * @param string $string String to validate
     * @param string $field Field name for error message
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return bool True if string length is valid
     */
    public static function length($string, $field, $min = 0, $max = null) {
        $length = mb_strlen($string);
        
        if ($length < $min) {
            self::$errors[$field] = "The $field must be at least $min characters";
            return false;
        }
        
        if ($max !== null && $length > $max) {
            self::$errors[$field] = "The $field cannot exceed $max characters";
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
     * @return bool True if value is numeric and within range
     */
    public static function numeric($value, $field, $min = null, $max = null) {
        if (!is_numeric($value)) {
            self::$errors[$field] = "The $field must be a number";
            return false;
        }
        
        if ($min !== null && $value < $min) {
            self::$errors[$field] = "The $field must be at least $min";
            return false;
        }
        
        if ($max !== null && $value > $max) {
            self::$errors[$field] = "The $field cannot exceed $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate integer value
     * 
     * @param mixed $value Value to validate
     * @param string $field Field name for error message
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return bool True if value is an integer and within range
     */
    public static function integer($value, $field, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            self::$errors[$field] = "The $field must be an integer";
            return false;
        }
        
        return self::numeric($value, $field, $min, $max);
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date to validate
     * @param string $field Field name for error message
     * @param string $format Date format
     * @return bool True if date is valid
     */
    public static function date($date, $field, $format = 'Y-m-d') {
        $d = \DateTime::createFromFormat($format, $date);
        
        if (!$d || $d->format($format) !== $date) {
            self::$errors[$field] = "The $field must be a valid date in the format $format";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $url URL to validate
     * @param string $field Field name for error message
     * @return bool True if URL is valid
     */
    public static function url($url, $field = 'url') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            self::$errors[$field] = "The $field must be a valid URL";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate against a regex pattern
     * 
     * @param string $value Value to validate
     * @param string $pattern Regex pattern
     * @param string $field Field name for error message
     * @param string $message Custom error message
     * @return bool True if value matches pattern
     */
    public static function pattern($value, $pattern, $field, $message = null) {
        if (!preg_match($pattern, $value)) {
            self::$errors[$field] = $message ?? "The $field format is invalid";
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate that a value is in a list of allowed values
     * 
     * @param mixed $value Value to validate
     * @param array $allowed Allowed values
     * @param string $field Field name for error message
     * @return bool True if value is in allowed list
     */
    public static function inList($value, $allowed, $field) {
        if (!in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            self::$errors[$field] = "The $field must be one of: $allowedStr";
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize string to prevent XSS
     * 
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString($string) {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize an array of data
     * 
     * @param array $data Data to sanitize
     * @return array Sanitized data
     */
    public static function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeData($value);
            } else if (is_string($value)) {
                $sanitized[$key] = self::sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
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
     * Check if there are any validation errors
     * 
     * @return bool True if there are errors
     */
    public static function hasErrors() {
        return !empty(self::$errors);
    }
    
    /**
     * Reset validation errors
     */
    public static function resetErrors() {
        self::$errors = [];
    }
    
    /**
     * Validate pagination parameters
     * 
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param int $maxPageSize Maximum allowed page size
     * @return array Validated pagination parameters
     */
    public static function validatePagination($page, $pageSize, $maxPageSize = 100) {
        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT, ['options' => ['default' => 25, 'min_range' => 1, 'max_range' => $maxPageSize]]);
        
        return ['page' => $page, 'pageSize' => $pageSize];
    }
    
    /**
     * Validate and sanitize sort parameters
     * 
     * @param string $sort Sort parameter
     * @param array $allowedFields Allowed fields to sort by
     * @return array|null Validated sort parameters or null if invalid
     */
    public static function validateSort($sort, $allowedFields) {
        if (empty($sort)) {
            return null;
        }
        
        $sortParts = explode(':', $sort);
        $field = $sortParts[0];
        $direction = isset($sortParts[1]) && strtolower($sortParts[1]) === 'desc' ? 'DESC' : 'ASC';
        
        if (!in_array($field, $allowedFields)) {
            return null;
        }
        
        return ['field' => $field, 'direction' => $direction];
    }
}