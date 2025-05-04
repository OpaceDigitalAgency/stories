/**
 * Configuration Reader
 * 
 * This file reads the shared configuration file and makes it available to Astro.
 */

import fs from 'fs';
import path from 'path';

// Define the path to the configuration file
const configPath = path.resolve('./config/site.json');

// Initialize the configuration object
let siteConfig: any = {};

// Read and parse the configuration file
try {
  const configJson = fs.readFileSync(configPath, 'utf-8');
  siteConfig = JSON.parse(configJson);
} catch (error) {
  console.error('Error reading site configuration:', error);
}

/**
 * Get a configuration value
 * 
 * @param key The configuration key (dot notation supported)
 * @param defaultValue The default value to return if the key is not found
 * @returns The configuration value
 */
export function getConfig(key: string, defaultValue: any = null): any {
  // Split the key into parts
  const parts = key.split('.');
  
  // Start with the full config object
  let value: any = siteConfig;
  
  // Traverse the config object
  for (const part of parts) {
    if (value === undefined || value === null || !Object.prototype.hasOwnProperty.call(value, part)) {
      return defaultValue;
    }
    value = value[part];
  }
  
  return value;
}

export default siteConfig;
