// Base API URL
const API_URL = 'https://api.storiesfromtheweb.org/api/v1';

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
  rating?: number;
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

    // Apply filters directly as query parameters
    // This matches the backend API implementation
    if (filters.featured === true) {
      params['featured'] = 1;
      console.log("Setting featured=1 filter");
    }

    // Map 'sponsored' to 'is_sponsored' for the API
    if (filters.sponsored === true) {
      params['is_sponsored'] = 1;
      console.log("Setting is_sponsored=1 filter");
    }

    if (filters.isSelfPublished === true) {
      params['is_self_published'] = 1;
      console.log("Setting is_self_published=1 filter");
    }

    if (filters.isAiEnhanced === true) {
      params['is_ai_enhanced'] = 1;
      console.log("Setting is_ai_enhanced=1 filter");
    }

    // Log the final params for debugging
    console.log("Final API params:", JSON.stringify(params));
  } catch (error) {
    console.error("Error applying filters:", error);
  }

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
    rating: Number(item.average_rating) || 0,
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

// Fetch stories by tag
export async function fetchStoriesByTag(tag: string): Promise<Story[]> {
  try {
    console.log(`Fetching stories with tag: ${tag}`);

    // Try with contains first
    let raw = await fetchApi<any[]>('/stories', {
      'filters[tags][$contains]': tag,
      'sort': 'publishedAt:desc',
      'populate': '*'
    });

    // If no results, try with containsi (case insensitive)
    if (!raw || raw.length === 0) {
      console.log(`No stories found with tag using contains, trying containsi: ${tag}`);
      raw = await fetchApi<any[]>('/stories', {
        'filters[tags][$containsi]': tag,
        'sort': 'publishedAt:desc',
        'populate': '*'
      });
    }

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

// Export fetchFromApi as an alias for backward compatibility
export const fetchFromApi = fetchApi;
