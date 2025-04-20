<?php
/**
 * Fix Controllers Use Statement
 * 
 * This script fixes the use statement in controllers to properly import the Controller class.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Controllers Use Statement</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';

// Find the Core directory
$coreUpperPath = $apiPath . '/Core';
$coreLowerPath = $apiPath . '/core';

if (is_dir($coreUpperPath)) {
    $corePath = $coreUpperPath;
    echo "<p>Using uppercase Core directory: $corePath</p>";
} elseif (is_dir($coreLowerPath)) {
    $corePath = $coreLowerPath;
    echo "<p>Using lowercase Core directory: $corePath</p>";
} else {
    echo "<p style='color:red'>❌ Core directory not found!</p>";
    exit;
}

// Find the endpoints directory
$endpointsUpperPath = $apiPath . '/Endpoints';
$endpointsLowerPath = $apiPath . '/endpoints';

if (is_dir($endpointsUpperPath)) {
    $endpointsPath = $endpointsUpperPath;
    echo "<p>Using uppercase Endpoints directory: $endpointsPath</p>";
} elseif (is_dir($endpointsLowerPath)) {
    $endpointsPath = $endpointsLowerPath;
    echo "<p>Using lowercase Endpoints directory: $endpointsPath</p>";
} else {
    echo "<p style='color:red'>❌ Endpoints directory not found!</p>";
    exit;
}

// Get the namespace suffixes
$coreNamespaceSuffix = basename($corePath);
$endpointsNamespaceSuffix = basename($endpointsPath);

// Get all controller files
$controllerFiles = glob($endpointsPath . '/*.php');

if (empty($controllerFiles)) {
    echo "<p style='color:red'>❌ No controller files found in $endpointsPath</p>";
    exit;
}

echo "<h2>Fixing Controller Files</h2>";

foreach ($controllerFiles as $controllerFile) {
    $fileName = basename($controllerFile);
    echo "<h3>Fixing $fileName</h3>";
    
    // Read the file content
    $content = file_get_contents($controllerFile);
    
    // Check if it extends Controller
    if (strpos($content, 'extends Controller') !== false) {
        // Check if it has the correct use statement
        $usePattern = "/use\s+StoriesAPI\\\\([^\\\\]+)\\\\Controller;/";
        if (preg_match($usePattern, $content, $matches)) {
            $useNamespace = $matches[1];
            if ($useNamespace !== $coreNamespaceSuffix) {
                echo "<p style='color:orange'>⚠️ $fileName is using incorrect namespace for Controller: $useNamespace. Expected: $coreNamespaceSuffix. Fixing...</p>";
                
                // Create a backup
                $backupFile = $controllerFile . '.bak.' . date('YmdHis');
                if (copy($controllerFile, $backupFile)) {
                    echo "<p>Created backup at: $backupFile</p>";
                }
                
                // Update the use statement
                $newContent = preg_replace($usePattern, "use StoriesAPI\\$coreNamespaceSuffix\\Controller;", $content);
                
                if (file_put_contents($controllerFile, $newContent)) {
                    echo "<p style='color:green'>✅ Updated use statement in $fileName.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update use statement in $fileName.</p>";
                }
            } else {
                echo "<p style='color:green'>✅ $fileName is using the correct namespace for Controller.</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ $fileName extends Controller but doesn't have a use statement for it. Adding...</p>";
            
            // Create a backup
            $backupFile = $controllerFile . '.bak.' . date('YmdHis');
            if (copy($controllerFile, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
            
            // Add the use statement after the namespace declaration
            $namespacePattern = "/namespace\s+([^;]+);/";
            if (preg_match($namespacePattern, $content, $matches)) {
                $namespace = $matches[1];
                $useStatement = "use StoriesAPI\\$coreNamespaceSuffix\\Controller;";
                $newContent = preg_replace($namespacePattern, "namespace $namespace;\n\n$useStatement", $content);
                
                if (file_put_contents($controllerFile, $newContent)) {
                    echo "<p style='color:green'>✅ Added use statement to $fileName.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to add use statement to $fileName.</p>";
                }
            } else {
                echo "<p style='color:red'>❌ Could not find namespace declaration in $fileName.</p>";
            }
        }
    } else {
        echo "<p style='color:orange'>⚠️ $fileName doesn't extend Controller. Checking if it should...</p>";
        
        // Check if it's a controller class
        if (strpos($fileName, 'Controller.php') !== false) {
            echo "<p style='color:orange'>⚠️ $fileName is a controller but doesn't extend Controller. Fixing...</p>";
            
            // Create a backup
            $backupFile = $controllerFile . '.bak.' . date('YmdHis');
            if (copy($controllerFile, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
            
            // Add the use statement and make it extend Controller
            $namespacePattern = "/namespace\s+([^;]+);/";
            if (preg_match($namespacePattern, $content, $matches)) {
                $namespace = $matches[1];
                $useStatement = "use StoriesAPI\\$coreNamespaceSuffix\\Controller;";
                $newContent = preg_replace($namespacePattern, "namespace $namespace;\n\n$useStatement", $content);
                
                // Make the class extend Controller
                $classPattern = "/class\s+([^\s]+)(\s+extends\s+[^\s{]+)?/";
                if (preg_match($classPattern, $newContent, $matches)) {
                    $className = $matches[1];
                    $extends = $matches[2] ?? '';
                    
                    if (empty($extends)) {
                        $newContent = preg_replace($classPattern, "class $className extends Controller", $newContent);
                    } else {
                        echo "<p style='color:orange'>⚠️ $fileName already extends another class: $extends</p>";
                    }
                }
                
                if (file_put_contents($controllerFile, $newContent)) {
                    echo "<p style='color:green'>✅ Updated $fileName to extend Controller.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update $fileName.</p>";
                }
            } else {
                echo "<p style='color:red'>❌ Could not find namespace declaration in $fileName.</p>";
            }
        } else {
            echo "<p>$fileName is not a controller class. Skipping.</p>";
        }
    }
}

// Specifically fix DirectoryItemsController and AiToolsController
$directoryController = $endpointsPath . '/DirectoryItemsController.php';
$aiToolsController = $endpointsPath . '/AiToolsController.php';

echo "<h2>Specifically Fixing DirectoryItemsController and AiToolsController</h2>";

// Fix DirectoryItemsController
if (file_exists($directoryController)) {
    echo "<h3>Fixing DirectoryItemsController</h3>";
    
    // Read the file content
    $content = file_get_contents($directoryController);
    
    // Create a backup
    $backupFile = $directoryController . '.bak.' . date('YmdHis');
    if (copy($directoryController, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
    
    // Check if it has the namespace declaration
    $namespacePattern = "/namespace\s+([^;]+);/";
    if (preg_match($namespacePattern, $content, $matches)) {
        $namespace = $matches[1];
        
        // Check if it has the use statement
        $usePattern = "/use\s+StoriesAPI\\\\([^\\\\]+)\\\\Controller;/";
        if (preg_match($usePattern, $content)) {
            // Update the use statement
            $newContent = preg_replace($usePattern, "use StoriesAPI\\$coreNamespaceSuffix\\Controller;", $content);
        } else {
            // Add the use statement
            $useStatement = "use StoriesAPI\\$coreNamespaceSuffix\\Controller;";
            $newContent = preg_replace($namespacePattern, "namespace $namespace;\n\n$useStatement", $content);
        }
        
        // Make sure it extends Controller
        $classPattern = "/class\s+DirectoryItemsController(\s+extends\s+[^\s{]+)?/";
        if (preg_match($classPattern, $newContent, $matches)) {
            $extends = $matches[1] ?? '';
            
            if (empty($extends)) {
                $newContent = preg_replace($classPattern, "class DirectoryItemsController extends Controller", $newContent);
            } elseif (strpos($extends, 'Controller') === false) {
                $newContent = preg_replace($classPattern, "class DirectoryItemsController extends Controller", $newContent);
            }
        }
        
        if (file_put_contents($directoryController, $newContent)) {
            echo "<p style='color:green'>✅ Updated DirectoryItemsController.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update DirectoryItemsController.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not find namespace declaration in DirectoryItemsController.</p>";
    }
} else {
    echo "<p style='color:red'>❌ DirectoryItemsController not found at: $directoryController</p>";
}

// Fix AiToolsController
if (file_exists($aiToolsController)) {
    echo "<h3>Fixing AiToolsController</h3>";
    
    // Read the file content
    $content = file_get_contents($aiToolsController);
    
    // Create a backup
    $backupFile = $aiToolsController . '.bak.' . date('YmdHis');
    if (copy($aiToolsController, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
    
    // Check if it has the namespace declaration
    $namespacePattern = "/namespace\s+([^;]+);/";
    if (preg_match($namespacePattern, $content, $matches)) {
        $namespace = $matches[1];
        
        // Check if it has the use statement
        $usePattern = "/use\s+StoriesAPI\\\\([^\\\\]+)\\\\Controller;/";
        if (preg_match($usePattern, $content)) {
            // Update the use statement
            $newContent = preg_replace($usePattern, "use StoriesAPI\\$coreNamespaceSuffix\\Controller;", $content);
        } else {
            // Add the use statement
            $useStatement = "use StoriesAPI\\$coreNamespaceSuffix\\Controller;";
            $newContent = preg_replace($namespacePattern, "namespace $namespace;\n\n$useStatement", $content);
        }
        
        // Make sure it extends Controller
        $classPattern = "/class\s+AiToolsController(\s+extends\s+[^\s{]+)?/";
        if (preg_match($classPattern, $newContent, $matches)) {
            $extends = $matches[1] ?? '';
            
            if (empty($extends)) {
                $newContent = preg_replace($classPattern, "class AiToolsController extends Controller", $newContent);
            } elseif (strpos($extends, 'Controller') === false) {
                $newContent = preg_replace($classPattern, "class AiToolsController extends Controller", $newContent);
            }
        }
        
        if (file_put_contents($aiToolsController, $newContent)) {
            echo "<p style='color:green'>✅ Updated AiToolsController.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update AiToolsController.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not find namespace declaration in AiToolsController.</p>";
    }
} else {
    echo "<p style='color:red'>❌ AiToolsController not found at: $aiToolsController</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>Now test the API endpoints using the <a href='test_api_format.php'>test_api_format.php</a> script.</p>";