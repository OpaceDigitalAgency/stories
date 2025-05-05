# AI Library

A reusable library for AI integrations, supporting both frontend (Astro) and backend (PHP) environments. Currently focused on image generation with OpenAI's DALL-E 3, with support for additional providers and capabilities planned.

## Features

- Image generation using OpenAI DALL-E 3
- Automatic image optimization and storage
- Usage tracking and rate limiting
- Frontend components for easy integration
- Provider abstraction for future extensibility
- Type-safe API interfaces

## Setup

### 1. Database Setup

Run the SQL script to create required tables:

```bash
mysql -u your_user -p your_database < stories-backend/docs/database/ai_tables.sql
```

### 2. Environment Configuration

Add the following environment variables:

```env
# OpenAI Configuration
OPENAI_API_KEY=your_api_key
OPENAI_ORGANIZATION=your_org_id  # Optional
OPENAI_MODEL=dall-e-3
OPENAI_MAX_TOKENS=2000
OPENAI_TEMPERATURE=0.7

# AI General Configuration
AI_DEFAULT_PROVIDER=openai
AI_CACHE_ENABLED=true
AI_CACHE_TTL=3600
AI_RATE_LIMIT=60
AI_COST_LIMIT=50.0
```

### 3. Directory Setup

Ensure these directories exist and are writable:

```bash
mkdir -p uploads/ai-generated/optimized
chmod -R 755 uploads
```

## Usage

### Backend (PHP)

```php
// Initialize image service
$imageService = new \Stories\Lib\AI\Services\ImageService();

// Generate image
$response = $imageService->generateImage(
    "A serene landscape with mountains and a lake",
    [
        'size' => '1024x1024',
        'style' => 'natural',
        'variations' => 1
    ]
);

if ($response->isSuccess()) {
    $imageUrl = $response->getData()['url'];
    // Handle successful generation
} else {
    $error = $response->getError();
    // Handle error
}
```

### Frontend (Astro)

```astro
---
import { ImageGenerator } from '../components/ai/ImageGenerator.astro';
---

<ImageGenerator
  prompt="A serene landscape"
  size="1024x1024"
  style="natural"
  onGenerate={(result) => {
    console.log('Generated image:', result);
  }}
  onError={(error) => {
    console.error('Generation failed:', error);
  }}
/>
```

## Architecture

### Directory Structure

```
src/lib/ai/
├── core/
│   ├── Provider.php      # Base provider interface
│   ├── Response.php      # Standardized response class
│   └── Config.php        # Configuration management
├── providers/
│   └── OpenAIProvider.php # OpenAI implementation
├── services/
│   └── ImageService.php   # Image generation service
└── utils/
    ├── Validator.php      # Input validation
    └── ErrorHandler.php   # Error handling
```

### Database Schema

The library uses several tables to manage AI operations:

- `ai_providers`: Registered AI providers and their configuration
- `ai_generations`: Record of all generation attempts
- `ai_usage`: Usage tracking and cost management
- `ai_rate_limit`: Rate limiting implementation

## Error Handling

The library uses a standardized error handling approach:

1. All operations return an `AIResponse` object
2. Errors are logged to the system error log
3. Rate limiting and cost control are enforced automatically

## Security

- API endpoints implement CORS and rate limiting
- Sensitive configuration is managed through environment variables
- Input validation is performed on all user inputs
- File operations use secure naming and paths

## Monitoring

Usage statistics are available through:

1. Admin interface at `/admin/content/ai-image-generator.php`
2. API endpoints:
   - `/api/ai/usage.php`: Current usage statistics
   - `/api/ai/history.php`: Generation history

## Future Extensions

The library is designed to be extensible for:

1. Additional AI providers
2. New content types (text, audio, video)
3. Advanced image manipulation
4. Frontend editing capabilities

## Contributing

1. Follow the existing code structure
2. Implement the appropriate interfaces
3. Add comprehensive error handling
4. Include unit tests
5. Update documentation

## License

MIT License - See LICENSE file for details