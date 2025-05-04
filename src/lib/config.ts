// Environment variables and configuration
const PUBLIC_API_URL = import.meta.env.PUBLIC_API_URL || '/api';
const PUBLIC_ASSETS_URL = import.meta.env.PUBLIC_ASSETS_URL || '/images';

// Helper function to build asset URLs
export const getAssetUrl = (path: string): string => {
  // Remove leading slash if present to avoid double slashes
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  // Replace spaces with dashes and encode the filename
  const encodedPath = cleanPath.replace(/\s+/g, '-').toLowerCase();
  return `${PUBLIC_ASSETS_URL}/${encodedPath}`;
};

export const config = {
  apiUrl: PUBLIC_API_URL,
  assetsUrl: PUBLIC_ASSETS_URL,
  getAssetUrl
};

export default config;
