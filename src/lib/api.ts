// Define environment variables type
interface ImportMetaEnv {
  STRAPI_URL?: string;
  STRAPI_TOKEN?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

// Augment the ImportMeta interface
declare global {
  interface ImportMeta {
    readonly env: ImportMetaEnv;
  }
}

// Define Strapi response types
export interface StrapiResponse<T> {
  data: StrapiData<T>[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

export interface StrapiData<T> {
  id: number;
  attributes: T;
}

export interface StrapiImage {
  data: {
    id: number;
    attributes: {
      url: string;
      width: number;
      height: number;
      formats: {
        thumbnail: { url: string; width: number; height: number; };
        small: { url: string; width: number; height: number; };
        medium: { url: string; width: number; height: number; };
        large: { url: string; width: number; height: number; };
      };
    };
  };
}

export interface Story {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  featured: boolean;
  averageRating: number;
  cover: StrapiImage;
  author: StrapiResponse<Author>;
  tags: StrapiResponse<Tag>;
}

export interface Author {
  name: string;
  slug: string;
  bio: string;
  avatar: StrapiImage;
  stories: StrapiResponse<Story>;
}

export interface BlogPost {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  cover: StrapiImage;
  author: StrapiResponse<Author>;
}

export interface DirectoryItem {
  name: string;
  description: string;
  url: string;
  logo: StrapiImage;
  category: string;
}

export interface Game {
  title: string;
  description: string;
  url: string;
  thumbnail: StrapiImage;
  category: string;
}

export interface AiTool {
  name: string;
  description: string;
  url: string;
  logo: StrapiImage;
  category: string;
}

export interface Tag {
  name: string;
  slug: string;
}

const STRAPI_URL = import.meta.env.STRAPI_URL || '';
const STRAPI_TOKEN = import.meta.env.STRAPI_TOKEN || '';

export const getMediaUrl = (url: string) => {
  if (url.startsWith('http')) {
    return url;
  }
  return `${STRAPI_URL}${url}`;
};

// Define params interface
interface StrapiParams {
  filters?: Record<string, any>;
  populate?: string | Record<string, any>;
  sort?: string;
  pagination?: {
    page?: number;
    pageSize?: number;
    limit?: number;
  };
  [key: string]: any;
}

export const fetchFromStrapi = async (endpoint: string, params: StrapiParams = {}) => {
  try {
    // If STRAPI_URL is empty, return empty data
    if (!STRAPI_URL) {
      console.log(`No Strapi URL configured. Returning empty data for endpoint: ${endpoint}`);
      return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
    }

    // Make the actual API call
    const queryString = new URLSearchParams(params).toString();
    const url = `${STRAPI_URL}/api/${endpoint}${queryString ? `?${queryString}` : ''}`;
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json'
    };
    
    if (STRAPI_TOKEN) {
      headers['Authorization'] = `Bearer ${STRAPI_TOKEN}`;
    }
    
    const res = await fetch(url, { headers });
    
    if (!res.ok) {
      console.error(`Error fetching from Strapi: ${res.status} ${res.statusText}`);
      throw new Error(`Failed to fetch from Strapi: ${res.status} ${res.statusText}`);
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from Strapi:', error);
    
    // On error, return empty data
    console.log(`Error fetching from Strapi. Returning empty data for endpoint: ${endpoint}`);
    
    // Return empty data structure
    if (endpoint.startsWith('authors/') || endpoint.startsWith('stories/')) {
      return { data: null };
    } else {
      return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
    }
  }
};