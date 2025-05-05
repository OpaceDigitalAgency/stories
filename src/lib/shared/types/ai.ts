/**
 * AI Provider Types and Interfaces
 */

export type AIProviderType = 'image' | 'text' | 'audio' | 'video';

export interface AIProvider {
    name: string;
    type: AIProviderType;
    capabilities: string[];
    config: Record<string, any>;
}

export interface AIMetadata {
    provider: string;
    timestamp: string;
    cost?: number;
}

export interface AIResponse<T = any> {
    success: boolean;
    data?: T;
    error?: string;
    metadata: AIMetadata;
}

/**
 * Image Generation Types
 */

export type ImageSize = '256x256' | '512x512' | '1024x1024' | '1024x1792' | '1792x1024';
export type ImageStyle = 'natural' | 'vivid' | 'artistic' | 'professional';
export type ImageFormat = 'png' | 'jpg' | 'webp';

export interface ImageGenerationOptions {
    size?: ImageSize;
    style?: ImageStyle;
    format?: ImageFormat;
    variations?: number;
    quality?: 'standard' | 'hd';
}

export interface ImageGenerationRequest {
    prompt: string;
    options?: ImageGenerationOptions;
}

export interface ImageGenerationResult {
    url: string;
    size: ImageSize;
    format: ImageFormat;
    variations?: string[];
}

/**
 * Text Generation Types
 */

export interface TextGenerationOptions {
    maxTokens?: number;
    temperature?: number;
    topP?: number;
    frequencyPenalty?: number;
    presencePenalty?: number;
    stop?: string[];
}

export interface TextGenerationRequest {
    prompt: string;
    options?: TextGenerationOptions;
}

export interface TextGenerationResult {
    text: string;
    tokens: number;
}

/**
 * API Response Types
 */

export interface ImageAPIResponse extends AIResponse<ImageGenerationResult> {}
export interface TextAPIResponse extends AIResponse<TextGenerationResult> {}

/**
 * Configuration Types
 */

export interface ProviderConfig {
    apiKey: string;
    organization?: string;
    model?: string;
    maxTokens?: number;
    temperature?: number;
}

export interface GeneralConfig {
    defaultProvider: string;
    cacheEnabled: boolean;
    cacheTTL: number;
    rateLimit: number;
    costLimit: number;
}

export interface AIConfig {
    openai?: ProviderConfig;
    stableDiffusion?: ProviderConfig;
    general: GeneralConfig;
}

/**
 * Component Props Types
 */

export interface ImageGeneratorProps {
    prompt?: string;
    size?: ImageSize;
    style?: ImageStyle;
    onGenerate?: (result: ImageGenerationResult) => void;
    onError?: (error: string) => void;
}

export interface TextGeneratorProps {
    prompt?: string;
    maxTokens?: number;
    temperature?: number;
    onGenerate?: (result: TextGenerationResult) => void;
    onError?: (error: string) => void;
}