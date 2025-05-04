/**
 * Script to fix admin pages that still have hard-coded HTML structure
 */

const fs = require('fs');
const path = require('path');
const { promisify } = require('util');

const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);

// List of files to ignore (archive files, etc.)
const ignorePatterns = [
  '_archive',
  '_wp',
  'header.php',
  'footer.php'
];

// Find all PHP files with hard-coded HTML structure
async function findFilesToFix() {
  const result = require('child_process').execSync(
    'grep -r "<html" --include="*.php" stories-backend/admin/',
    { encoding: 'utf-8' }
  );
  
  const files = result.split('\n')
    .filter(line => line.trim() !== '')
    .map(line => line.split(':')[0])
    .filter(file => !ignorePatterns.some(pattern => file.includes(pattern)));
  
  return files;
}

// Fix a file by removing hard-coded HTML structure and adding includes
async function fixFile(filePath) {
  try {
    // Read the file
    const content = await readFile(filePath, 'utf-8');
    
    // Skip if the file already includes header.php
    if (content.includes('include') && content.includes('header.php')) {
      console.log(`Skipping ${filePath} - already includes header.php`);
      return;
    }
    
    // Extract the PHP code at the beginning
    const phpStartMatch = content.match(/^<\?php([\s\S]*?)(?=<html|<\?php|$)/);
    let phpStart = '';
    if (phpStartMatch) {
      phpStart = phpStartMatch[0];
    }
    
    // Extract the main content between <body> and </body>
    const bodyMatch = content.match(/<body[^>]*>([\s\S]*?)<\/body>/);
    let bodyContent = '';
    if (bodyMatch) {
      bodyContent = bodyMatch[1];
    } else {
      // If no body tags, try to extract content between HTML tags
      const htmlMatch = content.match(/<html[^>]*>([\s\S]*?)<\/html>/);
      if (htmlMatch) {
        const htmlContent = htmlMatch[1];
        const headEndIndex = htmlContent.indexOf('</head>');
        if (headEndIndex !== -1) {
          bodyContent = htmlContent.substring(headEndIndex + 7);
        } else {
          bodyContent = htmlContent;
        }
      } else {
        console.log(`Skipping ${filePath} - could not extract content`);
        return;
      }
    }
    
    // Determine the relative path to the includes directory
    const relativePath = path.relative(path.dirname(filePath), path.join(path.dirname(filePath), '..', 'includes'));
    const headerPath = path.join(relativePath, 'header.php').replace(/\\/g, '/');
    const footerPath = path.join(relativePath, 'footer.php').replace(/\\/g, '/');
    
    // Extract the page title from the content
    const titleMatch = content.match(/<title>(.*?)<\/title>/);
    let pageTitle = path.basename(filePath, '.php')
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
    
    if (titleMatch) {
      pageTitle = titleMatch[1].replace(' - Stories Admin', '').trim();
    }
    
    // Create the new content
    const newContent = `<?php
${phpStart.replace('<?php', '').trim()}

// Page variables
$pageTitle = '${pageTitle}';
$currentPage = '${path.basename(filePath, '.php')}';

// Include header
include '${headerPath}';
?>

${bodyContent.trim()}

<?php
// Include footer
include '${footerPath}';
?>`;
    
    // Write the new content
    await writeFile(filePath, newContent, 'utf-8');
    console.log(`Fixed ${filePath}`);
  } catch (error) {
    console.error(`Error fixing ${filePath}:`, error);
  }
}

// Main function
async function main() {
  try {
    // Find files to fix
    const files = await findFilesToFix();
    console.log(`Found ${files.length} files to fix`);
    
    // Fix each file
    for (const file of files) {
      await fixFile(file);
    }
    
    console.log('Done!');
  } catch (error) {
    console.error('Error:', error);
  }
}

// Run the script
main();
