<?php

namespace Stories\Lib\AI\Core;

/**
 * Class AIResponse
 * 
 * Standardized response format for AI operations
 */
class AIResponse {
    private bool $success;
    private mixed $data;
    private ?string $error;
    private array $metadata;

    /**
     * Constructor
     * 
     * @param bool $success Operation success status
     * @param mixed $data Response data (optional)
     * @param string|null $error Error message if failed (optional)
     * @param array $metadata Additional metadata (optional)
     */
    public function __construct(
        bool $success,
        mixed $data = null,
        ?string $error = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->metadata = array_merge([
            'timestamp' => date('c'),
            'provider' => null,
            'cost' => null
        ], $metadata);
    }

    /**
     * Create a successful response
     * 
     * @param mixed $data Response data
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function success(mixed $data, array $metadata = []): self {
        return new self(true, $data, null, $metadata);
    }

    /**
     * Create an error response
     * 
     * @param string $error Error message
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function error(string $error, array $metadata = []): self {
        return new self(false, null, $error, $metadata);
    }

    /**
     * Check if the operation was successful
     * 
     * @return bool Success status
     */
    public function isSuccess(): bool {
        return $this->success;
    }

    /**
     * Get the response data
     * 
     * @return mixed Response data
     */
    public function getData(): mixed {
        return $this->data;
    }

    /**
     * Get the error message
     * 
     * @return string|null Error message
     */
    public function getError(): ?string {
        return $this->error;
    }

    /**
     * Get response metadata
     * 
     * @return array Metadata
     */
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * Convert response to array
     * 
     * @return array Response data
     */
    public function toArray(): array {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Convert response to JSON
     * 
     * @return string JSON string
     */
    public function toJson(): string {
        return json_encode($this->toArray());
    }
}