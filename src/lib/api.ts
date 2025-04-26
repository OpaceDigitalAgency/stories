// Base API URL
const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

// Type definitions
export interface Story {
  title: string;
  excerpt: string;
  coverImage: string;
  slug: string;
  publishDate: string;
  featured?: boolean;
  sponsored?: boolean;
  isAiEnhanced?: boolean;
  isSelfPublished?: boolean;
  needsModeration?: boolean;
  rating?: number;
  tags?: string[];
  author?: Author;
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
  const raw = await fetchApi<Story[]>('/stories', {
    'sort': 'publishedAt:desc',
    'pagination[limit]': limit,
    'pagination[page]': page
  });
  return raw.map(item => ({
    title: item.title,
    excerpt: item.excerpt,
    coverImage: item.cover_url,
    slug: item.slug,
    publishDate: item.publishedAt,
    featured: Boolean(item.featured),
    sponsored: Boolean(item.is_sponsored),
    isAiEnhanced: Boolean(item.is_ai_enhanced),
    isSelfPublished: Boolean(item.is_self_published),
    needsModeration: Boolean(item.needs_moderation),
    rating: Number(item.rating) || 0,
    tags: item.tags || [],
    author: item.author ? {
      name: item.author.name,
      bio: item.author.bio || '',
      avatar: item.author.avatar_url || '',
      slug: item.author.slug
    } : undefined
  }));
}

export async function fetchAuthors(): Promise<Author[]> {
  const raw = await fetchApi<Author[]>('/authors', {
    'sort': 'name:asc'
  });
  return raw.map(item => ({
    name: item.name,
    bio: item.bio,
    avatar: item.avatar_url,
    slug: item.slug
  }));
}

export async function fetchGames(): Promise<Game[]> {
  const raw = await fetchApi<Game[]>('/games', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    title: item.title,
    description: item.description,
    coverImage: item.cover_url,
    slug: item.slug,
    price: item.price,
    rating: item.rating
  }));
}

export async function fetchDirectoryItems(): Promise<DirectoryItem[]> {
  const raw = await fetchApi<DirectoryItem[]>('/directory-items', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
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
  const raw = await fetchApi<AiTool[]>('/ai-tools', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
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
  const raw = await fetchApi<Story[]>('/stories', {
    'filters[slug][$eq]': slug
  });
  const item = raw[0];
  return {
    title: item.title,
    excerpt: item.excerpt,
    coverImage: item.cover_url,
    slug: item.slug,
    publishDate: item.publishedAt,
    featured: Boolean(item.featured),
    sponsored: Boolean(item.is_sponsored),
    isAiEnhanced: Boolean(item.is_ai_enhanced),
    isSelfPublished: Boolean(item.is_self_published),
    needsModeration: Boolean(item.needs_moderation),
    rating: Number(item.rating) || 0,
    tags: item.tags || [],
    author: item.author ? {
      name: item.author.name,
      bio: item.author.bio || '',
      avatar: item.author.avatar_url || '',
      slug: item.author.slug
    } : undefined
  };
}

export async function fetchAuthor(slug: string): Promise<Author> {
  const raw = await fetchApi<Author[]>('/authors', {
    'filters[slug][$eq]': slug
  });
  const item = raw[0];
  return {
    name: item.name,
    bio: item.bio,
    avatar: item.avatar_url,
    slug: item.slug
  };
}

// Export fetchFromApi as an alias for backward compatibility
export const fetchFromApi = fetchApi;
