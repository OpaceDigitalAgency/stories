import type { Story, Author, Tag, BlogPost, DirectoryItem, Game, AiTool, ApiResponse, ApiData } from './api';

// Helper function to wrap data in API response format
const wrapResponse = <T>(data: ApiData<T>[], page = 1, pageSize = 25): ApiResponse<T> => ({
  data,
  meta: {
    pagination: {
      page,
      pageSize,
      pageCount: Math.ceil(data.length / pageSize),
      total: data.length
    }
  }
});

// Helper function to wrap attributes in API data format
const wrapData = <T>(id: number, attributes: T): ApiData<T> => ({
  id,
  attributes
});

// Create base image formats
const createImageFormats = (url: string) => ({
  thumbnail: { url, width: 50, height: 50 },
  small: { url, width: 100, height: 100 },
  medium: { url, width: 150, height: 150 },
  large: { url, width: 200, height: 200 }
});

// Create base image data
const createImage = (id: number, url: string) => ({
  data: {
    id,
    attributes: {
      url,
      width: 1920,
      height: 1080,
      formats: createImageFormats(url)
    }
  }
});

// Create base tags
const baseTags = [
  { name: "Fantasy", slug: "fantasy" },
  { name: "Nature", slug: "nature" },
  { name: "Adventure", slug: "adventure" },
  { name: "Technology", slug: "technology" },
  { name: "Friendship", slug: "friendship" }
].map((tag, index) => wrapData(index + 1, tag));

export const mockTags = wrapResponse(baseTags);

// Create base stories with proper Strapi structure
const createBaseStory = (
  id: number,
  title: string,
  slug: string,
  authorId: number,
  authorName: string,
  authorSlug: string,
  imageUrl: string,
  tagIds: number[],
  featured: boolean = false,
  averageRating: number = 4.5,
  publishedAt: string = new Date().toISOString()
): ApiData<Story> => ({
  id,
  attributes: {
    title,
    slug,
    content: `Content for ${title}...`,
    excerpt: `Excerpt for ${title}...`,
    publishedAt,
    featured,
    averageRating,
    cover: createImage(id, imageUrl),
    author: wrapResponse([wrapData(authorId, {
      name: authorName,
      slug: authorSlug,
      bio: `Bio for ${authorName}`,
      avatar: createImage(authorId, `https://i.pravatar.cc/150?img=${authorId}`),
      stories: wrapResponse([]) // Empty response with meta
    })]),
    tags: wrapResponse(tagIds.map(tagId => baseTags[tagId - 1]))
  }
});

// Create stories
const baseStories = [
  createBaseStory(
    1,
    "The Enchanted Forest",
    "enchanted-forest",
    1,
    "David Brown",
    "david-brown",
    "https://images.unsplash.com/photo-1448375240586-882707db888b",
    [1, 2, 3],
    true, // featured
    4.8,
    "2025-04-10T12:00:00.000Z"
  ),
  createBaseStory(
    2,
    "The Little Robot's Big Day",
    "little-robot-big-day",
    2,
    "Jennifer Lee",
    "jennifer-lee",
    "https://images.unsplash.com/photo-1485827404703-89b55fcc595e",
    [4, 5],
    false,
    4.2,
    "2025-04-05T12:00:00.000Z"
  ),
  createBaseStory(
    3,
    "Space Explorers",
    "space-explorers",
    1,
    "David Brown",
    "david-brown",
    "https://images.unsplash.com/photo-1446776811953-b23d57bd21aa",
    [1, 4],
    true,
    4.9,
    "2025-04-12T12:00:00.000Z"
  )
];

export const mockStories = wrapResponse(baseStories);

// Create authors with proper story references
export const mockAuthors = wrapResponse([
  wrapData(1, {
    name: "David Brown",
    slug: "david-brown",
    bio: "Children's book author and nature enthusiast",
    avatar: baseStories[0].attributes.author.data[0].attributes.avatar,
    stories: wrapResponse([baseStories[0], baseStories[2]])
  }),
  wrapData(2, {
    name: "Jennifer Lee",
    slug: "jennifer-lee",
    bio: "Tech enthusiast and storyteller",
    avatar: baseStories[1].attributes.author.data[0].attributes.avatar,
    stories: wrapResponse([baseStories[1]])
  })
]);

// Create blog posts
export const mockBlogPosts = wrapResponse([
  wrapData(1, {
    title: "Writing Tips for Young Authors",
    slug: "writing-tips-young-authors",
    content: "Here are some helpful tips for young writers...",
    excerpt: "Learn the essential writing tips that will help you create engaging stories for young readers.",
    publishedAt: "2025-04-01T12:00:00.000Z",
    cover: baseStories[0].attributes.cover,
    author: wrapResponse([mockAuthors.data[0]])
  })
]);

// Create directory items
export const mockDirectoryItems = wrapResponse([
  wrapData(1, {
    name: "Local Library",
    description: "Find books and resources at your local library",
    url: "https://library.example.com",
    logo: baseStories[0].attributes.cover,
    category: "Resources"
  })
]);

// Create games
export const mockGames = wrapResponse([
  wrapData(1, {
    title: "Word Adventure",
    description: "A fun word game for young readers",
    url: "/games/word-adventure",
    thumbnail: baseStories[0].attributes.cover,
    category: "Educational"
  })
]);

// Create AI tools
export const mockAiTools = wrapResponse([
  wrapData(1, {
    name: "Story Helper",
    description: "AI-powered writing assistant for young authors",
    url: "/ai-tools/story-helper",
    logo: baseStories[0].attributes.cover,
    category: "Writing"
  })
]);