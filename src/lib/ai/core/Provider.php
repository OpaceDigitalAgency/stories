<?php

namespace Stories\Lib\AI\Core;

/**
 * Interface AIProvider
 * 
 * Base interface for all AI providers (OpenAI, Stable Diffusion, etc.)
 */
interface AIProvider {
    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Get the provider type (image, text, etc.)
     */
    public function getType(): string;

    /**
     * Get provider capabilities
     * 
     * @return array List of supported capabilities
     */
    public function getCapabilities(): array;

    /**
     * Get provider configuration
     * 
     * @return array Configuration key-value pairs
     */
    public function getConfig(): array;

    /**
     * Initialize the provider with configuration
     * 
     * @param array $config Provider-specific configuration
     * @return bool Success status
     */
    public function initialize(array $config): bool;

    /**
     * Check if provider is properly configured and ready
     * 
     * @return bool Ready status
     */
    public function isReady(): bool;

    /**
     * Generate AI content based on prompt
     * 
     * @param string $prompt The input prompt
     * @param array $options Additional generation options
     * @return AIResponse The generation response
     */
    public function generate(string $prompt, array $options = []): AIResponse;

    /**
     * Get provider usage/cost information
     * 
     * @return array Usage statistics
     */
    public function getUsage(): array;
}