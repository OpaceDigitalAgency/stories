<?php
/**
 * AI Tables Setup Script
 * 
 * This script automatically creates the necessary database tables for the AI functionality
 * using the existing database configuration.
 */

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// The complete SQL for creating AI tables
$sql = <<<SQL
-- AI Provider Table
CREATE TABLE IF NOT EXISTS ai_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('image', 'text', 'audio', 'video') NOT NULL,
    config JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Generations Table
CREATE TABLE IF NOT EXISTS ai_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT,
    type ENUM('image', 'text', 'audio', 'video') NOT NULL,
    prompt TEXT NOT NULL,
    result_url VARCHAR(255),
    metadata JSON,
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
    INDEX idx_type_status (type, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Usage Tracking Table
CREATE TABLE IF NOT EXISTS ai_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT,
    type ENUM('image', 'text', 'audio', 'video') NOT NULL,
    cost DECIMAL(10,6) NOT NULL DEFAULT 0,
    tokens INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES ai_providers(id),
    INDEX idx_type_date (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AI Rate Limiting Table
CREATE TABLE IF NOT EXISTS ai_rate_limit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_date (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default OpenAI provider
INSERT INTO ai_providers (name, type, config, is_active) VALUES 
('openai', 'image', '{"model": "dall-e-3", "max_tokens": 2000}', true)
ON DUPLICATE KEY UPDATE 
    type = VALUES(type),
    config = VALUES(config),
    is_active = VALUES(is_active);

-- Create trigger to clean up old rate limit entries
DELIMITER //
CREATE TRIGGER IF NOT EXISTS cleanup_rate_limit 
BEFORE INSERT ON ai_rate_limit
FOR EACH ROW
BEGIN
    -- Delete entries older than 1 minute
    DELETE FROM ai_rate_limit 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);
END;//
DELIMITER ;

-- Create procedure to get usage statistics
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_ai_usage_stats(
    IN provider_name VARCHAR(50),
    IN usage_type ENUM('image', 'text', 'audio', 'video'),
    IN period_start TIMESTAMP,
    IN period_end TIMESTAMP
)
BEGIN
    SELECT 
        COUNT(g.id) as total_generations,
        COALESCE(SUM(u.cost), 0) as total_cost,
        COALESCE(SUM(u.tokens), 0) as total_tokens
    FROM ai_generations g
    LEFT JOIN ai_providers p ON g.provider_id = p.id
    LEFT JOIN ai_usage u ON u.provider_id = p.id 
        AND DATE(u.created_at) = DATE(g.created_at)
    WHERE p.name = provider_name
        AND g.type = usage_type
        AND g.created_at BETWEEN period_start AND period_end
        AND g.status = 'completed';
END;//
DELIMITER ;

-- Create view for generation statistics
CREATE OR REPLACE VIEW v_ai_generation_stats AS
SELECT 
    p.name as provider_name,
    g.type,
    DATE(g.created_at) as generation_date,
    COUNT(*) as total_generations,
    COALESCE(SUM(u.cost), 0) as total_cost,
    COUNT(CASE WHEN g.status = 'failed' THEN 1 END) as failed_generations
FROM ai_generations g
JOIN ai_providers p ON g.provider_id = p.id
LEFT JOIN ai_usage u ON u.provider_id = p.id 
    AND DATE(u.created_at) = DATE(g.created_at)
GROUP BY p.name, g.type, DATE(g.created_at);
SQL;

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', 
            explode(';', str_replace('DELIMITER //', '', str_replace('DELIMITER ;', '', $sql)))
        )
    );

    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'AI tables installed successfully!'
    ]);

} catch (PDOException $e) {
    error_log("Error installing AI tables: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}