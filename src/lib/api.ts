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

// Import mock data
import {
  mockStories,
  mockAuthors,
  mockTags,
  mockBlogPosts,
  mockDirectoryItems,
  mockGames,
  mockAiTools
} from './mockData';

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
    // If STRAPI_URL is empty, return mock data
    if (!STRAPI_URL) {
      console.log(`Using mock data for endpoint: ${endpoint}`);
      
      // Handle stories endpoint with filters
      if (endpoint === 'stories') {
        const filters = params.filters || {};
        
        // Create a copy of the mock stories data
        const storiesData = JSON.parse(JSON.stringify(mockStories));
        
        // If there are filters, apply them
        if (Object.keys(filters).length > 0) {
          // Handle featured filter
          if (filters.featured === true) {
            storiesData.data = storiesData.data.filter(story => story.attributes.featured === true);
          }
          
          // Handle isSelfPublished filter (mock this by using stories with id 2)
          if (filters.isSelfPublished === true) {
            storiesData.data = storiesData.data.filter(story => story.id === 2);
          }
          
          // Handle isAIEnhanced filter (mock this by using stories with id 3)
          if (filters.isAIEnhanced === true) {
            storiesData.data = storiesData.data.filter(story => story.id === 3);
          }
          
          // Handle isSponsored filter (mock this by using stories with id 1)
          if (filters.isSponsored === true) {
            storiesData.data = storiesData.data.filter(story => story.id === 1);
          }
          
          // Update pagination metadata
          storiesData.meta.pagination.total = storiesData.data.length;
          storiesData.meta.pagination.pageCount = Math.ceil(storiesData.data.length / storiesData.meta.pagination.pageSize);
        }
        
        return storiesData;
      }
      
      // Handle other endpoints
      switch (endpoint) {
        case 'authors':
          return mockAuthors;
        case 'tags':
          return mockTags;
        case 'blog-posts':
          return mockBlogPosts;
        case 'directory-items':
          return mockDirectoryItems;
        case 'games':
          return mockGames;
        case 'ai-tools':
          return mockAiTools;
        default:
          console.warn(`No mock data available for endpoint: ${endpoint}`);
          return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
      }
    }

    // If STRAPI_URL is set, make the actual API call
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
    
    // On error, fall back to mock data
    console.log(`Falling back to mock data for endpoint: ${endpoint}`);
    
    // Handle stories endpoint with filters
    if (endpoint === 'stories') {
      const filters = params.filters || {};
      
      // Create a copy of the mock stories data
      const storiesData = JSON.parse(JSON.stringify(mockStories));
      
      // If there are filters, apply them
      if (Object.keys(filters).length > 0) {
        // Handle featured filter
        if (filters.featured === true) {
          storiesData.data = storiesData.data.filter(story => story.attributes.featured === true);
        }
        
        // Handle isSelfPublished filter (mock this by using stories with id 2)
        if (filters.isSelfPublished === true) {
          storiesData.data = storiesData.data.filter(story => story.id === 2);
        }
        
        // Handle isAIEnhanced filter (mock this by using stories with id 3)
        if (filters.isAIEnhanced === true) {
          storiesData.data = storiesData.data.filter(story => story.id === 3);
        }
        
        // Handle isSponsored filter (mock this by using stories with id 1)
        if (filters.isSponsored === true) {
          storiesData.data = storiesData.data.filter(story => story.id === 1);
        }
        
        // Update pagination metadata
        storiesData.meta.pagination.total = storiesData.data.length;
        storiesData.meta.pagination.pageCount = Math.ceil(storiesData.data.length / storiesData.meta.pagination.pageSize);
      }
      
      return storiesData;
    }
    
    // Handle other endpoints
    switch (endpoint) {
      case 'authors':
        return mockAuthors;
      case 'tags':
        return mockTags;
      case 'blog-posts':
        return mockBlogPosts;
      case 'directory-items':
        return mockDirectoryItems;
      case 'games':
        return mockGames;
      case 'ai-tools':
        return mockAiTools;
      default:
        return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
    }
  }
};