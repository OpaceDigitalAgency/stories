<?php
/**
 * OpenAI Models Helper
 * 
 * This file provides functions to fetch, cache, and manage OpenAI models.
 */

/**
 * Fetch available models from OpenAI API
 * 
 * @param string $apiKey The OpenAI API key
 * @param bool $forceRefresh Whether to force a refresh of the cached models
 * @return array Array of available models categorized by type
 */
function fetchOpenAIModels($apiKey, $forceRefresh = false) {
    global $db;
    
    // Check if we have cached models and they're not expired
    $cacheExpiry = 24 * 60 * 60; // 24 hours in seconds
    $cachedModels = getCachedModels();
    
    if (!$forceRefresh && $cachedModels && $cachedModels['timestamp'] > (time() - $cacheExpiry)) {
        return $cachedModels['models'];
    }
    
    // Default models to use if API call fails
    $defaultModels = [
        'image' => [
            'gpt-image-1' => 'GPT Image 1 (Latest)',
            'dall-e-3' => 'DALL·E 3 (Legacy)',
            'dall-e-2' => 'DALL·E 2 (Legacy)'
        ],
        'text' => [
            'gpt-4.1' => 'GPT-4.1 (Latest)',
            'gpt-4o' => 'GPT-4o (Balanced)',
            'o4-mini' => 'o4-mini (Fast)',
            'o3' => 'o3 (Powerful)',
            'o3-mini' => 'o3-mini (Balanced)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Economical)'
        ]
    ];
    
    // If no API key, return default models
    if (empty($apiKey)) {
        return $defaultModels;
    }
    
    try {
        // Make API request to fetch models
        $url = "https://api.openai.com/v1/models";
        $headers = [
            "Authorization: Bearer $apiKey"
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10 // 10 second timeout
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err || $httpCode !== 200) {
            error_log("Error fetching OpenAI models: $err, HTTP code: $httpCode");
            return $defaultModels;
        }
        
        $models = json_decode($response, true);
        if (!isset($models['data']) || !is_array($models['data'])) {
            error_log("Invalid response from OpenAI API: " . substr($response, 0, 200));
            return $defaultModels;
        }
        
        // Process and categorize models
        $availableModels = [
            'image' => [],
            'text' => []
        ];
        
        // Model name mappings for friendly display
        $modelNames = [
            'gpt-image-1' => 'GPT Image 1 (Latest)',
            'dall-e-3' => 'DALL·E 3 (Legacy)',
            'dall-e-2' => 'DALL·E 2 (Legacy)',
            'gpt-4.1' => 'GPT-4.1 (Latest)',
            'gpt-4o' => 'GPT-4o (Balanced)',
            'o4-mini' => 'o4-mini (Fast)',
            'o3' => 'o3 (Powerful)',
            'o3-mini' => 'o3-mini (Balanced)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Economical)'
        ];
        
        foreach ($models['data'] as $model) {
            $modelId = $model['id'];
            
            // Skip deprecated or non-relevant models
            if (strpos($modelId, 'deprecated') !== false || 
                strpos($modelId, 'instruct') !== false ||
                strpos($modelId, 'embedding') !== false ||
                strpos($modelId, 'search') !== false ||
                strpos($modelId, 'similarity') !== false ||
                strpos($modelId, 'edit') !== false ||
                strpos($modelId, 'audio') !== false ||
                strpos($modelId, 'whisper') !== false ||
                strpos($modelId, 'moderation') !== false) {
                continue;
            }
            
            // Categorize models
            if (strpos($modelId, 'dall-e') === 0 || strpos($modelId, 'gpt-image') === 0) {
                $displayName = isset($modelNames[$modelId]) ? $modelNames[$modelId] : $modelId;
                $availableModels['image'][$modelId] = $displayName;
            } 
            else if (strpos($modelId, 'gpt-4') === 0 || 
                     strpos($modelId, 'gpt-3.5') === 0 ||
                     strpos($modelId, 'o3') === 0 ||
                     strpos($modelId, 'o4') === 0) {
                $displayName = isset($modelNames[$modelId]) ? $modelNames[$modelId] : $modelId;
                $availableModels['text'][$modelId] = $displayName;
            }
        }
        
        // If no models found in a category, use defaults
        if (empty($availableModels['image'])) {
            $availableModels['image'] = $defaultModels['image'];
        }
        
        if (empty($availableModels['text'])) {
            $availableModels['text'] = $defaultModels['text'];
        }
        
        // Cache the models
        cacheModels($availableModels);
        
        return $availableModels;
        
    } catch (Exception $e) {
        error_log("Exception fetching OpenAI models: " . $e->getMessage());
        return $defaultModels;
    }
}

/**
 * Get cached models from the database
 * 
 * @return array|null Cached models or null if no cache exists
 */
function getCachedModels() {
    global $db;
    
    try {
        // Check if the cache table exists
        $stmt = $db->query("SHOW TABLES LIKE 'ai_models_cache'");
        if ($stmt->rowCount() === 0) {
            // Create the cache table if it doesn't exist
            $db->exec("CREATE TABLE ai_models_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                models_data LONGTEXT NOT NULL,
                timestamp INT NOT NULL
            )");
            return null;
        }
        
        // Get the most recent cache entry
        $stmt = $db->query("SELECT models_data, timestamp FROM ai_models_cache ORDER BY timestamp DESC LIMIT 1");
        $cache = $stmt->fetch();
        
        if (!$cache) {
            return null;
        }
        
        return [
            'models' => json_decode($cache['models_data'], true),
            'timestamp' => $cache['timestamp']
        ];
        
    } catch (Exception $e) {
        error_log("Error getting cached models: " . $e->getMessage());
        return null;
    }
}

/**
 * Cache models in the database
 * 
 * @param array $models The models to cache
 * @return bool Whether the caching was successful
 */
function cacheModels($models) {
    global $db;
    
    try {
        // Check if the cache table exists
        $stmt = $db->query("SHOW TABLES LIKE 'ai_models_cache'");
        if ($stmt->rowCount() === 0) {
            // Create the cache table if it doesn't exist
            $db->exec("CREATE TABLE ai_models_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                models_data LONGTEXT NOT NULL,
                timestamp INT NOT NULL
            )");
        }
        
        // Insert the new cache entry
        $stmt = $db->prepare("INSERT INTO ai_models_cache (models_data, timestamp) VALUES (?, ?)");
        $stmt->execute([json_encode($models), time()]);
        
        // Keep only the 5 most recent cache entries
        $db->exec("DELETE FROM ai_models_cache WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM ai_models_cache ORDER BY timestamp DESC LIMIT 5
            ) as recent
        )");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error caching models: " . $e->getMessage());
        return false;
    }
}
