<?php
/**
 * AI Tables Setup Script
 * 
 * This script creates the necessary database tables for the AI functionality.
 * You can either:
 * 1. Run this script directly to install the tables
 * 2. Copy the SQL from the $sql variable and run it in phpMyAdmin
 */

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

// If this script is being run directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    // Check if we should just display the SQL
    if (isset($_GET['show_sql'])) {
        header('Content-Type: text/plain');
        echo $sql;
        exit;
    }

    // Otherwise, try to install the tables
    if (isset($_POST['install'])) {
        try {
            $db = new PDO(
                "mysql:host={$_POST['host']};dbname={$_POST['database']};charset=utf8mb4",
                $_POST['username'],
                $_POST['password']
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

            $success = "AI tables installed successfully!";

        } catch (PDOException $e) {
            $error = "Error installing AI tables: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Tables Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
            line-height: 1.6;
        }
        .options {
            margin: 2rem 0;
        }
        .button {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 1rem;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #0056b3;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success {
            padding: 1rem;
            background: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error {
            padding: 1rem;
            background: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <h1>AI Tables Setup</h1>
    <p>This script helps you set up the necessary database tables for the AI functionality.</p>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="options">
        <h2>Options:</h2>
        
        <h3>1. Install Tables Directly</h3>
        <form method="post">
            <div class="form-group">
                <label for="host">Database Host:</label>
                <input type="text" id="host" name="host" value="localhost" required>
            </div>
            <div class="form-group">
                <label for="database">Database Name:</label>
                <input type="text" id="database" name="database" value="stories_db" required>
            </div>
            <div class="form-group">
                <label for="username">Database Username:</label>
                <input type="text" id="username" name="username" value="stories_user" required>
            </div>
            <div class="form-group">
                <label for="password">Database Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="install" class="button">Install Tables</button>
        </form>

        <h3>2. Get SQL for phpMyAdmin</h3>
        <p>
            <a href="?show_sql=1" class="button">Show SQL</a>
        </p>
    </div>

    <div class="instructions">
        <h2>Instructions:</h2>
        <ol>
            <li>Either fill in your database details and click "Install Tables"</li>
            <li>Or click "Show SQL" to get the SQL that you can copy and paste into phpMyAdmin</li>
            <li>After installation, make sure to set your OpenAI API key in the environment variables</li>
        </ol>
    </div>
</body>
</html>