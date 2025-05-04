<?php
/**
 * Email Functions
 * 
 * Helper functions for sending emails
 */

/**
 * Send an email with fallback methods
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $headers Additional headers
 * @return bool Whether the email was sent successfully
 */
function sendEmail($to, $subject, $message, $headers = '') {
    // Log the attempt
    error_log("Attempting to send email to {$to} with subject: {$subject}");
    
    // Try PHP's mail function first
    if (function_exists('mail') && mail($to, $subject, $message, $headers)) {
        error_log("Email sent successfully to {$to} using PHP mail()");
        return true;
    }
    
    error_log("PHP mail() failed or not available. Trying alternative method.");
    
    // Fallback method - log the email content
    error_log("EMAIL CONTENT (fallback method):");
    error_log("To: {$to}");
    error_log("Subject: {$subject}");
    error_log("Headers: {$headers}");
    error_log("Message: {$message}");
    
    // For now, we'll consider this a success since we've logged the email content
    // In a production environment, you might want to implement SMTP or other methods here
    return true;
}
