// Environment variables and configuration
const PUBLIC_API_URL = import.meta.env.PUBLIC_API_URL || '/api';
const PUBLIC_ASSETS_URL = import.meta.env.PUBLIC_ASSETS_URL || '/images';

// Site configuration
const siteConfig = {
  site: {
    name: 'Stories From The Web',
    title: 'Stories From The Web | Share and Discover Children\'s Stories',
    description: 'A platform for sharing and discovering stories from around the web. We believe everyone has a story worth telling.',
    url: 'https://storiesfromtheweb.org',
    version: '2.1',
    favicon: {
      png: '/favicon.png'
    },
    social: {
      twitter: 'storiesfromtheweb',
      facebook: 'storiesfromtheweb',
      instagram: 'storiesfromtheweb'
    },
    contact: {
      email: 'support@storiesfromtheweb.org'
    },
    footer: {
      copyright: 'Stories from the Web. All rights reserved.'
    },
    meta: {
      og_image: 'https://storiesfromtheweb.org/og-image.jpg',
      twitter_image: 'https://storiesfromtheweb.org/twitter-image.jpg'
    }
  }
};

// Helper function to build asset URLs
export const getAssetUrl = (path: string): string => {
  // Remove leading slash if present to avoid double slashes
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  // Replace spaces with dashes and encode the filename
  const encodedPath = cleanPath.replace(/\s+/g, '-').toLowerCase();
  return `${PUBLIC_ASSETS_URL}/${encodedPath}`;
};

// Helper function to get config values with dot notation
export function getConfig(key: string, defaultValue: any = null): any {
  const parts = key.split('.');
  let value: any = siteConfig;
  
  for (const part of parts) {
    if (value === undefined || value === null || !Object.prototype.hasOwnProperty.call(value, part)) {
      return defaultValue;
    }
    value = value[part];
  }
  
  return value;
}

// Export config object and helper functions
export { getConfig };

export const config = {
  apiUrl: PUBLIC_API_URL,
  assetsUrl: PUBLIC_ASSETS_URL,
  getAssetUrl,
  site: siteConfig.site
};

export default config;
