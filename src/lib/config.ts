// Config interface with index signature to allow string access
interface Config {
  [key: string]: string | number | boolean | null;
  'site.name': string;
  'site.title': string;
  'site.description': string;
  'site.favicon.png': string;
  'api.url': string;
  'api.timeout': number;
  'features.premium': boolean;
  'features.ai': boolean;
  'features.educator': boolean;
  'features.publish': boolean;
  'ui.theme': string;
  'ui.font': string;
  'ui.logo': string;
  'social.twitter': string;
  'social.facebook': string;
  'social.instagram': string;
  'content.storiesPerPage': number;
  'content.authorsPerPage': number;
  'content.featuredStories': number;
}

// Default configuration
const defaultConfig: Config = {
  'site.name': 'Stories From The Web',
  'site.title': 'Stories From The Web | Share and read children\'s stories',
  'site.description': 'A platform for sharing and discovering stories from around the web.',
  'site.favicon.png': '/favicon.png',

  // API configuration - with public URL from environment or default to relative path
  'api.url': '/api',
  'api.timeout': 10000,
  
  // Feature flags
  'features.premium': false,
  'features.ai': true,
  'features.educator': true,
  'features.publish': true,
  
  // UI customization
  'ui.theme': 'light',
  'ui.font': 'Nunito',
  'ui.logo': '/stories_from_the_web_transparent.png',
  
  // Social media
  'social.twitter': 'https://twitter.com/storiesfromtheweb',
  'social.facebook': 'https://facebook.com/storiesfromtheweb',
  'social.instagram': 'https://instagram.com/storiesfromtheweb',
  
  // Content limits
  'content.storiesPerPage': 12,
  'content.authorsPerPage': 24,
  'content.featuredStories': 5
};

// Function to get configuration value
export function getConfig(key: string, defaultValue?: any): any {
  // Try to get from environment
  const envKey = `PUBLIC_${key.replace(/\./g, '_').toUpperCase()}`;
  const envValue = import.meta.env[envKey];
  if (envValue !== undefined) {
    return envValue;
  }
  
  // Try to get from default config
  if (defaultConfig[key] !== undefined) {
    return defaultConfig[key];
  }
  
  // Fallback to provided default value or null
  return defaultValue !== undefined ? defaultValue : null;
}

// Function to get API URL with fallbacks
export function getApiUrl(): string {
  // Try Astro environment variable first
  const envApiUrl = import.meta.env.PUBLIC_API_URL;
  if (envApiUrl) {
    return envApiUrl;
  }
  
  // Try window.location based URL if we're in the browser
  if (typeof window !== 'undefined') {
    // If we're on Netlify, we'll use the Netlify function
    if (window.location.hostname.includes('netlify.app')) {
      return '/.netlify/functions/api';
    }
    
    // For API subdomain
    if (window.location.hostname.startsWith('api.')) {
      return '';
    }
    
    // Try to use the same domain but with api subdomain
    const hostname = window.location.hostname;
    if (hostname !== 'localhost' && !hostname.includes('127.0.0.1')) {
      return `https://api.${hostname}`;
    }
  }
  
  // Fallback to relative path
  return '/api';
}

// Export the config object for direct access
export const config = defaultConfig;
