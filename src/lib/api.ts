// Base API URL from environment variable
const API_URL = import.meta.env.PUBLIC_API_URL;
if (!API_URL) {
  throw new Error('PUBLIC_API_URL environment variable is not set');
}

// Assets URL from environment variable
const ASSETS_URL = import.meta.env.PUBLIC_ASSETS_URL || '/images';

// Type definitions
// Helper function to build asset URLs
export const getAssetUrl = (path: string): string => {
  if (!path) return '';
  // Remove leading slash if present to avoid double slashes
  const cleanPath = path.startsWith('/') ? path.slice(1) : path;
  // Replace spaces with dashes and encode the filename
  const encodedPath = cleanPath.replace(/\s+/g, '-').toLowerCase();
  return `${ASSETS_URL}/${encodedPath}`;
};

export interface CoverImageUrls {
  default: string;
  thumbnail?: string;
  medium?: string;
  large?: string;
}

export interface Story {
  title: string;
  excerpt: string;
  content?: string;
  coverImage: string;
  cover_url?: string;
  cover_urls?: CoverImageUrls;
  slug: string;
  publishDate: string;
  publishedAt?: string;
  featured?: boolean;
  sponsored?: boolean;
  is_sponsored?: boolean;
  isAiEnhanced?: boolean;
  is_ai_enhanced?: boolean;
  isSelfPublished?: boolean;
  is_self_published?: boolean;
  needsModeration?: boolean;
  needs_moderation?: boolean;
  is_published?: boolean;
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
  avatar_url?: string;
  slug: string;
  author_type?: 'retail' | 'parent' | 'child' | 'educator';
  featured?: boolean;
  age?: number | string | null;
  location?: string | null;
  id?: number;
  storyCount?: number;
  joinDate?: string;
  twitter_url?: string;
  instagram_url?: string;
  website_url?: string;
  socialLinks?: {
    twitter?: string;
    instagram?: string;
    website?: string;
  };
}

export interface CoverImageUrls {
  default: string;
  thumbnail?: string;
  medium?: string;
  large?: string;
}

export interface Game {
  title: string;
  description: string;
  coverImage: string | CoverImageUrls;
  cover_url?: string;
  cover_urls?: CoverImageUrls;
  slug: string;
  price: number;
  rating: number;
  category: string;
  ageRange: string;
  featured?: boolean;
  website_url?: string;
  platform?: string;
  developer?: string;
  publisher?: string;
  release_date?: string | null;
  is_published?: boolean;
  genre?: string;
}

export interface DirectoryItem {
  name: string;
  description: string;
  logo: string;
  url?: string;
  slug: string;
  category: string;
  rating: number;
  priceRange: string;
  contactEmail?: string;
  contactPhone?: string;
  address?: string;
  featured: boolean;
  isPublished: boolean;
}

export interface AiTool {
  name: string;
  description: string;
  logo: string;
  url?: string;
  slug: string;
  category: string;
  pricingType: string;
  priceInfo?: string;
  features?: string;
  rating?: number;
  featured: boolean;
  isPublished: boolean;
}

// Helper function to build URL with query parameters
export const buildUrl = (endpoint: string, params: Record<string, string | number | boolean> = {}) => {
  // Remove leading slash if present to avoid double slashes
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
  const url = new URL(`${API_URL}/${cleanEndpoint}`);
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.append(key, String(value));
  });
  return url.toString();
};

// Generic fetch function with enhanced error handling and retries
export async function fetchApi<T>(
  endpoint: string,
  params: Record<string, string | number | boolean> = {},
  retries = 2
): Promise<T> {
  const url = buildUrl(endpoint, params);

  try {
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache'
      }
    });

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`API error (${response.status}): ${errorText || response.statusText}`);
    }

    const data = await response.json();
    return data;
  } catch (error) {
    console.error(`API request failed for ${url}:`, error);

    // Retry logic for network errors
    if (retries > 0 && error instanceof Error && error.message.includes('fetch')) {
      console.log(`Retrying... (${retries} attempts left)`);
      return fetchApi(endpoint, params, retries - 1);
    }

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
  authorId?: number;
}

