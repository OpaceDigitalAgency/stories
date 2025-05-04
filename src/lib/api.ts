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
  const url = new URL(`${API_URL}${endpoint}`);
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.append(key, String(value));
  });
  return url.toString();
};

// Generic fetch function with error handling
export async function fetchApi<T>(endpoint: string, params: Record<string, string | number | boolean> = {}): Promise<T> {
  const url = buildUrl(endpoint, params);

  try {
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

// Define filter interface
interface StoryFilters {
  featured?: boolean;
  sponsored?: boolean;
  isSelfPublished?: boolean;
  isAiEnhanced?: boolean;
  sort?: string;
  'filters[tags][$contains]'?: string;
  'filters[tags][$containsi]'?: string;
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

  // Add filters
  Object.entries(filters).forEach(([key, value]) => {
    if (key.startsWith('filters[tags]')) {
      params[key] = value;
    } else if (key === 'featured' && value === true) {
      params['featured'] = 1;
    } else if (key === 'sponsored' && value === true) {
      params['is_sponsored'] = 1;
    } else if (key === 'isSelfPublished' && value === true) {
      params['is_self_published'] = 1;
    } else if (key === 'isAiEnhanced' && value === true) {
      params['is_ai_enhanced'] = 1;
    } else if (key === 'sort') {
      params['sort'] = value;
    }
  });

  console.log("Final API params:", JSON.stringify(params));

  // Add populate parameter to ensure we get content
  params['populate'] = '*';

  const raw = await fetchApi<any[]>('/stories', params);
  return raw.map(item => ({
    title: item.title,
    excerpt: item.excerpt || '',
    content: item.content || '',  // Include content field
    coverImage: item.cover_urls ? {
      default: item.cover_url || '',
      thumbnail: item.cover_urls.thumbnail || '',
      small: item.cover_urls.small || '',
      medium: item.cover_urls.medium || '',
      large: item.cover_urls.large || ''
    } : item.cover_url || '',
    slug: item.slug,
    publishDate: item.publishedAt || '',
    featured: Boolean(item.featured),
    sponsored: Boolean(item.is_sponsored),
    isAiEnhanced: Boolean(item.is_ai_enhanced),
    isSelfPublished: Boolean(item.is_self_published),
    needsModeration: Boolean(item.needs_moderation),
    is_published: Boolean(item.is_published),  // Include is_published field
    rating: Number(item.average_rating) > 0 ? Number(item.average_rating) : undefined,
    reviewCount: Number(item.review_count) || 0,
    source_type: item.source_type || 'child',
    allow_reviews: Boolean(item.allow_reviews),
    estimated_reading_time: item.estimated_reading_time || '1',  // Include reading time
    age_group: item.age_group || '7-12',  // Include age group
    tags: Array.isArray(item.tags) ? item.tags :
          (item.tags ? [String(item.tags)] : []),
    author: item.author ? {
      name: item.author.name,
      bio: item.author.bio || '',
      avatar: item.author.avatar_url || '',
      slug: item.author.slug,
      author_type: item.author.author_type || 'retail',
      age: item.author.age || null,  // Include author age
      location: item.author.location || null  // Include author location
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
    slug: item.slug,
    author_type: item.author_type || 'parent',
    featured: Boolean(item.featured)
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
    rating: Number(item.rating) || 0,
    category: item.category || 'General',
    ageRange: item.age_range || 'All Ages'
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

// Fetch stories by tag
export async function fetchStoriesByTag(tag: string): Promise<Story[]> {
  try {
    console.log(`Fetching stories with tag: ${tag}`);

    // Try with contains first
    let raw = await fetchApi<any[]>('/stories', {
      'filters[tags]': tag,
      'sort': 'publishedAt:desc',
      'populate': '*'
    });

    if (!raw || raw.length === 0) {
      console.log(`No stories found with tag: ${tag}`);
      return [];
    }

    return raw.map(item => ({
      title: item.title,
      excerpt: item.excerpt || '',
      content: item.content,
      coverImage: item.cover_urls ? {
        default: item.cover_url || '',
        thumbnail: item.cover_urls.thumbnail || '',
        small: item.cover_urls.small || '',
        medium: item.cover_urls.medium || '',
        large: item.cover_urls.large || ''
      } : item.cover_url || '',
      slug: item.slug,
      publishDate: item.publishedAt || '',
      featured: Boolean(item.featured),
      sponsored: Boolean(item.is_sponsored),
      isAiEnhanced: Boolean(item.is_ai_enhanced),
      isSelfPublished: Boolean(item.is_self_published),
      needsModeration: Boolean(item.needs_moderation),
      is_published: Boolean(item.is_published),
      rating: Number(item.average_rating) || 0,
      reviewCount: Number(item.review_count) || 0,
      source_type: item.source_type || 'child',
      allow_reviews: Boolean(item.allow_reviews),
      estimated_reading_time: item.estimated_reading_time || '1',
      age_group: item.age_group || '7-12',
      tags: Array.isArray(item.tags) ? item.tags :
            (item.tags ? [String(item.tags)] : []),
      author: item.author ? {
        name: item.author.name,
        bio: item.author.bio || '',
        avatar: item.author.avatar_url || '',
        slug: item.author.slug,
        author_type: item.author.author_type || 'retail',
        age: item.author.age || null,
        location: item.author.location || null
      } : undefined
    }));
  } catch (error) {
    console.error(`Error fetching stories with tag ${tag}:`, error);
    throw error; // Re-throw to allow the caller to handle the error
  }
}

// Single item fetch functions
export async function fetchStory(slug: string): Promise<Story | null> {
  try {
    const raw = await fetchApi<any[]>('/stories', {
      'filters[slug][$eq]': slug,
      'populate': '*'  // Ensure we get all fields including content
    });

    // Check if we got any results
    if (!raw || raw.length === 0) {
      console.error(`No story found with slug: ${slug}`);
      return null;
    }

    const item = raw[0];
    return {
      title: item.title,
      excerpt: item.excerpt || '',
      content: item.content || '',
      coverImage: item.cover_urls ? {
        default: item.cover_url || '',
        thumbnail: item.cover_urls.thumbnail || '',
        small: item.cover_urls.small || '',
        medium: item.cover_urls.medium || '',
        large: item.cover_urls.large || ''
      } : item.cover_url || '',
      slug: item.slug,
      publishDate: item.publishedAt || '',
      featured: Boolean(item.featured),
      sponsored: Boolean(item.is_sponsored),
      isAiEnhanced: Boolean(item.is_ai_enhanced),
      isSelfPublished: Boolean(item.is_self_published),
      needsModeration: Boolean(item.needs_moderation),
      is_published: Boolean(item.is_published),
      rating: Number(item.average_rating) || 0,
      reviewCount: Number(item.review_count) || 0,
      source_type: item.source_type || 'child',
      allow_reviews: Boolean(item.allow_reviews),
      estimated_reading_time: item.estimated_reading_time || '1',
      age_group: item.age_group || '7-12',
      tags: Array.isArray(item.tags) ? item.tags :
            (item.tags ? [String(item.tags)] : []),
      author: item.author ? {
        name: item.author.name,
        bio: item.author.bio || '',
        avatar: item.author.avatar_url || '',
        slug: item.author.slug,
        author_type: item.author.author_type || 'retail',
        age: item.author.age || null,
        location: item.author.location || null
      } : undefined
    };
  } catch (error) {
    console.error(`Error fetching story with slug ${slug}:`, error);
    return null;
  }
}

export async function fetchAuthor(slug: string): Promise<Author | null> {
  try {
    const raw = await fetchApi<any[]>('/authors', {
      'filters[slug][$eq]': slug
    });

    // Check if we got any results
    if (!raw || raw.length === 0) {
      console.error(`No author found with slug: ${slug}`);
      return null;
    }

    const item = raw[0];
    return {
      name: item.name,
      bio: item.bio || '',
      avatar: item.avatar_url || '',
      slug: item.slug,
      author_type: item.author_type || 'parent',
      featured: Boolean(item.featured),
      age: item.age || null,
      location: item.location || null
    };
  } catch (error) {
    console.error(`Error fetching author with slug ${slug}:`, error);
    return null;
  }
}

// Fetch blog posts
export async function fetchBlogPosts(page = 1, limit = 10, filters: Record<string, any> = {}): Promise<any[]> {
  // Default parameters
  const params: Record<string, string | number | boolean> = {
    'pagination[limit]': limit,
    'pagination[page]': page
  };

  // Set default sort if not specified in filters
  if (!filters.sort) {
    params['sort'] = 'created_at:desc';
  } else {
    params['sort'] = filters.sort;
  }

  // Add any additional filters
  Object.entries(filters).forEach(([key, value]) => {
    if (key !== 'sort') {
      params[key] = value;
    }
  });

  console.log("Fetching blog posts with params:", JSON.stringify(params));

  try {
    const raw = await fetchApi<any[]>('/blog-posts', params);
    return raw.map(item => ({
      id: item.id,
      title: item.title,
      excerpt: item.excerpt || '',
      content: item.content || '',
      coverImage: item.cover_url || '',
      slug: item.slug,
      publishDate: item.created_at || '',
      author: item.author_id ? {
        id: item.author_id,
        name: 'Author ' + item.author_id, // This will be replaced with actual author data
        slug: 'author-' + item.author_id,
        avatar: '/images/default-avatar.svg'
      } : undefined
    }));
  } catch (error) {
    console.error("Error fetching blog posts:", error);
    return [];
  }
}

// Fetch tags
export async function fetchTags(): Promise<any[]> {
  try {
    const raw = await fetchApi<any[]>('/tags', {
      'sort': 'name:asc'
    });
    return raw.map(item => ({
      id: item.id,
      name: item.name,
      slug: item.slug || item.name.toLowerCase().replace(/\s+/g, '-')
    }));
  } catch (error) {
    console.error('Error fetching tags:', error);
    return [];
  }
}

// Export fetchFromApi as an alias for backward compatibility
export const fetchFromApi = fetchApi;
