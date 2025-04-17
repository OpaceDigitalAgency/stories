<?php
/**
 * File Upload Utility Class
 * 
 * This class provides methods for handling file uploads.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class FileUpload {
    /**
     * @var array Allowed file types
     */
    private $allowedTypes;
    
    /**
     * @var int Maximum file size in bytes
     */
    private $maxSize;
    
    /**
     * @var string Upload directory
     */
    private $uploadDir;
    
    /**
     * @var string Base URL for uploaded files
     */
    private $baseUrl;
    
    /**
     * @var array Upload errors
     */
    private $errors = [];
    
    /**
     * Constructor
     * 
     * @param array $config Media configuration
     */
    public function __construct($config) {
        // Debug: Check if config is valid
        if (!is_array($config)) {
            throw new Exception("Invalid configuration: Not an array");
        }
        
        // Check required configuration keys
        $requiredKeys = ['allowed_types', 'max_file_size', 'upload_dir', 'base_url'];
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                throw new Exception("Missing required configuration key: $key");
            }
        }
        
        $this->allowedTypes = $config['allowed_types'];
        $this->maxSize = $config['max_file_size'];
        $this->uploadDir = $config['upload_dir'];
        $this->baseUrl = $config['base_url'];
        
        // Debug: Print upload directory
        error_log("Upload directory: " . $this->uploadDir);
        error_log("Base URL: " . $this->baseUrl);
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Failed to create upload directory: " . $this->uploadDir);
            }
        }
        
        // Check if upload directory is writable
        if (!is_writable($this->uploadDir)) {
            throw new Exception("Upload directory is not writable: " . $this->uploadDir);
        }
    }
    
    /**
     * Upload a file
     * 
     * @param array $file File data from $_FILES
     * @param string $entityType Entity type (story, author, etc.)
     * @param int $entityId Entity ID
     * @param string $type File type (cover, avatar, etc.)
     * @return array|bool File data if upload is successful, false otherwise
     */
    public function upload($file, $entityType, $entityId, $type) {
        // Validate file
        if (!$this->validate($file)) {
            return false;
        }
        
        // Generate unique filename
        $filename = $this->generateFilename($file['name']);
        
        // Create entity directory if it doesn't exist
        $entityDir = $this->uploadDir . $entityType . '/' . $entityId . '/';
        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0755, true);
        }
        
        // Upload file
        $filePath = $entityDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->errors[] = "Failed to upload file";
            return false;
        }
        
        // Get image dimensions
        $dimensions = $this->getImageDimensions($filePath);
        
        // Insert file data into database
        $db = Database::getInstance();
        
        try {
            $query = "INSERT INTO media (entity_type, entity_id, type, url, width, height, alt_text, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $url = $this->baseUrl . $entityType . '/' . $entityId . '/' . $filename;
            $altText = isset($_POST['alt_text']) ? Validator::sanitizeString($_POST['alt_text']) : '';
            
            $db->query($query, [
                $entityType,
                $entityId,
                $type,
                $url,
                $dimensions['width'],
                $dimensions['height'],
                $altText,
                date('Y-m-d H:i:s')
            ]);
            
            $mediaId = $db->lastInsertId();
            
            return [
                'id' => $mediaId,
                'url' => $url,
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'alt_text' => $altText
            ];
        } catch (Exception $e) {
            $this->errors[] = "Failed to save file data: " . $e->getMessage();
            
            // Delete uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return false;
        }
    }
    
    /**
     * Delete a file
     * 
     * @param int $mediaId Media ID
     * @return bool True if deletion is successful
     */
    public function delete($mediaId) {
        $db = Database::getInstance();
        
        try {
            // Get file data
            $query = "SELECT entity_type, entity_id, url FROM media WHERE id = ? LIMIT 1";
            $stmt = $db->query($query, [$mediaId]);
            
            if ($stmt->rowCount() === 0) {
                $this->errors[] = "File not found";
                return false;
            }
            
            $file = $stmt->fetch();
            
            // Delete file from database
            $query = "DELETE FROM media WHERE id = ?";
            $db->query($query, [$mediaId]);
            
            // Delete file from disk
            $filePath = $this->getFilePathFromUrl($file['url']);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return true;
        } catch (Exception $e) {
            $this->errors[] = "Failed to delete file: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Validate a file
     * 
     * @param array $file File data from $_FILES
     * @return bool True if file is valid
     */
    private function validate($file) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "Error uploading file";
            return false;
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowedTypes)) {
            $this->errors[] = "Invalid file type. Allowed types: " . implode(', ', $this->allowedTypes);
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = "File size exceeds the maximum allowed size of " . $this->formatBytes($this->maxSize);
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate a unique filename
     * 
     * @param string $originalName Original filename
     * @return string Generated filename
     */
    private function generateFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = Validator::generateSlug($basename);
        
        return $basename . '-' . uniqid() . '.' . $extension;
    }
    
    /**
     * Get image dimensions
     * 
     * @param string $filePath Path to image file
     * @return array Image dimensions
     */
    private function getImageDimensions($filePath) {
        $dimensions = [
            'width' => 0,
            'height' => 0
        ];
        
        if (file_exists($filePath)) {
            list($width, $height) = getimagesize($filePath);
            $dimensions['width'] = $width;
            $dimensions['height'] = $height;
        }
        
        return $dimensions;
    }
    
    /**
     * Get file path from URL
     * 
     * @param string $url File URL
     * @return string File path
     */
    private function getFilePathFromUrl($url) {
        $relativePath = str_replace($this->baseUrl, '', $url);
        return $this->uploadDir . $relativePath;
    }
    
    /**
     * Get upload errors
     * 
     * @return array Upload errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted bytes
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}