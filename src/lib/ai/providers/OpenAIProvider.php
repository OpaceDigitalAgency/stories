<?php

namespace Stories\Lib\AI\Providers;

use Stories\Lib\AI\Core\AIProvider;
use Stories\Lib\AI\Core\AIResponse;
use Stories\Lib\AI\Core\Config;

/**
 * OpenAI Provider Implementation
 * 
 * Handles integration with OpenAI's APIs (DALL-E, GPT, etc.)
 */
class OpenAIProvider implements AIProvider {
    private string $apiKey;
    private ?string $organization;
    private string $model;
    private array $config;
    private bool $isInitialized = false;

    /**
     * Constructor
     */
    public function __construct() {
        $config = Config::getInstance();
        $this->initialize($config->getProviderConfig('openai'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string {
        return 'image';
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array {
        return [
            'image_generation',
            'image_variation',
            'image_edit'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $config): bool {
        $this->apiKey = $config['api_key'] ?? '';
        $this->organization = $config['organization'] ?? null;
        $this->model = $config['model'] ?? 'dall-e-3';
        $this->config = $config;

        $this->isInitialized = !empty($this->apiKey);
        return $this->isInitialized;
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool {
        return $this->isInitialized;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): AIResponse {
        if (!$this->isReady()) {
            return AIResponse::error('Provider not initialized');
        }

        try {
            // Prepare request data
            $data = [
                'model' => 'gpt-image-1', // Use the model from feedback
                'prompt' => $prompt,
                'size' => $options['size'] ?? '1024x1024',
                'response_format' => 'b64_json' // Always use base64 format
            ];

            // Make API request
            $response = $this->makeRequest('https://api.openai.com/v1/images/generations', $data);

            if (!$response) {
                return AIResponse::error('Failed to generate image');
            }

            $result = json_decode($response, true);

            if (isset($result['error'])) {
                return AIResponse::error($result['error']['message']);
            }

            if (!isset($result['data'][0]['b64_json'])) {
                return AIResponse::error('No image data in response');
            }

            // Decode base64 image
            $imageData = base64_decode($result['data'][0]['b64_json']);
            if (!$imageData) {
                return AIResponse::error('Failed to decode image data');
            }

            // Generate unique filename
            $filename = uniqid('ai_', true) . '.png';
            $uploadDir = '../../uploads/ai-generated/';
            $filepath = $uploadDir . $filename;

            // Ensure upload directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Save image file
            if (!file_put_contents($filepath, $imageData)) {
                return AIResponse::error('Failed to save generated image');
            }

            // Create optimized version if enabled
            $optimizedUrl = null;
            if ($this->config['optimize_images'] ?? true) {
                $optimizedDir = $uploadDir . 'optimized/';
                if (!is_dir($optimizedDir)) {
                    mkdir($optimizedDir, 0755, true);
                }

                $optimizedPath = $this->optimizeImage($filepath, $optimizedDir);
                if ($optimizedPath) {
                    $optimizedUrl = $this->getPublicUrl($optimizedPath);
                }
            }

            // Calculate approximate cost
            $cost = $this->calculateCost($data['size'], $options['quality'] ?? 'standard', 1);

            // Prepare response data
            $responseData = [
                'url' => $this->getPublicUrl($filepath),
                'size' => $data['size'],
                'format' => 'png'
            ];

            if ($optimizedUrl) {
                $responseData['optimized_url'] = $optimizedUrl;
            }

            return AIResponse::success($responseData, [
                'provider' => $this->getName(),
                'cost' => $cost,
                'prompt' => $prompt
            ]);

        } catch (\Exception $e) {
            error_log("OpenAI generation error: " . $e->getMessage());
            return AIResponse::error($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUsage(): array {
        // Implementation for usage tracking
        return [
            'requests' => 0,
            'tokens' => 0,
            'cost' => 0
        ];
    }

    /**
     * Make HTTP request to OpenAI API
     * 
     * @param string $url API endpoint
     * @param array $data Request data
     * @return string|false Response body or false on failure
     */
    private function makeRequest(string $url, array $data): string|false {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        if ($this->organization) {
            $headers[] = 'OpenAI-Organization: ' . $this->organization;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30 // 30 second timeout
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("OpenAI API request error: " . $error);
            return false;
        }

        return $response;
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
     * Calculate approximate cost for image generation
     * 
     * @param string $size Image size
     * @param string $quality Image quality
     * @param int $count Number of images
     * @return float Approximate cost in USD
     */
    private function calculateCost(string $size, string $quality, int $count): float {
        $baseCost = match($size) {
            '1024x1024' => 0.040,
            '1024x1792', '1792x1024' => 0.080,
            default => 0.020
        };

        if ($quality === 'hd') {
            $baseCost *= 2;
        }

        return $baseCost * $count;
    }
}