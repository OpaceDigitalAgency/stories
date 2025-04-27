# Stories From The Web - Planning Document

## Goals
- Create a platform for sharing and discovering children's stories
- Allow filtering of stories by different categories (AI-Enhanced, Self-Published, Sponsored)
- Implement proper admin functionality for managing stories

## Architecture Decisions
- Frontend: Astro.js with server-side rendering
- Backend: PHP with MySQL database
- API: RESTful API for communication between frontend and backend

## Constraints
- Ensure proper filtering of stories by category
- Fix form submission issues in the admin panel
- Ensure all boolean fields are properly saved

## Known Issues and Solutions

### Issue 1: Same stories showing in all sections
**Problem**: All sections (AI-Enhanced, Self-Published, Sponsored) were showing the same stories.
**Solution**: Modified the API calls in `index.astro` to properly filter stories by type using query parameters.

### Issue 2: Form saving issues
**Problem**: Certain fields like "Is Self Published" and author selection weren't saving properly.
**Solution**:
- Added explicit handling for all boolean fields in `save-story.php`
- Added debug logging for author_id to track any issues
- Ensured all form fields are properly processed

### Issue 3: Author selection not displaying correctly
**Problem**: Authors were being saved but not displayed in the stories list or when editing a story.
**Solution**:
- Modified the SQL query in `stories.php` to properly join with the authors table
- Updated the story form to display the current author when editing
- Added verification in `save-story.php` to ensure the selected author exists
- Added debug logging to track author information throughout the process

### Issue 4: "Much Loved" section criteria
**Problem**: Unclear what determines stories in the "Much Loved" section.
**Solution**: Modified the API call to sort by `average_rating` in descending order to show highest-rated stories first.

### Issue 5: Admin interface design and usability
**Problem**: The admin interface lacked a modern design and consistent user experience.
**Solution**:
- Created a modern CSS file (modern-admin.css) with a clean, professional design
- Implemented a consistent header and navigation across all admin pages
- Added view functionality for all content types
- Ensured all content types have consistent actions (view, edit, delete)
- Maintained JavaScript-free architecture as required