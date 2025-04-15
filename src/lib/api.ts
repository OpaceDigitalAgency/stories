const STRAPI_URL = import.meta.env.STRAPI_URL || 'http://localhost:1337';
const STRAPI_TOKEN = import.meta.env.STRAPI_TOKEN;

export const getMediaUrl = (url: string) => {
  if (url.startsWith('http')) {
    return url;
  }
  return `${STRAPI_URL}${url}`;
};

export const fetchFromStrapi = async (endpoint: string, params = {}) => {
  try {
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
      return { data: [] };
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from Strapi:', error);
    return { data: [] };
  }
};

// Strapi response interfaces
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