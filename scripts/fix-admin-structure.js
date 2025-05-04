/**
 * Script to fix admin pages that have both includes and hard-coded HTML structure
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
  'includes/header.php',
  'includes/footer.php'
];

// Find all PHP files with both includes and hard-coded HTML structure
async function findFilesToFix() {
  const result = require('child_process').execSync(
    'grep -r "include.*header.php" --include="*.php" stories-backend/admin/',
    { encoding: 'utf-8' }
  );
  
  const files = result.split('\n')
    .filter(line => line.trim() !== '')
    .map(line => line.split(':')[0])
    .filter(file => !ignorePatterns.some(pattern => file.includes(pattern)));
  
  return files;
}

// Fix a file by removing hard-coded HTML structure
async function fixFile(filePath) {
  try {
    // Read the file
    const content = await readFile(filePath, 'utf-8');
    
    // Skip if the file doesn't have hard-coded HTML structure
    if (!content.includes('<!DOCTYPE html>') && !content.includes('<html') && !content.includes('<head')) {
      console.log(`Skipping ${filePath} - no hard-coded HTML structure`);
      return;
    }
    
    // Extract the PHP code and includes
    const headerIncludeMatch = content.match(/include\s+['"].*?header\.php['"];/);
    const footerIncludeMatch = content.match(/include\s+['"].*?footer\.php['"];/);
    
    if (!headerIncludeMatch) {
      console.log(`Skipping ${filePath} - no header include found`);
      return;
    }
    
    // Extract the main content between the header include and the footer include or end of file
    let mainContent = '';
    const headerIncludePos = content.indexOf(headerIncludeMatch[0]) + headerIncludeMatch[0].length;
    const footerIncludePos = footerIncludeMatch ? content.indexOf(footerIncludeMatch[0]) : content.length;
    
    // Extract the content between the header include and the HTML structure
    const beforeHtmlMatch = content.substring(headerIncludePos, footerIncludePos).match(/^([\s\S]*?)(?=<!DOCTYPE|<html|$)/);
    let beforeHtml = '';
    if (beforeHtmlMatch) {
      beforeHtml = beforeHtmlMatch[1];
    }
    
    // Extract the content between the body tags
    const bodyMatch = content.substring(headerIncludePos, footerIncludePos).match(/<body[^>]*>([\s\S]*?)<\/body>/);
    if (bodyMatch) {
      mainContent = bodyMatch[1];
    } else {
      // If no body tags, try to extract content between the HTML structure and the footer include
      const htmlMatch = content.substring(headerIncludePos, footerIncludePos).match(/<html[^>]*>([\s\S]*?)(?=<\/html>|$)/);
      if (htmlMatch) {
        const htmlContent = htmlMatch[1];
        const headEndIndex = htmlContent.indexOf('</head>');
        if (headEndIndex !== -1) {
          mainContent = htmlContent.substring(headEndIndex + 7);
        } else {
          mainContent = htmlContent;
        }
      } else {
        // If no HTML structure, use all content between header and footer includes
        mainContent = content.substring(headerIncludePos, footerIncludePos);
      }
    }
    
    // Create the new content
    const newContent = content.substring(0, headerIncludePos) + 
      beforeHtml + 
      mainContent + 
      (footerIncludeMatch ? content.substring(footerIncludePos) : '');
    
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
    console.log(`Found ${files.length} files to check`);
    
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
