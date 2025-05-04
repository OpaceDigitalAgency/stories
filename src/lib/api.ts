// Base API URL - use environment variable or fallback to proxy path
const API_URL = import.meta.env.PUBLIC_API_URL || '/api';

// Type definitions
export interface CoverImageUrls {
  default: string;
  thumbnail?: string;
  small?: string;
  medium?: string;
  large?: string;
}

export interface Story {
  title: string;
  excerpt: string;
  content?: string;
  coverImage: string | CoverImageUrls;
  cover_url?: string; // API response field
  cover_urls?: CoverImageUrls;
  slug: string;
  publishDate: string;
  publishedAt?: string; // API response field
  featured?: boolean;
  sponsored?: boolean;
  is_sponsored?: boolean; // API response field
  isAiEnhanced?: boolean;
  is_ai_enhanced?: boolean; // API response field
  isSelfPublished?: boolean;
  is_self_published?: boolean; // API response field
  needsModeration?: boolean;
  needs_moderation?: boolean; // API response field
  is_published?: boolean; // Whether the story is published
  rating?: number | undefined;
  reviewCount?: number;
  tags?: string[];
  author?: Author;
  source_type?: 'child' | 'parent' | 'classic';
  allow_reviews?: boolean;
  estimated_reading_time?: string | number;
  age_group?: string;
}

export interface Author {
  name: string;
  bio: string;
  avatar: string;
  avatar_url?: string; // API response field
  slug: string;
  author_type?: 'retail' | 'parent' | 'child' | 'educator';
  featured?: boolean;
  age?: number | string | null;
  location?: string | null;
}

export interface Game {
  title: string;
  description: string;
  coverImage: string;
  cover_url?: string; // API response field
  slug: string;
  price: number;
  rating: number;
  category: string;
  ageRange: string;
}

export interface DirectoryItem {
  title: string;
  description: string;
  coverImage: string;
  cover_url?: string; // API response field
  slug: string;
  category: string;
  rating: number;
  priceRange: string;
  price_range?: string; // API response field
}

export interface AiTool {
  title: string;
  description: string;
  coverImage: string;
  cover_url?: string; // API response field
  slug: string;
  category: string;
  pricingType: string;
  pricing_type?: string; // API response field
  featured: boolean;
}

// Helper function to build URL with query parameters
export const buildUrl = (endpoint: string, params: Record<string, string | number | boolean> = {}) => {
  try {
    // If we're using a Netlify function, manually construct an absolute URL
    if (typeof window !== 'undefined' && window.location.hostname.includes('netlify.app') && API_URL.includes('/.netlify/functions/api')) {
      const baseUrl = `${window.location.protocol}//${window.location.host}${API_URL}`;
      const url = new URL(endpoint, baseUrl);
      Object.entries(params).forEach(([key, value]) => {
        url.searchParams.append(key, String(value));
      });
      return url.toString();
    }
    
    // For regular API endpoints
    let apiBase = API_URL;
    // Ensure endpoint starts with a slash if the API URL doesn't end with one
    if (!apiBase.endsWith('/') && !endpoint.startsWith('/')) {
      apiBase += '/';
    }
    
    // If API_URL is a relative path, ensure it's properly formatted
    const fullUrl = apiBase + endpoint;
    const url = new URL(fullUrl, 'https://api.example.com'); // Use a dummy base for relative URLs
    
    Object.entries(params).forEach(([key, value]) => {
      url.searchParams.append(key, String(value));
    });
    
    // If using relative URLs, return just the pathname + search
    if (API_URL.startsWith('/')) {
      return `${url.pathname}${url.search}`;
    }
    
    return url.toString();
  } catch (e) {
    console.error('Error building URL:', e);
    // Fallback to simple concatenation
    let queryString = new URLSearchParams(
      Object.entries(params).map(([k, v]) => [k, String(v)])
    ).toString();
    return `${API_URL}${endpoint}${queryString ? '?' + queryString : ''}`;
  }
};

// Generic fetch function with error handling
export async function fetchApi<T>(endpoint: string, params: Record<string, string | number | boolean> = {}): Promise<T> {
  const url = buildUrl(endpoint, params);

  try {
    console.log(`Fetching from API: ${url}`);
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache'
      }
    });

    if (!response.ok) {
      throw new Error(`API error: ${response.status} ${response.statusText}`);
    }

    return await response.json();
  } catch (error) {
    console.error(`API request failed for ${url}:`, error);
    const errorObj: any = new Error(`API request failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    errorObj.endpoint = endpoint;
    errorObj.originalError = error;
    throw errorObj;
  }
}

// Rest of the file remains unchanged...
