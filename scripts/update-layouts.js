/**
 * Script to update all Astro pages to use the Layout component
 * 
 * This script will:
 * 1. Find all Astro pages
 * 2. Update them to use the Layout component
 * 3. Replace the old HTML structure with the Layout component
 */

const fs = require('fs');
const path = require('path');
const { promisify } = require('util');

const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);
const readdir = promisify(fs.readdir);
const stat = promisify(fs.stat);

// Configuration
const pagesDir = path.resolve(__dirname, '../src/pages');
const layoutPath = '../layouts/Layout.astro';
const configPath = '../lib/config';

// Helper function to find all Astro files recursively
async function findAstroFiles(dir) {
  const files = [];
  const entries = await readdir(dir);
  
  for (const entry of entries) {
    const fullPath = path.join(dir, entry);
    const stats = await stat(fullPath);
    
    if (stats.isDirectory()) {
      const subFiles = await findAstroFiles(fullPath);
      files.push(...subFiles);
    } else if (entry.endsWith('.astro')) {
      files.push(fullPath);
    }
  }
  
  return files;
}

// Helper function to update a file to use the Layout component
async function updateFile(filePath) {
  try {
    // Read the file
    const content = await readFile(filePath, 'utf-8');
    
    // Skip files that already use the Layout component
    if (content.includes(`import Layout from '`) || content.includes(`import Layout from "`)) {
      console.log(`Skipping ${filePath} - already uses Layout component`);
      return;
    }
    
    // Determine the relative path to the layout
    const relativeDir = path.relative(path.dirname(filePath), path.resolve(__dirname, '../src/layouts'));
    const layoutImportPath = path.join(relativeDir, 'Layout.astro').replace(/\\/g, '/');
    
    // Determine the relative path to the config
    const relativeConfigDir = path.relative(path.dirname(filePath), path.resolve(__dirname, '../src/lib'));
    const configImportPath = path.join(relativeConfigDir, 'config').replace(/\\/g, '/');
    
    // Extract the frontmatter
    const frontmatterMatch = content.match(/^---\n([\s\S]*?)---\n/);
    if (!frontmatterMatch) {
      console.log(`Skipping ${filePath} - no frontmatter found`);
      return;
    }
    
    const frontmatter = frontmatterMatch[1];
    
    // Extract imports
    const imports = frontmatter.match(/import .+ from .+;/g) || [];
    
    // Remove NavHeader, Footer, and Favicon imports
    const filteredImports = imports.filter(imp => 
      !imp.includes('NavHeader') && 
      !imp.includes('Footer') && 
      !imp.includes('Favicon') &&
      !imp.includes('base.css')
    );
    
    // Add Layout and config imports
    filteredImports.unshift(`import Layout from '${layoutImportPath}';`);
    filteredImports.push(`import { getConfig } from '${configImportPath}';`);
    
    // Extract title and description
    let title = '';
    let description = '';
    
    const titleMatch = frontmatter.match(/const title = ['"](.+)['"]/);
    if (titleMatch) {
      title = titleMatch[1];
    }
    
    const descriptionMatch = frontmatter.match(/const description = ['"](.+)['"]/);
    if (descriptionMatch) {
      description = descriptionMatch[1];
    }
    
    // Create new frontmatter
    let newFrontmatter = filteredImports.join('\n');
    newFrontmatter += `\n\n// Get site name from config\nconst siteName = getConfig('site.name', 'Stories From The Web');\n`;
    
    if (title) {
      newFrontmatter += `\n// Page metadata\nconst title = \`${title.replace(/Stories From The Web/g, '${siteName}')}\`;\n`;
    } else {
      newFrontmatter += `\n// Page metadata\nconst title = siteName;\n`;
    }
    
    if (description) {
      newFrontmatter += `const description = \`${description.replace(/Stories From The Web/g, '${siteName}')}\`;\n`;
    }
    
    // Extract the body content
    const bodyMatch = content.match(/<body[^>]*>([\s\S]*)<\/body>/);
    if (!bodyMatch) {
      console.log(`Skipping ${filePath} - no body found`);
      return;
    }
    
    const bodyContent = bodyMatch[1];
    
    // Extract the main content
    const mainMatch = bodyContent.match(/<main[^>]*>([\s\S]*)<\/main>/);
    if (!mainMatch) {
      console.log(`Skipping ${filePath} - no main content found`);
      return;
    }
    
    const mainContent = mainMatch[1];
    
    // Extract scripts
    const scriptMatches = bodyContent.match(/<script[^>]*>([\s\S]*?)<\/script>/g) || [];
    let scripts = '';
    
    if (scriptMatches.length > 0) {
      scripts = `\n  <script slot="scripts">\n    ${scriptMatches.join('\n').replace(/<\/?script[^>]*>/g, '')}\n  </script>`;
    }
    
    // Extract styles
    const styleMatches = bodyContent.match(/<style[^>]*>([\s\S]*?)<\/style>/g) || [];
    let styles = '';
    
    if (styleMatches.length > 0) {
      styles = `\n  <style slot="scripts">\n    ${styleMatches.join('\n').replace(/<\/?style[^>]*>/g, '')}\n  </style>`;
    }
    
    // Create the new content
    const newContent = `---
${newFrontmatter}
---

<Layout title={title} description={description}>
  <main class="flex-grow w-full">
    ${mainContent.trim()}
  </main>${styles}${scripts}
</Layout>`;
    
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
    // Find all Astro files
    const files = await findAstroFiles(pagesDir);
    console.log(`Found ${files.length} Astro files`);
    
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
