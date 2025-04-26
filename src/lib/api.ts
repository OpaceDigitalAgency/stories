// Base API URL
const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

// Type definitions
export interface Story {
  title: string;
  excerpt: string;
  coverImage: string;
  slug: string;
  publishDate: string;
}

export interface Author {
  name: string;
  bio: string;
  avatar: string;
  slug: string;
}

export interface Game {
  title: string;
  description: string;
  coverImage: string;
  slug: string;
  price: number;
  rating: number;
}

export interface DirectoryItem {
  title: string;
  description: string;
  coverImage: string;
  slug: string;
  category: string;
  rating: number;
  priceRange: string;
}

export interface AiTool {
  title: string;
  description: string;
  coverImage: string;
  slug: string;
  category: string;
  pricingType: string;
  featured: boolean;
}

// API response type
interface ApiResponse<T> {
  data: T[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

// Helper function to build URL with query parameters
export const buildUrl = (endpoint: string, params: Record<string, string | number | boolean> = {}) => {
  const url = new URL(`${API_URL}${endpoint}`);
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.append(key, String(value));
  });
  return url.toString();
};

// Generic fetch function with error handling
export async function fetchApi<T>(endpoint: string, params: Record<string, string | number | boolean> = {}): Promise<T> {
  const url = buildUrl(endpoint, params);
  const response = await fetch(url);
  
  if (!response.ok) {
    throw new Error(`API error: ${response.status} ${response.statusText}`);
  }
  
  return response.json();
}

// Resource-specific fetch functions with proper mapping
export async function fetchStories(page = 1, limit = 10): Promise<Story[]> {
  const response = await fetchApi<ApiResponse<Story>>('/stories', {
    'sort': 'publishedAt:desc',
    'pagination[limit]': limit,
    'pagination[page]': page
  });
  return response.data.map(item => ({
    title: item.title,
    excerpt: item.excerpt,
    coverImage: item.cover_url,
    slug: item.slug,
    publishDate: item.publishedAt
  }));
}

export async function fetchAuthors(): Promise<Author[]> {
  const response = await fetchApi<ApiResponse<Author>>('/authors', {
    'sort': 'name:asc'
  });
  return response.data.map(item => ({
    name: item.name,
    bio: item.bio,
    avatar: item.avatar_url,
    slug: item.slug
  }));
}

export async function fetchGames(): Promise<Game[]> {
  const response = await fetchApi<ApiResponse<Game>>('/games', {
    'sort': 'created_at:desc'
  });
  return response.data.map(item => ({
    title: item.title,
    description: item.description,
    coverImage: item.cover_url,
    slug: item.slug,
    price: item.price,
    rating: item.rating
  }));
}

export async function fetchDirectoryItems(): Promise<DirectoryItem[]> {
  const response = await fetchApi<ApiResponse<DirectoryItem>>('/directory-items', {
    'sort': 'created_at:desc'
  });
  return response.data.map(item => ({
    title: item.title,
    description: item.description,
    coverImage: item.cover_url,
    slug: item.slug,
    category: item.category,
    rating: item.rating,
    priceRange: item.price_range
  }));
}

export async function fetchAiTools(): Promise<AiTool[]> {
  const response = await fetchApi<ApiResponse<AiTool>>('/ai-tools', {
    'sort': 'created_at:desc'
  });
  return response.data.map(item => ({
    title: item.title,
    description: item.description,
    coverImage: item.cover_url,
    slug: item.slug,
    category: item.category,
    pricingType: item.pricing_type,
    featured: item.featured
  }));
}

// Single item fetch functions
export async function fetchStory(slug: string): Promise<Story> {
  const response = await fetchApi<ApiResponse<Story>>('/stories', {
    'filters[slug][$eq]': slug
  });
  const item = response.data[0];
  return {
    title: item.title,
    excerpt: item.excerpt,
    coverImage: item.cover_url,
    slug: item.slug,
    publishDate: item.publishedAt
  };
}

export async function fetchAuthor(slug: string): Promise<Author> {
  const response = await fetchApi<ApiResponse<Author>>('/authors', {
    'filters[slug][$eq]': slug
  });
  const item = response.data[0];
  return {
    name: item.name,
    bio: item.bio,
    avatar: item.avatar_url,
    slug: item.slug
  };
}