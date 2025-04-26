// Base API URL
const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

// Type definitions
export interface Story {
  title: string;
  excerpt: string;
  coverImage: string;
  cover_url?: string; // API response field
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
  rating?: number;
  tags?: string[];
  author?: Author;
}

export interface Author {
  name: string;
  bio: string;
  avatar: string;
  avatar_url?: string; // API response field
  slug: string;
}

export interface Game {
  title: string;
  description: string;
  coverImage: string;
  cover_url?: string; // API response field
  slug: string;
  price: number;
  rating: number;
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

// Define filter interface
interface StoryFilters {
  featured?: boolean;
  sponsored?: boolean;
  isSelfPublished?: boolean;
  isAiEnhanced?: boolean;
  sort?: string;
}

// Resource-specific fetch functions with proper mapping
export async function fetchStories(page = 1, limit = 10, filters: StoryFilters = {}): Promise<Story[]> {
  // Default parameters
  const params: Record<string, string | number | boolean> = {
    'pagination[limit]': limit,
    'pagination[page]': page
  };
  
  // Set default sort if not specified in filters
  if (!filters.sort) {
    params['sort'] = 'publishedAt:desc';
  } else {
    params['sort'] = filters.sort;
  }
  
  // Add filters only if they're defined
  // Using a more lenient approach to avoid breaking the API
  try {
    console.log("Applying filters:", JSON.stringify(filters));
    
    // Only apply filters if they're explicitly set to true
    // This prevents filtering out stories when the filter isn't explicitly true
    if (filters.featured === true) {
      params['filters[featured][$eq]'] = true;
    }
    
    if (filters.sponsored === true) {
      params['filters[is_sponsored][$eq]'] = true;
    }
    
    if (filters.isSelfPublished === true) {
      params['filters[is_self_published][$eq]'] = true;
    }
    
    if (filters.isAiEnhanced === true) {
      params['filters[is_ai_enhanced][$eq]'] = true;
    }
  } catch (error) {
    console.error("Error applying filters:", error);
  }
  
  const raw = await fetchApi<any[]>('/stories', params);
  return raw.map(item => ({
    title: item.title,
    excerpt: item.excerpt,
    coverImage: item.cover_url || '',
    slug: item.slug,
    publishDate: item.publishedAt || '',
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
  const raw = await fetchApi<any[]>('/authors', {
    'sort': 'name:asc'
  });
  return raw.map(item => ({
    name: item.name,
    bio: item.bio || '',
    avatar: item.avatar_url || '',
    slug: item.slug
  }));
}

export async function fetchGames(): Promise<Game[]> {
  const raw = await fetchApi<any[]>('/games', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    title: item.title,
    description: item.description || '',
    coverImage: item.cover_url || '',
    slug: item.slug,
    price: Number(item.price) || 0,
    rating: Number(item.rating) || 0
  }));
}

export async function fetchDirectoryItems(): Promise<DirectoryItem[]> {
  const raw = await fetchApi<any[]>('/directory-items', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    title: item.title,
    description: item.description || '',
    coverImage: item.cover_url || '',
    slug: item.slug,
    category: item.category || '',
    rating: Number(item.rating) || 0,
    priceRange: item.price_range || ''
  }));
}

export async function fetchAiTools(): Promise<AiTool[]> {
  const raw = await fetchApi<any[]>('/ai-tools', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    title: item.title,
    description: item.description || '',
    coverImage: item.cover_url || '',
    slug: item.slug,
    category: item.category || '',
    pricingType: item.pricing_type || '',
    featured: Boolean(item.featured)
  }));
}

// Single item fetch functions
export async function fetchStory(slug: string): Promise<Story> {
  const raw = await fetchApi<any[]>('/stories', {
    'filters[slug][$eq]': slug
  });
  const item = raw[0];
  return {
    title: item.title,
    excerpt: item.excerpt || '',
    coverImage: item.cover_url || '',
    slug: item.slug,
    publishDate: item.publishedAt || '',
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
  const raw = await fetchApi<any[]>('/authors', {
    'filters[slug][$eq]': slug
  });
  const item = raw[0];
  return {
    name: item.name,
    bio: item.bio || '',
    avatar: item.avatar_url || '',
    slug: item.slug
  };
}

// Export fetchFromApi as an alias for backward compatibility
export const fetchFromApi = fetchApi;
