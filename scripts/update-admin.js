/**
 * Script to update all admin PHP files to use the shared configuration
 * 
 * This script will:
 * 1. Find all PHP files in the admin directory
 * 2. Update them to use the shared configuration
 */

const fs = require('fs');
const path = require('path');
const { promisify } = require('util');

const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);
const readdir = promisify(fs.readdir);
const stat = promisify(fs.stat);

// Configuration
const adminDir = path.resolve(__dirname, '../stories-backend/admin');
const excludeDirs = ['_archive', '_wp'];

// Helper function to find all PHP files recursively
async function findPhpFiles(dir) {
  const files = [];
  const entries = await readdir(dir);
  
  for (const entry of entries) {
    // Skip excluded directories
    if (excludeDirs.includes(entry)) {
      continue;
    }
    
    const fullPath = path.join(dir, entry);
    const stats = await stat(fullPath);
    
    if (stats.isDirectory()) {
      const subFiles = await findPhpFiles(fullPath);
      files.push(...subFiles);
    } else if (entry.endsWith('.php')) {
      files.push(fullPath);
    }
  }
  
  return files;
}

// Helper function to update a file to use the shared configuration
async function updateFile(filePath) {
  try {
    // Skip header.php and footer.php as they've already been updated
    if (filePath.endsWith('header.php') || filePath.endsWith('footer.php')) {
      console.log(`Skipping ${filePath} - already updated`);
      return;
    }
    
    // Read the file
    const content = await readFile(filePath, 'utf-8');
    
    // Skip files that already include the header
    if (content.includes('include') && content.includes('header.php')) {
      console.log(`Skipping ${filePath} - already includes header.php`);
      return;
    }
    
    // Skip files that don't have PHP opening tag
    if (!content.includes('<?php')) {
      console.log(`Skipping ${filePath} - not a PHP file`);
      return;
    }
    
    // Update the file
    let newContent = content;
    
    // Add page title and current page variables if not present
    if (!content.includes('$pageTitle') && !content.includes('$currentPage')) {
      const fileName = path.basename(filePath, '.php');
      const pageTitle = fileName
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
      
      const phpOpenTagPos = content.indexOf('<?php');
      const afterPhpOpenTag = content.indexOf('\n', phpOpenTagPos) + 1;
      
      newContent = content.slice(0, afterPhpOpenTag) + 
        `\n// Page variables\n$pageTitle = '${pageTitle}';\n$currentPage = '${fileName}';\n\n` + 
        content.slice(afterPhpOpenTag);
    }
    
    // Add header include if not present
    if (!newContent.includes('include') || !newContent.includes('header.php')) {
      // Determine the relative path to the includes directory
      const relativePath = path.relative(path.dirname(filePath), path.join(adminDir, 'includes'));
      const headerPath = path.join(relativePath, 'header.php').replace(/\\/g, '/');
      
      // Find a good position to add the include
      const phpOpenTagPos = newContent.indexOf('<?php');
      const afterPhpOpenTag = newContent.indexOf('\n', phpOpenTagPos) + 1;
      
      // Add the include after any initial comments and variable declarations
      let includePos = afterPhpOpenTag;
      const lines = newContent.split('\n');
      for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (line.trim() === '' || line.trim().startsWith('//') || line.trim().startsWith('/*') || 
            line.trim().startsWith('*') || line.trim().startsWith('*/') || 
            line.trim().startsWith('$') || line.trim().startsWith('require') || 
            line.trim().startsWith('include')) {
          includePos += line.length + 1; // +1 for the newline
        } else {
          break;
        }
      }
      
      newContent = newContent.slice(0, includePos) + 
        `\n// Include header\ninclude '${headerPath}';\n\n` + 
        newContent.slice(includePos);
    }
    
    // Add footer include if not present
    if (!newContent.includes('include') || !newContent.includes('footer.php')) {
      // Determine the relative path to the includes directory
      const relativePath = path.relative(path.dirname(filePath), path.join(adminDir, 'includes'));
      const footerPath = path.join(relativePath, 'footer.php').replace(/\\/g, '/');
      
      // Find a good position to add the include
      const htmlClosePos = newContent.lastIndexOf('</html>');
      const bodyClosePos = newContent.lastIndexOf('</body>');
      
      if (htmlClosePos !== -1 && bodyClosePos !== -1) {
        // If HTML and body tags are present, replace them with the footer include
        newContent = newContent.slice(0, bodyClosePos) + 
          `\n// Include footer\ninclude '${footerPath}';\n` + 
          newContent.slice(htmlClosePos + 8); // +8 to remove </html>
      } else {
        // Otherwise, add the footer include at the end
        newContent += `\n\n// Include footer\ninclude '${footerPath}';\n`;
      }
    }
    
    // Write the new content
    await writeFile(filePath, newContent, 'utf-8');
    console.log(`Updated ${filePath}`);
  } catch (error) {
    console.error(`Error updating ${filePath}:`, error);
  }
}

// Main function
async function main() {
  try {
    // Find all PHP files
    const files = await findPhpFiles(adminDir);
    console.log(`Found ${files.length} PHP files`);
    
    // Update each file
    for (const file of files) {
      await updateFile(file);
    }
    
    console.log('Done!');
  } catch (error) {
    console.error('Error:', error);
  }
}

// Run the script
main();