// Resource-specific fetch functions with proper mapping
// Helper function to map story response
function mapStoryResponse(item: any): Story {
  return {
    title: item.title,
    excerpt: item.excerpt || '',
    content: item.content || '',
    coverImage: item.cover_urls || item.cover_url || '',
    cover_urls: item.cover_urls,
    slug: item.slug,
    publishDate: item.publishedAt || '',
    featured: Boolean(item.featured),
    sponsored: Boolean(item.is_sponsored),
    isAiEnhanced: Boolean(item.is_ai_enhanced),
    isSelfPublished: Boolean(item.is_self_published),
    needsModeration: Boolean(item.needs_moderation),
    is_published: Boolean(item.is_published),
    rating: Number(item.average_rating) > 0 ? Number(item.average_rating) : undefined,
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
}

export async function fetchStories(page = 1, limit = 10, filters: StoryFilters = {}): Promise<Story[]> {
  try {
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
      }
    });

    // Fetch stories
    const stories = await fetchApi<any[]>('/stories', params);

    // If no stories found, return empty array
    if (!stories || !Array.isArray(stories)) {
      console.error('No stories found or invalid response');
      return [];
    }

    // If authorId is provided in filters, filter by author
    const filteredStories = filters.authorId
      ? stories.filter(story => story.author && Number(story.author.id) === filters.authorId)
      : stories;

    // Map stories to correct format
    return filteredStories.map(mapStoryResponse);
  } catch (error) {
    console.error('Error fetching stories:', error);
    return [];
  }
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
  try {
    const raw = await fetchApi<any[]>('/games', {
      'sort': 'created_at:desc',
      'populate': '*'
    });

    if (!raw || !Array.isArray(raw)) {
      console.error('Invalid games data received');
      return [];
    }

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
  } catch (error) {
    console.error('Error fetching games:', error);
    return [];
  }
}

export async function fetchDirectoryItem(slug: string): Promise<DirectoryItem | null> {
  try {
    const raw = await fetchApi<any>(`/directory-items/by-slug/${slug}`);

    if (!raw) {
      console.error(`No directory item found with slug: ${slug}`);
      return null;
    }
    return {
      name: raw.name,
      description: raw.description || '',
      logo: raw.logo || '',
      url: raw.url,
      slug: raw.slug,
      category: raw.category || '',
      rating: Number(raw.rating) || 0,
      priceRange: raw.priceRange || '',
      contactEmail: raw.contactEmail,
      contactPhone: raw.contactPhone,
      address: raw.address,
      featured: Boolean(raw.featured),
      isPublished: Boolean(raw.isPublished)
    };
  } catch (error) {
    console.error(`Error fetching directory item with slug ${slug}:`, error);
    return null;
  }
}

export async function fetchAiTool(slug: string): Promise<AiTool | null> {
  try {
    const raw = await fetchApi<any>(`/ai-tools/by-slug/${slug}`);

    if (!raw) {
      console.error(`No AI tool found with slug: ${slug}`);
      return null;
    }
    return {
      name: raw.name,
      description: raw.description || '',
      logo: raw.logo || '',
      url: raw.url,
      slug: raw.slug,
      category: raw.category || '',
      pricingType: raw.pricingType || '',
      priceInfo: raw.priceInfo,
      features: raw.features,
      rating: raw.rating,
      featured: Boolean(raw.featured),
      isPublished: Boolean(raw.isPublished)
    };
  } catch (error) {
    console.error(`Error fetching AI tool with slug ${slug}:`, error);
    return null;
  }
}

