<?php
require_once '../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if content is provided
if (!isset($_POST['content']) || empty($_POST['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No content provided']);
    exit;
}

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Get all available tags
    $stmt = $db->query("SELECT id, name FROM tags ORDER BY name");
    $allTags = $stmt->fetchAll();
    
    // Create a map of tag keywords to tag IDs
    $tagKeywords = [];
    foreach ($allTags as $tag) {
        // Add the tag name itself as a keyword
        $tagKeywords[strtolower($tag['name'])] = $tag['id'];
        
        // Add additional keywords for specific tags
        switch (strtolower($tag['name'])) {
            case 'adventure':
                $additionalKeywords = ['journey', 'quest', 'explore', 'expedition', 'travel', 'discover'];
                break;
            case 'animals':
                $additionalKeywords = ['dog', 'cat', 'pet', 'zoo', 'wildlife', 'bird', 'fish', 'bear', 'lion', 'tiger', 'elephant'];
                break;
            case 'children\'s story':
                $additionalKeywords = ['kid', 'child', 'young', 'bedtime', 'lesson'];
                break;
            case 'dinosaurs':
                $additionalKeywords = ['prehistoric', 'jurassic', 'trex', 't-rex', 'raptor', 'fossil'];
                break;
            case 'educational':
                $additionalKeywords = ['learn', 'school', 'teach', 'lesson', 'knowledge', 'fact'];
                break;
            case 'family':
                $additionalKeywords = ['parent', 'mother', 'father', 'sister', 'brother', 'mom', 'dad', 'grandparent'];
                break;
            case 'fantasy':
                $additionalKeywords = ['magic', 'wizard', 'dragon', 'fairy', 'elf', 'mythical', 'enchanted', 'spell'];
                break;
            case 'fiction':
                $additionalKeywords = ['story', 'tale', 'novel', 'imaginary', 'made-up'];
                break;
            case 'friendship':
                $additionalKeywords = ['friend', 'pal', 'buddy', 'companion', 'relationship', 'together'];
                break;
            case 'magic':
                $additionalKeywords = ['spell', 'wizard', 'witch', 'wand', 'potion', 'enchant', 'magical'];
                break;
            case 'monsters':
                $additionalKeywords = ['creature', 'beast', 'scary', 'giant', 'dragon', 'werewolf', 'vampire'];
                break;
            case 'robots':
                $additionalKeywords = ['machine', 'android', 'ai', 'artificial', 'mechanical', 'electronic', 'robot'];
                break;
            case 'space':
                $additionalKeywords = ['planet', 'star', 'galaxy', 'astronaut', 'rocket', 'alien', 'moon', 'sun', 'universe'];
                break;
            default:
                $additionalKeywords = [];
        }
        
        foreach ($additionalKeywords as $keyword) {
            $tagKeywords[$keyword] = $tag['id'];
        }
    }
    
    // Get content from request
    $content = $_POST['content'];
    
    // Analyze content and suggest tags
    $suggestedTags = [];
    $content = strtolower($content);
    
    // Check for each keyword in the content
    foreach ($tagKeywords as $keyword => $tagId) {
        if (strpos($content, $keyword) !== false) {
            $suggestedTags[$tagId] = true; // Use associative array to avoid duplicates
        }
    }
    
    // Convert to array of tag IDs
    $suggestedTagIds = array_keys($suggestedTags);
    
    // Get tag details for the suggested tags
    $suggestedTagDetails = [];
    if (!empty($suggestedTagIds)) {
        $placeholders = implode(',', array_fill(0, count($suggestedTagIds), '?'));
        $stmt = $db->prepare("SELECT id, name FROM tags WHERE id IN ($placeholders)");
        $stmt->execute($suggestedTagIds);
        $suggestedTagDetails = $stmt->fetchAll();
    }
    
    // Return suggested tags
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'tags' => $suggestedTagDetails
    ]);
    
} catch (PDOException $e) {
    error_log("Suggest tags error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}