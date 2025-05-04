/**
 * Script to fix dynamic pages that weren't properly updated
 */

const fs = require('fs');
const path = require('path');
const { promisify } = require('util');

const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);

// List of dynamic pages to fix
const pagesToFix = [
  'src/pages/ai-tools/[slug].astro',
  'src/pages/directories/[slug].astro',
  'src/pages/games/[slug].astro'
];

// Template for dynamic pages
const dynamicPageTemplate = (relativePath) => `---
import Layout from '${relativePath}/layouts/Layout.astro';
import { getConfig } from '${relativePath}/lib/config';
import { fetchData } from '${relativePath}/lib/api';

export const prerender = false;

// Get site name from config
const siteName = getConfig('site.name', 'Stories From The Web');

// Get the slug from the URL
const { slug } = Astro.params;

// Fetch data based on slug
let item = null;
let error = null;

try {
  // Replace with the appropriate fetch function for this page type
  item = await fetchData(slug);
} catch (e) {
  error = e.message;
}

// Page metadata
const title = item ? \`\${item.title} | \${siteName}\` : \`Item Not Found | \${siteName}\`;
const description = item ? item.description : \`We couldn't find the item you're looking for.\`;
---

<Layout title={title} description={description}>
  <main class="flex-grow w-full pt-24 pb-16">
    <div class="container mx-auto px-4 max-w-4xl">
      {error || !item ? (
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
          <h1 class="text-3xl font-bold text-red-600 mb-4">Item Not Found</h1>
          <p class="text-lg text-red-700 mb-6">
            We couldn't find the item you're looking for. It may have been removed or the URL might be incorrect.
          </p>
          <a href="/" class="inline-block bg-primary text-white font-medium py-2 px-6 rounded-lg hover:bg-primary-dark transition-colors">
            Return to Home
          </a>
        </div>
      ) : (
        <>
          <header class="mb-8">
            <h1 class="text-4xl font-bold text-text-primary mb-4">{item.title}</h1>
            {item.subtitle && (
              <p class="text-xl text-text-secondary">{item.subtitle}</p>
            )}
          </header>
          
          <div class="prose prose-lg max-w-none">
            {/* Replace with appropriate content structure for this page type */}
            <div set:html={item.content} />
          </div>
        </>
      )}
    </div>
  </main>
</Layout>`;

// Fix each page
async function fixPage(pagePath) {
  try {
    // Determine the relative path to the src directory
    const relativePath = path.relative(path.dirname(pagePath), path.resolve(__dirname, '../src')).replace(/\\/g, '/');
    
    // Create the new content
    const newContent = dynamicPageTemplate(relativePath);
    
    // Write the new content
    await writeFile(pagePath, newContent, 'utf-8');
    console.log(`Fixed ${pagePath}`);
  } catch (error) {
    console.error(`Error fixing ${pagePath}:`, error);
  }
}

// Main function
async function main() {
  try {
    // Fix each page
    for (const page of pagesToFix) {
      await fixPage(page);
    }
    
    console.log('Done!');
  } catch (error) {
    console.error('Error:', error);
  }
}

// Run the script
main();