export async function fetchGame(slug: string): Promise<Game | null> {
  try {
    const raw = await fetchApi<any[]>('/games');
    
    // Find game with matching slug
    const item = raw.find(game => game.slug === slug);
    
    if (!item) {
      console.error(`No game found with slug: ${slug}`);
      return null;
    }

    return {
      title: item.title,
      description: item.description || '',
      coverImage: item.cover_url || '',
      slug: item.slug,
      price: Number(item.price) || 0,
      rating: Number(item.rating) || 0,
      category: item.genre || 'General',
      ageRange: item.age_range || 'All Ages',
      featured: Boolean(item.featured),
      website_url: item.website_url || '',
      platform: item.platform || '',
      developer: item.developer || '',
      publisher: item.publisher || '',
      release_date: item.release_date || null,
      is_published: Boolean(item.is_published)
    };
  } catch (error) {
    console.error(`Error fetching game with slug ${slug}:`, error);
    return null;
  }
}

export async function fetchDirectoryItems(): Promise<DirectoryItem[]> {
  const raw = await fetchApi<any[]>('/directory-items', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    name: item.name,
    description: item.description || '',
    logo: item.logo || '',
    url: item.url,
    slug: item.slug,
    category: item.category || '',
    rating: Number(item.rating) || 0,
    priceRange: item.priceRange || '',
    contactEmail: item.contactEmail,
    contactPhone: item.contactPhone,
    address: item.address,
    featured: Boolean(item.featured),
    isPublished: Boolean(item.isPublished)
  }));
}

export async function fetchAiTools(): Promise<AiTool[]> {
  const raw = await fetchApi<any[]>('/ai-tools', {
    'sort': 'created_at:desc'
  });
  return raw.map(item => ({
    name: item.name,
    description: item.description || '',
    logo: item.logo || '',
    url: item.url,
    slug: item.slug,
    category: item.category || '',
    pricingType: item.pricingType || '',
    priceInfo: item.priceInfo,
    features: item.features,
    rating: item.rating,
    featured: Boolean(item.featured),
    isPublished: Boolean(item.isPublished)
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
      coverImage: item.cover_url || '',
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
    const raw = await fetchApi<any[]>('/stories');
    
    // Find story with matching slug
    const item = raw.find(story => story.slug === slug);
    
    if (!item) {
      console.error(`No story found with slug: ${slug}`);
      return null;
    }

    return {
      title: item.title,
      excerpt: item.excerpt || '',
      content: item.content || '',
      coverImage: item.cover_url || '',
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
    const raw = await fetchApi<any[]>('/authors');
    
    // Find author with matching slug
    const item = raw.find(author => author.slug === slug);
    
    if (!item) {
      console.error(`No author found with slug: ${slug}`);
      return null;
    }

    // Map social links
    const socialLinks = {
      twitter: item.twitter_url || undefined,
      instagram: item.instagram_url || undefined,
      website: item.website_url || undefined
    };

    // Only include socialLinks if at least one social URL exists
    const hasSocialLinks = Object.values(socialLinks).some(v => v !== undefined);

    return {
      name: item.name,
      bio: item.bio || '',
      avatar: item.avatar_url || '',
      slug: item.slug,
      author_type: item.author_type || 'parent',
      featured: Boolean(item.featured),
      age: item.age || null,
      location: item.location || null,
      id: Number(item.id) || undefined,
      storyCount: Number(item.story_count) || 0,
      joinDate: item.join_date || item.created_at || new Date().toISOString(),
      twitter_url: item.twitter_url || '',
      instagram_url: item.instagram_url || '',
      website_url: item.website_url || '',
      socialLinks: hasSocialLinks ? socialLinks : undefined
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

// Category interface
export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  count?: number;
}

// Fetch categories
export async function fetchCategories(): Promise<Category[]> {
  try {
    const raw = await fetchApi<any[]>('/categories', {
      'sort': 'name:asc'
    });

    return raw.map(item => ({
      id: item.id,
      name: item.name || 'Uncategorized',
      slug: item.slug || 'uncategorized',
      description: item.description || '',
      count: Number(item.count) || 0
    }));
  } catch (error) {
    console.error("Error fetching categories:", error);
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
