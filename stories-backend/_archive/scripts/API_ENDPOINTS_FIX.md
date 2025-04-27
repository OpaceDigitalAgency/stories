# API Endpoints Fix

This document describes the changes made to fix the directory-items and ai-tools API endpoints to match the frontend expectations.

## Problem

The frontend components were expecting a flat structure for the API responses, but the API was returning a nested Strapi-like structure with data/attributes. This mismatch was causing the directory and AI games sections to show "Error loading AI tools data. Please try again." instead of the sample data.

Additionally, there might be missing columns in the database tables that are required for the expected response format.

## Solution

The API endpoints have been updated to return a flat structure without the nested data/attributes format, similar to how the stories endpoint was fixed previously. The changes include:

1. For directory-items endpoint:
   - Return a flat structure without the data/attributes nesting
   - Map the database columns correctly (title → name, website_url → url, cover_url → direct URL for logo)

2. For ai-tools endpoint:
   - Return a flat structure without the data/attributes nesting
   - Map the database columns correctly (title → name, website_url → url, cover_url → direct URL for logo)

3. Database table check and update:
   - Added a script to check if the required columns exist in the tables
   - The script will add any missing columns without dropping or recreating the tables

## Files Changed

- `stories-backend/api/v1/api.php`: Updated the directory-items and ai-tools endpoints to return a flat structure
- `stories-backend/check_and_update_tables.php`: Added script to check and update database tables
- `stories-backend/tests/DirectoryAndAiToolsEndpointTest.php`: Added tests to verify the endpoints are working correctly
- `stories-backend/docs/api-spec/v1/directory-items.example.json`: Added example response for directory-items endpoint
- `stories-backend/docs/api-spec/v1/ai-tools.example.json`: Added example response for ai-tools endpoint
- `stories-backend/docs/api-spec/v1/openapi.yaml`: Added OpenAPI specification for both endpoints
- `stories-backend/run_api_tests.php`: Added script to run the tests

## Database Update

Before testing the API endpoints, you should run the database check and update script to ensure all required columns exist:

```bash
php stories-backend/check_and_update_tables.php
```

This script will:
1. Check if the directory_items and ai_tools tables exist
2. Check if all required columns exist in each table
3. Add any missing columns without dropping or recreating the tables

The required columns for each table are:

### directory_items
- id
- title
- description
- slug
- website_url
- category
- rating
- price_range
- cover_url
- is_published
- created_at
- updated_at

### ai_tools
- id
- title
- description
- slug
- website_url
- category
- pricing_type
- price_info
- features
- rating
- featured
- cover_url
- is_published
- created_at
- updated_at

## Testing

After updating the database, test the API endpoints by running:

```bash
php stories-backend/run_api_tests.php
```

This will start a local PHP server, run the tests, and then stop the server.

## Next Steps

To apply the same fix to other endpoints (e.g., authors, blog-posts, games), follow these steps:

1. Identify the expected response format by examining the frontend components that use the endpoint
2. Update the endpoint in `stories-backend/api/v1/api.php` to return a flat structure without the data/attributes nesting
3. Map the database columns correctly to match the frontend expectations
4. Create tests to verify the endpoint is working correctly
5. Document the expected response format in the API specification

For example, to fix the authors endpoint:

1. Examine how the frontend uses the authors endpoint (e.g., in `src/pages/authors/index.astro`)
2. Update the authors endpoint in `stories-backend/api/v1/api.php` to return a flat structure
3. Map the database columns correctly (e.g., name, slug, bio, avatar)
4. Create tests in `stories-backend/tests/AuthorsEndpointTest.php`
5. Document the expected response format in `stories-backend/docs/api-spec/v1/authors.example.json`