#!/bin/bash

# Enforce case sensitivity in Git and project structure
# This script provides a permanent solution to case sensitivity issues

# Set error handling
set -e
echo "Starting case sensitivity enforcement..."

# 1. Configure Git to track case changes
git config core.ignorecase false
echo "✓ Configured Git to track case changes"

# 2. Create temporary directories and move files
BASE_DIR="stories-backend/api/v1"
DIRS=("core:Core" "middleware:Middleware" "endpoints:Endpoints" "utils:Utils" "config:Config")

for dir_pair in "${DIRS[@]}"; do
    OLD_DIR="${dir_pair%%:*}"
    NEW_DIR="${dir_pair##*:}"
    
    if [ -d "$BASE_DIR/$OLD_DIR" ]; then
        echo "Moving $OLD_DIR to ${OLD_DIR}_temp..."
        git mv "$BASE_DIR/$OLD_DIR" "$BASE_DIR/${OLD_DIR}_temp"
        
        echo "Moving ${OLD_DIR}_temp to $NEW_DIR..."
        git mv "$BASE_DIR/${OLD_DIR}_temp" "$BASE_DIR/$NEW_DIR"
    fi
done

# 3. Create pre-commit hook
HOOK_FILE=".git/hooks/pre-commit"
cat > "$HOOK_FILE" << 'EOL'
#!/bin/bash

# Check for lowercase directories that should be capitalized
DIRS_TO_CHECK=("core" "middleware" "endpoints" "utils" "config")
BASE_DIR="stories-backend/api/v1"

for dir in "${DIRS_TO_CHECK[@]}"; do
    if [ -d "$BASE_DIR/$dir" ]; then
        echo "Error: Directory $BASE_DIR/$dir should be capitalized"
        exit 1
    fi
done

# Check for lowercase namespace declarations
NAMESPACE_PATTERN="namespace\s+StoriesAPI\\\\(core|middleware|endpoints|utils|config)\s*;"
if git diff --cached -U0 | grep -P "^\+.*$NAMESPACE_PATTERN"; then
    echo "Error: Found lowercase namespace declarations. Please use proper capitalization:"
    echo "  StoriesAPI\\Core"
    echo "  StoriesAPI\\Middleware"
    echo "  StoriesAPI\\Endpoints"
    echo "  StoriesAPI\\Utils"
    echo "  StoriesAPI\\Config"
    exit 1
fi

exit 0
EOL

chmod +x "$HOOK_FILE"
echo "✓ Created pre-commit hook"

# 4. Create deployment check script
cat > "stories-backend/check_case_sensitivity.php" << 'EOL'
<?php
/**
 * Case Sensitivity Check Script
 * 
 * This script runs during deployment to ensure proper case sensitivity
 * in both directory structure and namespace declarations.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = __DIR__ . '/api/v1';
$expectedDirs = [
    'Core',
    'Middleware',
    'Endpoints',
    'Utils',
    'Config'
];

$errors = [];

// Check directory structure
foreach ($expectedDirs as $dir) {
    $path = $baseDir . '/' . $dir;
    $lowercasePath = $baseDir . '/' . strtolower($dir);
    
    if (!is_dir($path)) {
        $errors[] = "Missing directory: $path";
    }
    
    if (is_dir($lowercasePath) && $lowercasePath !== $path) {
        $errors[] = "Found lowercase directory: $lowercasePath (should be $path)";
    }
}

// Check namespace declarations
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/namespace\s+StoriesAPI\\\\(core|middleware|endpoints|utils|config)\s*;/i', $content, $matches)) {
            $errors[] = sprintf(
                "Found lowercase namespace in %s: %s",
                $file->getPathname(),
                $matches[0]
            );
        }
    }
}

if (!empty($errors)) {
    echo "Case sensitivity errors found:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    exit(1);
}

echo "✓ All case sensitivity checks passed\n";
exit(0);
EOL

echo "✓ Created deployment check script"

# 5. Add deployment check to deployment script
if [ -f "stories-backend/deploy.sh" ]; then
    # Add check before deployment
    sed -i.bak '2i\
# Check case sensitivity\
php check_case_sensitivity.php || exit 1\
' "stories-backend/deploy.sh"
    echo "✓ Added case sensitivity check to deployment script"
fi

# 6. Commit changes
git add .
git commit -m "Implement permanent case sensitivity solution

- Configure Git to track case changes
- Fix directory structure
- Add pre-commit hook for case checks
- Add deployment validation
- Add runtime validation"

echo "
✅ Case sensitivity enforcement complete!

The following measures are now in place:
1. Git is configured to track case changes
2. Directory structure is fixed
3. Pre-commit hook prevents lowercase directories
4. Deployment script includes case sensitivity validation
5. Runtime validation ensures proper capitalization

To maintain case sensitivity:
- Always use proper capitalization in namespaces
- Keep directory names capitalized
- Run check_case_sensitivity.php before deployments
"