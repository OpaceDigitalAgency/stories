# Subscribers System Documentation

This document provides comprehensive documentation for the Subscribers system in the Stories from the Web platform.

## Overview

The Subscribers system allows users to sign up for notifications about upcoming premium features. It includes:

1. A database table to store subscriber information
2. An API endpoint for submitting subscription requests
3. An admin interface for managing subscribers

## Database Schema

The `subscribers` table stores information about users who have subscribed to be notified about upcoming premium features:

```sql
CREATE TABLE `subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `name` VARCHAR(255),
  `feature` VARCHAR(100) NOT NULL,
  `message` TEXT,
  `is_contacted` TINYINT(1) DEFAULT 0,
  `admin_notes` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT | Unique identifier |
| `email` | VARCHAR(255) | Subscriber's email address (unique) |
| `name` | VARCHAR(255) | Subscriber's name (optional) |
| `feature` | VARCHAR(100) | Feature they're interested in (e.g., 'premium') |
| `message` | TEXT | Optional message from subscriber |
| `is_contacted` | TINYINT(1) | Whether the subscriber has been contacted |
| `admin_notes` | TEXT | Admin notes about the subscriber |
| `created_at` | DATETIME | Subscription timestamp |
| `updated_at` | DATETIME | Update timestamp |

## API Endpoint

### Subscribe to Feature Notification

```
POST /api/v1/subscribers.php
```

#### Request Body

```json
{
  "email": "user@example.com",
  "name": "John Doe",
  "feature": "premium",
  "message": "I'm excited about this feature!"
}
```

#### Response

```json
{
  "success": true,
  "message": "Thank you for subscribing! We'll notify you when this feature is available."
}
```

If the email already exists:

```json
{
  "success": true,
  "message": "Your subscription has been updated. We'll notify you when this feature is available.",
  "updated": true
}
```

### Get Subscribers (Admin Only)

```
GET /api/v1/subscribers.php?admin_token=stories_admin_token
```

#### Query Parameters
- `feature`: Filter by feature name
- `admin_token`: Required for authentication

#### Response

```json
{
  "success": true,
  "subscribers": [
    {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "feature": "premium",
      "message": "I'm excited about this feature!",
      "is_contacted": 0,
      "admin_notes": null,
      "created_at": "2025-04-26 09:17:50",
      "updated_at": "2025-04-26 09:17:50"
    }
  ]
}
```

## Admin Interface

The admin interface for managing subscribers is located at:

```
/admin/content/subscribers.php
```

### Features

- View a list of all subscribers
- Filter subscribers by feature
- Filter subscribers by contact status
- Update subscriber contact status
- Add admin notes to subscribers
- View subscriber details in a modal

### Implementation Details

The admin interface is implemented as a standalone PHP file in the `stories-backend/admin/content/` directory. It uses Bootstrap for styling and includes the following components:

- Filter form for searching and filtering subscribers
- Table for displaying subscriber information
- Modal for viewing and updating subscriber details
- Form for updating subscriber contact status and admin notes

## Frontend Integration

The subscription form is integrated into the Premium page and other "Coming Soon" pages using the `ComingSoonPage.astro` component:

```typescript
// src/components/ComingSoonPage.astro
<form id="notifyForm" class="flex flex-col sm:flex-row gap-3">
  <input
    type="email"
    name="email"
    id="subscriber-email"
    placeholder="Your email address"
    class="flex-grow px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
    required
  />
  <input type="hidden" name="feature" value={featureName.toLowerCase()} />
  <button
    type="submit"
    id="notify-submit-btn"
    class="bg-primary text-white px-6 py-3 rounded-lg font-bold hover:bg-primary-600 transition-colors duration-300 whitespace-nowrap"
  >
    Notify Me
  </button>
</form>
```

The form submission is handled by JavaScript that sends a POST request to the API endpoint:

```typescript
form.addEventListener('submit', async function(e) {
  e.preventDefault();

  // Show loading state
  submitBtn.disabled = true;
  submitBtn.innerHTML = 'Submitting...';

  // Get form data
  const formData = new FormData(form);
  const formObject: Record<string, string> = {};
  formData.forEach((value, key) => {
    if (typeof value === 'string') {
      formObject[key] = value;
    }
  });

  try {
    // Submit to API
    const response = await fetch('https://api.storiesfromtheweb.org/api/v1/subscribers.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(formObject)
    });

    const data = await response.json();

    // Reset form
    form.reset();

    // Show success message
    resultDiv.classList.remove('hidden');
    successMsg.classList.remove('hidden');
    errorMsg.classList.add('hidden');
    successMsg.textContent = data.message || 'Thank you for subscribing! We\'ll notify you when this feature is available.';

  } catch (error) {
    // Show error message
    resultDiv.classList.remove('hidden');
    errorMsg.classList.remove('hidden');
    successMsg.classList.add('hidden');
    errorMsg.textContent = 'Sorry, there was an error submitting your request. Please try again.';
    console.error('Subscription error:', error);
  } finally {
    // Reset button state
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Notify Me';
  }
});
```

## Security Considerations

- Email addresses are stored in the database and should be treated as sensitive information
- The admin endpoint requires authentication to prevent unauthorized access to subscriber data
- Input validation is performed on both the client and server side to prevent injection attacks
- CORS headers are set to allow requests from the frontend domain only

## Future Improvements

- Add email verification to confirm subscriber email addresses
- Implement a bulk email system for notifying subscribers when features are available
- Add analytics to track subscription rates and feature interest
- Enhance the admin interface with more filtering and sorting options
- Add the ability to export subscriber data to CSV for marketing purposes
