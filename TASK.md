# Data Enrichment System - Tasks

## Active Tasks

### 🔄 Transform Data Enrichment from PoC to Production
**Priority:** High  
**Status:** In Progress  
**Assigned:** Code Mode  

#### Sub-tasks:
1. ⏳ Examine current data enrichment system architecture
2. ⏳ Fix automatic source selection logic (Select All + Fix All)
3. ⏳ Implement proper authors table relationships for publishers
4. ⏳ Add directory_item_tags junction table handling for tags/genres
5. ⏳ Fix age_ranges table lookup instead of hardcoded strings
6. ⏳ Implement cover image download with progress component
7. ⏳ Validate and fix enrichment data accuracy issues

## Backlog

### Critical Issues Identified:
- Select All doesn't auto-select highest confidence sources
- Fix All doesn't apply automatically without user intervention
- Publisher relationships bypass authors table structure
- Tags/genres don't show on modal (not using junction table)
- Age ranges use hardcoded strings instead of database lookup
- Cover URLs are external links only (no download/upload)
- Enrichment data shows suspicious values (multiple ISBNs, conflicting data)

### Technical Debt:
- Data enrichment is basic PoC level
- Missing transactional database operations
- No proper error handling for API failures
- No validation of enriched data quality