<?php

namespace Stories\Lib\AI\Services;

use Stories\Lib\AI\Core\AIProvider;
use Stories\Lib\AI\Core\AIResponse;
use Stories\Lib\AI\Core\Config;
use Stories\Lib\AI\Providers\OpenAIProvider;

/**
 * Image Service
 * 
 * Handles AI image generation operations across different providers
 */
class ImageService {
    private Config $config;
    private ?AIProvider $provider = null;
    private array $supportedSizes = [
        '256x256',
        '512x512',
        '1024x1024',
        '1024x1792',
        '1792x1024'
    ];

    /**
     * Constructor
     * 
     * @param string|null $providerName Specific provider to use (optional)
     */
    public function __construct(?string $providerName = null) {
        $this->config = Config::getInstance();
        $this->initializeProvider($providerName);
    }

    /**
     * Initialize the AI provider
     * 
     * @param string|null $providerName Provider name
     * @return bool Success status
     */
    private function initializeProvider(?string $providerName = null): bool {
        // Use specified provider or default
        $providerName = $providerName ?? $this->config->getDefaultProvider();

        // Initialize appropriate provider
        $this->provider = match($providerName) {
            'openai' => new OpenAIProvider(),
            // Add more providers here as they're implemented
            default => new OpenAIProvider() // Default to OpenAI for now
        };

        return $this->provider->isReady();
    }

    /**
     * Generate an image from a prompt
     * 
     * @param string $prompt Image description
     * @param array $options Generation options
     * @return AIResponse Response with image data
     */
    public function generateImage(string $prompt, array $options = []): AIResponse {
        try {
            // Validate provider
            if (!$this->provider || !$this->provider->isReady()) {
                return AIResponse::error('AI provider not initialized');
            }

            // Validate and sanitize options
            $options = $this->validateOptions($options);

            // Generate image
            $response = $this->provider->generate($prompt, $options);

            if (!$response->isSuccess()) {
                return $response;
            }

            // Process and store the generated image
            $processedResponse = $this->processGeneratedImage($response);

            // Track usage
            $this->trackUsage($response);

            return $processedResponse;

        } catch (\Exception $e) {
            error_log("Image generation error: " . $e->getMessage());
            return AIResponse::error('Failed to generate image: ' . $e->getMessage());
        }
    }

    /**
     * Validate and sanitize generation options
     * 
     * @param array $options Raw options
     * @return array Sanitized options
     */
    private function validateOptions(array $options): array {
        // Validate size
        if (isset($options['size']) && !in_array($options['size'], $this->supportedSizes)) {
            $options['size'] = '1024x1024'; // Default to 1024x1024 if invalid
        }

        // Validate quality
        if (isset($options['quality']) && !in_array($options['quality'], ['standard', 'hd'])) {
            $options['quality'] = 'standard';
        }

        // Validate variations count
        if (isset($options['variations'])) {
            $options['variations'] = min(max(1, (int)$options['variations']), 4);
        }

        return $options;
    }

    /**
     * Process and store generated image
     * 
     * @param AIResponse $response Original response
     * @return AIResponse Processed response
     */
    private function processGeneratedImage(AIResponse $response): AIResponse {
        $data = $response->getData();
        
        if (!isset($data['url'])) {
            return AIResponse::error('Invalid image data received');
        }

        try {
            // Create upload directories if they don't exist
            $uploadDir = '../../uploads/ai-generated/';
            $optimizedDir = '../../uploads/ai-generated/optimized/';
            
            foreach ([$uploadDir, $optimizedDir] as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // Download the generated image
            $imageContent = file_get_contents($data['url']);
            if ($imageContent === false) {
                return AIResponse::error('Failed to download generated image');
            }

            // Generate unique filename
            $filename = uniqid('ai_', true) . '.png';
            $filepath = $uploadDir . $filename;

            // Save original image
            if (!file_put_contents($filepath, $imageContent)) {
                return AIResponse::error('Failed to save generated image');
            }

            // Optimize image if enabled
            if ($this->config->get('general.optimize_images', true)) {
                $optimizedPath = $this->optimizeImage($filepath, $optimizedDir);
                if ($optimizedPath) {
                    $data['optimized_url'] = $this->getPublicUrl($optimizedPath);
                }
            }

            // Update response with local URL
            $data['url'] = $this->getPublicUrl($filepath);
            
            // Store image metadata in database
            $this->storeImageMetadata($data, $response->getMetadata());

            return AIResponse::success($data, $response->getMetadata());

        } catch (\Exception $e) {
            error_log("Image processing error: " . $e->getMessage());
            return AIResponse::error('Failed to process generated image: ' . $e->getMessage());
        }
    }

    /**
     * Optimize image and create variants
     * 
     * @param string $filepath Original image path
     * @param string $outputDir Output directory
     * @return string|null Path to optimized image
     */
    private function optimizeImage(string $filepath, string $outputDir): ?string {
        try {
            if (!extension_loaded('gd')) {
                throw new \Exception('GD library not available');
            }

            // Load image
            $image = imagecreatefrompng($filepath);
            if (!$image) {
                throw new \Exception('Failed to load image');
            }

            // Get original dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Create optimized version
            $optimizedPath = $outputDir . pathinfo($filepath, PATHINFO_FILENAME) . '_optimized.webp';
            
            // Convert to WebP with 80% quality
            imagewebp($image, $optimizedPath, 80);
            
            imagedestroy($image);
            
            return $optimizedPath;

        } catch (\Exception $e) {
            error_log("Image optimization error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get public URL for a file path
     * 
     * @param string $filepath Server file path
     * @return string Public URL
     */
    private function getPublicUrl(string $filepath): string {
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filepath);
        return 'https://' . $_SERVER['HTTP_HOST'] . $relativePath;
    }

    /**
     * Store image metadata in database
     * 
     * @param array $imageData Image data
     * @param array $metadata Generation metadata
     */
    private function storeImageMetadata(array $imageData, array $metadata): void {
        try {
            $db = new \PDO(
                "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
                "stories_user",
                '$tw1cac3+sOt'
            );

            $stmt = $db->prepare("
                INSERT INTO ai_generations 
                (provider_id, type, prompt, result_url, metadata, status) 
                VALUES 
                ((SELECT id FROM ai_providers WHERE name = ?), ?, ?, ?, ?, 'completed')
            ");

            $stmt->execute([
                $metadata['provider'],
                'image',
                $metadata['prompt'] ?? '',
                $imageData['url'],
                json_encode($metadata)
            ]);

        } catch (\PDOException $e) {
            error_log("Failed to store image metadata: " . $e->getMessage());
        }
    }

    /**
     * Track provider usage
     * 
     * @param AIResponse $response Generation response
     */
    private function trackUsage(AIResponse $response): void {
        try {
            $metadata = $response->getMetadata();
            
            if (!isset($metadata['provider']) || !isset($metadata['cost'])) {
                return;
            }

            $db = new \PDO(
                "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
                "stories_user",
                '$tw1cac3+sOt'
            );

            $stmt = $db->prepare("
                INSERT INTO ai_usage 
                (provider_id, type, cost) 
                VALUES 
                ((SELECT id FROM ai_providers WHERE name = ?), ?, ?)
            ");

            $stmt->execute([
                $metadata['provider'],
                'image',
                $metadata['cost']
            ]);

        } catch (\PDOException $e) {
            error_log("Failed to track usage: " . $e->getMessage());
        }
    }
}