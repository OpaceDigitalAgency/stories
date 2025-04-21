# Case Sensitivity Solution

This document explains the permanent solution implemented to handle case sensitivity issues in the Stories API project.

## The Problem

Git and some file systems (like macOS) are case-insensitive by default, which can lead to:
- Directories reverting to lowercase
- Namespace inconsistencies
- Deployment issues
- Runtime errors

## The Solution

We've implemented a multi-layered approach to enforce case sensitivity:

### 1. Git Configuration
- Configured Git to track case changes with `core.ignorecase false`
- Added pre-commit hook to prevent lowercase directories and namespaces

### 2. Directory Structure
Standard directory structure with proper capitalization:
```
stories-backend/api/v1/
├── Core/
├── Middleware/
├── Endpoints/
├── Utils/
└── Config/
```

### 3. Namespace Standards
All namespaces must use proper capitalization:
```php
namespace StoriesAPI\Core;
namespace StoriesAPI\Middleware;
namespace StoriesAPI\Endpoints;
namespace StoriesAPI\Utils;
namespace StoriesAPI\Config;
```

### 4. Deployment Validation
- Added `check_case_sensitivity.php` to validate before deployment
- Checks both directory structure and namespace declarations
- Prevents deployment if case sensitivity issues are found

### 5. Runtime Validation
- Strict PSR-4 autoloader enforces case sensitivity
- No case-insensitive fallbacks allowed
- Clear error messages for case mismatches

## Implementation

1. Run the enforcement script:
```bash
chmod +x stories-backend/enforce_case_sensitivity.sh
./stories-backend/enforce_case_sensitivity.sh
```

2. The script will:
   - Configure Git
   - Fix directory structure
   - Install pre-commit hook
   - Add deployment checks
   - Commit all changes

## Maintaining Case Sensitivity

### For Developers
1. Always use proper capitalization in:
   - Directory names
   - Namespace declarations
   - Class references

2. The pre-commit hook will prevent:
   - Lowercase directories
   - Lowercase namespace declarations

### For Deployment
1. The deployment process now includes case sensitivity validation
2. Run `php check_case_sensitivity.php` manually to check for issues
3. Fix any reported problems before deploying

### Common Issues and Solutions

1. **Directory Case Mismatch**
   ```bash
   Error: Directory api/v1/core should be capitalized
   Solution: Use git mv to rename correctly:
   git mv api/v1/core api/v1/core_temp
   git mv api/v1/core_temp api/v1/Core
   ```

2. **Namespace Case Mismatch**
   ```php
   // Wrong
   namespace StoriesAPI\core;
   
   // Correct
   namespace StoriesAPI\Core;
   ```

3. **Class Import Case Mismatch**
   ```php
   // Wrong
   use StoriesAPI\core\BaseController;
   
   // Correct
   use StoriesAPI\Core\BaseController;
   ```

## Benefits

1. **Consistency**
   - Predictable directory structure
   - Consistent namespace usage
   - Clear standards for the team

2. **Reliability**
   - Prevents case-related bugs
   - Ensures proper autoloading
   - Maintains PSR-4 compliance

3. **Maintainability**
   - Easy to spot issues early
   - Automated validation
   - Clear error messages

## Troubleshooting

If you encounter case sensitivity issues:

1. Run the check script:
   ```bash
   php stories-backend/check_case_sensitivity.php
   ```

2. Fix any reported issues:
   - Use `git mv` for directory renames
   - Update namespace declarations
   - Update class references

3. Commit changes:
   ```bash
   git add .
   git commit -m "Fix case sensitivity issues"
   ```

4. Verify fixes:
   ```bash
   php stories-backend/check_case_sensitivity.php
   ```

Remember: Prevention is better than cure. The pre-commit hook and deployment checks will help catch issues early.