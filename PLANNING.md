# Data Enrichment System - Planning

## Goals
Transform the data enrichment system from basic PoC to fully working implementation with proper relationship handling, automatic source selection, and cover image downloading.

## Architecture Decisions
- Implement proper authors table relationships for publishers and book authors
- Use directory_item_tags junction table for tags/genres instead of comma-separated strings
- Add automatic highest-score source selection for "Select All" and "Fix All" operations
- Implement cover image download with progress indicator using existing media optimization components
- Add proper age_ranges table lookup instead of hardcoded strings

## Constraints
- Must maintain backward compatibility with existing book data
- Must handle multiple API sources (Google Books, OpenLibrary) with confidence scoring
- Must implement transactional database operations to prevent data corruption
- Must use existing media upload/optimization infrastructure

## Upcoming
- Examine current data enrichment system files
- Analyze database schema and relationship tables
- Fix automatic source selection logic
- Implement proper relationship table updates
- Add cover image download with progress component
- Validate enrichment data accuracy