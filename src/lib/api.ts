// Define API response types

export interface ApiResponse<T> {
  data: ApiData<T>[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

export interface ApiData<T> {
  id: number;
  attributes: T;
}

export interface ApiImage {
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
  cover: ApiImage;
  author: ApiResponse<Author>;
  tags: ApiResponse<Tag>;
}

export interface Author {
  name: string;
  slug: string;
  bio: string;
  avatar: ApiImage;
  stories: ApiResponse<Story>;
}

export interface BlogPost {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  cover: ApiImage;
  author: ApiResponse<Author>;
}

export interface DirectoryItem {
  name: string;
  description: string;
  url: string;
  logo: ApiImage;
  category: string;
}

export interface Game {
  title: string;
  description: string;
  url: string;
  thumbnail: ApiImage;
  category: string;
}

export interface AiTool {
  name: string;
  description: string;
  url: string;
  logo: ApiImage;
  category: string;
}

export interface Tag {
  name: string;
  slug: string;
}

// API URL for the custom backend
const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

export const getMediaUrl = (url: string) => {
  if (url.startsWith('http')) {
    return url;
  }
  return `${API_URL}/uploads/${url}`;
};

// Define params interface
interface ApiParams {
  filters?: Record<string, any>;
  sort?: string;
  page?: number;
  pageSize?: number;
  [key: string]: any;
}

export const fetchFromApi = async (endpoint: string, params: ApiParams = {}) => {
  try {
    // Build query parameters
    const queryParams = new URLSearchParams();
    
    if (params.page) {
      queryParams.append('page', params.page.toString());
    }
    
    if (params.pageSize) {
      queryParams.append('pageSize', params.pageSize.toString());
    }
    
    if (params.sort) {
      queryParams.append('sort', params.sort);
    }
    
    // Add any filters
    if (params.filters) {
      Object.entries(params.filters).forEach(([key, value]) => {
        queryParams.append(key, value.toString());
      });
    }
    
    // Make the API call
    const queryString = queryParams.toString();
    const url = `${API_URL}/${endpoint}${queryString ? `?${queryString}` : ''}`;
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json'
    };
    
    // Get auth token from cookie if available
    const authToken = document.cookie
      .split('; ')
      .find(row => row.startsWith('auth_token='))
      ?.split('=')[1];
    
    if (authToken) {
      headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const res = await fetch(url, { headers });
    
    if (!res.ok) {
      const errorMessage = `Error fetching from API: ${res.status} ${res.statusText}`;
      console.error(errorMessage);
      console.error(`URL: ${url}`);
      console.error(`Headers: ${JSON.stringify(headers)}`);
      
      try {
        const errorResponse = await res.text();
        console.error(`Error response: ${errorResponse}`);
      } catch (textError) {
        console.error('Could not read error response body');
      }
      
      throw new Error(`Failed to fetch from API: ${res.status} ${res.statusText}`);
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from API:', error);
    console.error(`Endpoint: ${endpoint}`);
    console.error(`Params: ${JSON.stringify(params)}`);
    
    // On error, return empty data
    console.log(`Error fetching from API. Returning empty data for endpoint: ${endpoint}`);
    
    // Return empty data structure
    if (endpoint.includes('/') && endpoint.split('/').length > 1) {
      return { data: null };
    } else {
      return { data: [], meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } } };
    }
  }
};