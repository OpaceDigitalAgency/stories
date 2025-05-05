<?php

namespace Stories\Lib\AI\Core;

/**
 * Class Config
 * 
 * Manages configuration for AI providers and services
 */
class Config {
    private static ?Config $instance = null;
    private array $config = [];
    private array $providers = [];
    private string $defaultProvider = '';

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->loadConfig();
    }

    /**
     * Get singleton instance
     * 
     * @return self Config instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load configuration from environment and database
     */
    private function loadConfig(): void {
        // Load environment variables
        $this->config = [
            'openai' => [
                'api_key' => getenv('OPENAI_API_KEY'),
                'organization' => getenv('OPENAI_ORGANIZATION'),
                'model' => getenv('OPENAI_MODEL') ?? 'dall-e-3',
                'max_tokens' => (int)(getenv('OPENAI_MAX_TOKENS') ?? 2000),
                'temperature' => (float)(getenv('OPENAI_TEMPERATURE') ?? 0.7)
            ],
            'stable_diffusion' => [
                'api_key' => getenv('STABLE_DIFFUSION_API_KEY'),
                'api_host' => getenv('STABLE_DIFFUSION_API_HOST'),
                'model' => getenv('STABLE_DIFFUSION_MODEL') ?? 'stable-diffusion-xl-1024-v1-0'
            ],
            'general' => [
                'default_provider' => getenv('AI_DEFAULT_PROVIDER') ?? 'openai',
                'cache_enabled' => (bool)(getenv('AI_CACHE_ENABLED') ?? true),
                'cache_ttl' => (int)(getenv('AI_CACHE_TTL') ?? 3600),
                'rate_limit' => (int)(getenv('AI_RATE_LIMIT') ?? 60),
                'cost_limit' => (float)(getenv('AI_COST_LIMIT') ?? 50.0)
            ]
        ];

        // Set default provider
        $this->defaultProvider = $this->config['general']['default_provider'];

        // Load active providers from database
        try {
            $db = new \PDO(
                "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
                "stories_user",
                '$tw1cac3+sOt'
            );
            
            $stmt = $db->query("SELECT * FROM ai_providers WHERE is_active = 1");
            $this->providers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Failed to load AI providers from database: " . $e->getMessage());
            $this->providers = [];
        }
    }

    /**
     * Get provider configuration
     * 
     * @param string $provider Provider name
     * @return array Provider config
     */
    public function getProviderConfig(string $provider): array {
        return $this->config[$provider] ?? [];
    }

    /**
     * Get general configuration
     * 
     * @return array General config
     */
    public function getGeneralConfig(): array {
        return $this->config['general'];
    }

    /**
     * Get active providers
     * 
     * @return array Active providers
     */
    public function getActiveProviders(): array {
        return $this->providers;
    }

    /**
     * Get default provider
     * 
     * @return string Default provider name
     */
    public function getDefaultProvider(): string {
        return $this->defaultProvider;
    }

    /**
     * Set configuration value
     * 
     * @param string $key Config key
     * @param mixed $value Config value
     */
    public function set(string $key, mixed $value): void {
        $parts = explode('.', $key);
        $current = &$this->config;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        
        $current = $value;
    }

    /**
     * Get configuration value
     * 
     * @param string $key Config key
     * @param mixed $default Default value
     * @return mixed Config value
     */
    public function get(string $key, mixed $default = null): mixed {
        $parts = explode('.', $key);
        $current = $this->config;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return $default;
            }
            $current = $current[$part];
        }
        
        return $current;
    }

    /**
     * Check if configuration exists
     * 
     * @param string $key Config key
     * @return bool Exists status
     */
    public function has(string $key): bool {
        return $this->get($key) !== null;
    }
}