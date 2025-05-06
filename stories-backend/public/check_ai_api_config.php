<?php
/**
 * Check AI API Configuration
 * 
 * This script checks the OpenAI API configuration and tests connectivity.
 */

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "<p>✅ Database connection successful.</p>";
} catch (PDOException $e) {
    die("<p>❌ Database Error: " . $e->getMessage() . "</p>");
}

// Check OpenAI provider configuration
try {
    $stmt = $db->query("SELECT * FROM ai_providers WHERE name = 'openai'");
    $provider = $stmt->fetch();
    
    echo "<h2>OpenAI Provider Configuration</h2>";
    
    if ($provider) {
        echo "<p>✅ OpenAI provider found in database.</p>";
        
        // Display provider details
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><td>{$provider['id']}</td></tr>";
        echo "<tr><th>Name</th><td>{$provider['name']}</td></tr>";
        echo "<tr><th>Type</th><td>{$provider['type']}</td></tr>";
        echo "<tr><th>Active</th><td>" . ($provider['is_active'] ? 'Yes' : 'No') . "</td></tr>";
        
        // Parse and display config
        $config = json_decode($provider['config'], true);
        echo "<tr><th>Model</th><td>" . ($config['model'] ?? 'Not set') . "</td></tr>";
        echo "<tr><th>Text Model</th><td>" . ($config['text_model'] ?? 'Not set') . "</td></tr>";
        echo "<tr><th>Max Tokens</th><td>" . ($config['max_tokens'] ?? 'Not set') . "</td></tr>";
        echo "<tr><th>Temperature</th><td>" . ($config['temperature'] ?? 'Not set') . "</td></tr>";
        
        // Check if API key is set
        if (isset($config['api_key'])) {
            echo "<tr><th>API Key</th><td>Set (masked)</td></tr>";
        } else {
            echo "<tr><th>API Key</th><td style='color:red;'>❌ Not set in provider config</td></tr>";
        }
        
        echo "</table>";
        
        // Check for API key in environment or config files
        echo "<h3>API Key Configuration</h3>";
        
        // Check environment variable
        $env_api_key = getenv('OPENAI_API_KEY');
        if ($env_api_key) {
            echo "<p>✅ OPENAI_API_KEY environment variable is set.</p>";
        } else {
            echo "<p>❌ OPENAI_API_KEY environment variable is not set.</p>";
        }
        
        // Check config file
        $config_files = [
            '../admin/includes/config.php',
            '../admin/includes/ai-config.php',
            '../config/ai-config.php',
            '../config/openai-config.php'
        ];
        
        $config_file_found = false;
        foreach ($config_files as $file) {
            if (file_exists($file)) {
                echo "<p>✅ Config file found: $file</p>";
                $config_file_found = true;
                
                // Check file content for API key
                $content = file_get_contents($file);
                if (strpos($content, 'API_KEY') !== false || strpos($content, 'OPENAI_KEY') !== false) {
                    echo "<p>✅ API key reference found in config file.</p>";
                } else {
                    echo "<p>⚠️ No API key reference found in config file.</p>";
                }
                
                break;
            }
        }
        
        if (!$config_file_found) {
            echo "<p>❌ No config file found for API key.</p>";
        }
        
    } else {
        echo "<p>❌ OpenAI provider not found in database.</p>";
    }
    
    // Check API endpoint configuration
    echo "<h3>API Endpoint Configuration</h3>";
    
    // Check for API endpoint in JavaScript
    $js_files = [
        '../admin/js/ai-image-generator.js',
        '../admin/js/openai-integration.js',
        '../admin/js/ai-integration.js'
    ];
    
    $js_file_found = false;
    foreach ($js_files as $file) {
        if (file_exists($file)) {
            echo "<p>✅ JavaScript file found: $file</p>";
            $js_file_found = true;
            
            // Check file content for API endpoint
            $content = file_get_contents($file);
            if (strpos($content, 'api.storiesfromtheweb.org/api/v1/ai/image.php') !== false) {
                echo "<p>✅ API endpoint reference found in JavaScript file.</p>";
            } else {
                echo "<p>⚠️ No API endpoint reference found in JavaScript file.</p>";
            }
            
            break;
        }
    }
    
    if (!$js_file_found) {
        echo "<p>❌ No JavaScript file found for API endpoint.</p>";
    }
    
    // Check if the API endpoint exists
    $api_endpoint = '../api/v1/ai/image.php';
    if (file_exists($api_endpoint)) {
        echo "<p>✅ API endpoint file exists: $api_endpoint</p>";
        
        // Check file content
        $content = file_get_contents($api_endpoint);
        if (strpos($content, 'openai') !== false) {
            echo "<p>✅ OpenAI reference found in API endpoint file.</p>";
        } else {
            echo "<p>⚠️ No OpenAI reference found in API endpoint file.</p>";
        }
    } else {
        echo "<p>❌ API endpoint file does not exist: $api_endpoint</p>";
    }
    
    // Check for CORS headers
    echo "<h3>CORS Configuration</h3>";
    
    $htaccess_file = '../.htaccess';
    if (file_exists($htaccess_file)) {
        echo "<p>✅ .htaccess file exists.</p>";
        
        // Check file content for CORS headers
        $content = file_get_contents($htaccess_file);
        if (strpos($content, 'Access-Control-Allow-Origin') !== false) {
            echo "<p>✅ CORS headers found in .htaccess file.</p>";
        } else {
            echo "<p>⚠️ No CORS headers found in .htaccess file.</p>";
        }
    } else {
        echo "<p>❌ .htaccess file does not exist.</p>";
    }
    
    // Recommendations
    echo "<h2>Recommendations</h2>";
    echo "<ol>";
    
    if (!isset($config['api_key']) && !$env_api_key) {
        echo "<li>Set the OpenAI API key in the provider config or as an environment variable.</li>";
    }
    
    if (!$js_file_found || !file_exists($api_endpoint)) {
        echo "<li>Create or fix the API endpoint file at: $api_endpoint</li>";
    }
    
    echo "<li>Check the browser console for specific error messages when generating images.</li>";
    echo "<li>Ensure the API endpoint has proper error handling and returns detailed error messages.</li>";
    echo "<li>Add CORS headers to allow requests from the admin domain.</li>";
    echo "</ol>";
    
    // Create or update the API endpoint file
    echo "<h2>Fix API Endpoint</h2>";
    echo "<p>Click the button below to create or update the API endpoint file:</p>";
    echo "<form method='post'>";
    echo "<input type='submit' name='fix_api_endpoint' value='Fix API Endpoint'>";
    echo "</form>";
    
    if (isset($_POST['fix_api_endpoint'])) {
        // Create the directory if it doesn't exist
        $api_dir = '../api/v1/ai';
        if (!file_exists($api_dir)) {
            mkdir($api_dir, 0755, true);
            echo "<p>✅ Created directory: $api_dir</p>";
        }
        
        // Create the API endpoint file
        $api_content = <<<'EOT'
<?php
/**
 * OpenAI Image Generation API Endpoint
 * 
 * This endpoint handles requests to generate images using OpenAI's API.
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['prompt']) || empty($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

// Set default values
$size = isset($input['size']) ? $input['size'] : '1024x1024';
$style = isset($input['style']) ? $input['style'] : 'natural';
$quality = isset($input['quality']) ? $input['quality'] : 'standard';
$n = isset($input['n']) ? (int)$input['n'] : 1;

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get OpenAI provider config
try {
    $stmt = $db->query("SELECT * FROM ai_providers WHERE name = 'openai' AND is_active = 1");
    $provider = $stmt->fetch();
    
    if (!$provider) {
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI provider not found or not active']);
        exit;
    }
    
    $config = json_decode($provider['config'], true);
    
    // Check if API key is set
    $api_key = $config['api_key'] ?? getenv('OPENAI_API_KEY');
    
    if (!$api_key) {
        http_response_code(500);
        echo json_encode(['error' => 'OpenAI API key not configured']);
        exit;
    }
    
    // Set model
    $model = $config['model'] ?? 'gpt-image-1';
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get provider config: ' . $e->getMessage()]);
    exit;
}

// Prepare OpenAI API request
$url = 'https://api.openai.com/v1/images/generations';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
];

$data = [
    'model' => $model,
    'prompt' => $input['prompt'],
    'n' => $n,
    'size' => $size,
    'quality' => $quality,
    'style' => $style
];

// Make request to OpenAI API
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($curl_error) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . $curl_error]);
    exit;
}

// Decode response
$response_data = json_decode($response, true);

// Check for OpenAI API errors
if ($status_code !== 200) {
    http_response_code($status_code);
    echo json_encode([
        'error' => 'OpenAI API error',
        'status_code' => $status_code,
        'response' => $response_data
    ]);
    exit;
}

// Log generation in database
try {
    $stmt = $db->prepare("
        INSERT INTO ai_generations (provider_id, type, prompt, result_url, metadata, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $image_url = $response_data['data'][0]['url'] ?? null;
    $metadata = json_encode([
        'size' => $size,
        'style' => $style,
        'quality' => $quality,
        'n' => $n
    ]);
    
    $stmt->execute([
        $provider['id'],
        'image',
        $input['prompt'],
        $image_url,
        $metadata,
        'completed'
    ]);
    
    // Log usage
    $stmt = $db->prepare("
        INSERT INTO ai_usage (provider_id, type, cost)
        VALUES (?, ?, ?)
    ");
    
    // Calculate cost based on size and quality
    $cost = 0.04; // Base cost for 1024x1024 standard
    if ($size === '1792x1024' || $size === '1024x1792') {
        $cost = 0.08;
    }
    if ($quality === 'hd') {
        $cost *= 2;
    }
    
    $stmt->execute([
        $provider['id'],
        'image',
        $cost
    ]);
    
} catch (PDOException $e) {
    // Don't fail the request if logging fails
    error_log('Failed to log AI generation: ' . $e->getMessage());
}

// Return successful response
echo json_encode($response_data);
EOT;
        
        file_put_contents($api_endpoint, $api_content);
        echo "<p>✅ Created/updated API endpoint file: $api_endpoint</p>";
        
        // Create .htaccess file with CORS headers if it doesn't exist
        if (!file_exists($htaccess_file)) {
            $htaccess_content = <<<'EOT'
# Enable CORS
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
    
    # Handle OPTIONS method
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
EOT;
            
            file_put_contents($htaccess_file, $htaccess_content);
            echo "<p>✅ Created .htaccess file with CORS headers.</p>";
        } else {
            // Check if CORS headers are already in .htaccess
            $htaccess_content = file_get_contents($htaccess_file);
            if (strpos($htaccess_content, 'Access-Control-Allow-Origin') === false) {
                // Add CORS headers to existing .htaccess
                $cors_headers = <<<'EOT'

# Enable CORS
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
    
    # Handle OPTIONS method
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
EOT;
                
                file_put_contents($htaccess_file, $htaccess_content . $cors_headers);
                echo "<p>✅ Added CORS headers to existing .htaccess file.</p>";
            }
        }
        
        echo "<p>✅ API endpoint fixed. Try generating images again.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Error checking OpenAI provider: " . $e->getMessage() . "</p>";
}
?>
