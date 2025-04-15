/**
 * Common interfaces for story-related data from Strapi
 */
export interface StrapiImage {
  data?: {
    attributes: {
      url: string;
      alternativeText?: string;
      width: number;
      height: number;
      formats?: {
        thumbnail?: { url: string; width: number; height: number; };
        small?: { url: string; width: number; height: number; };
        medium?: { url: string; width: number; height: number; };
        large?: { url: string; width: number; height: number; };
      };
    };
  };
}

export interface StrapiAuthor {
  data?: {
    id: number;
    attributes: {
      name: string;
      bio?: string;
      avatar?: StrapiImage;
      slug: string;
      storyCount?: number;
      twitter?: string;
      instagram?: string;
      website?: string;
    };
  };
}

export interface StrapiTag {
  data?: Array<{
    id: number;
    attributes: {
      name: string;
      slug: string;
    };
  }>;
}

export interface StrapiStory {
  id: number;
  attributes: {
    title: string;
    slug: string;
    excerpt?: string;
    content: string;
    cover?: StrapiImage;
    author?: StrapiAuthor;
    publishedAt: string;
    tags?: StrapiTag;
    averageRating?: number;
    reviewCount?: number;
    estimatedReadingTime?: string;
    isSponsored?: boolean;
    ageGroup?: string;
    needsModeration?: boolean;
    featured?: boolean;
    isSelfPublished?: boolean;
    isAIEnhanced?: boolean;
  };
}

/**
 * Component Props Interfaces
 */

export interface CardStoryProps {
  /** The story data */
  story: {
    title: string;
    slug: string;
    excerpt?: string;
    coverImage?: string;
    author?: {
      name: string;
      avatar?: string;
      slug: string;
    };
    rating?: number;
    tags?: string[];
    publishDate?: Date;
  };
  /** Optional class names to apply to the card */
  className?: string;
}

export interface CardAuthorProps {
  /** The author data */
  author: {
    name: string;
    avatar?: string;
    bio?: string;
    slug: string;
    storyCount?: number;
    socialLinks?: {
      twitter?: string;
      instagram?: string;
      website?: string;
    };
  };
  /** Optional class names to apply to the card */
  className?: string;
}

export interface TagBadgeProps {
  /** The tag text to display */
  tag: string;
  /** Optional size variant */
  size?: 'sm' | 'md' | 'lg';
  /** Optional class names to apply to the badge */
  className?: string;
}

export interface RatingStarsProps {
  /** The rating value (0-5) */
  rating: number;
  /** Optional size variant */
  size?: 'sm' | 'md' | 'lg';
  /** Optional class names to apply to the container */
  className?: string;
}

export interface StoryCarouselProps {
  /** Title for the carousel */
  title: string;
  /** Link for "View All" button */
  viewAllLink: string;
  /** Array of stories to display */
  stories: CardStoryProps['story'][];
  /** Unique ID for the carousel */
  carouselId: string;
  /** Optional class names to apply to the container */
  className?: string;
}

export interface SponsoredCarouselProps {
  /** Title for the carousel */
  title: string;
  /** Link for "View All" button */
  viewAllLink: string;
  /** Array of sponsored stories */
  stories: CardStoryProps['story'][];
  /** Unique ID for the carousel */
  carouselId: string;
  /** Optional class names to apply to the container */
  className?: string;
}

export interface ReviewSectionProps {
  /** Type of content being reviewed */
  itemType: 'story' | 'author';
  /** ID of the content */
  itemId: number | string;
  /** Name of the content */
  itemName: string;
  /** Optional class names to apply to the section */
  className?: string;
}

export interface SponsoredBadgeProps {
  /** Type of badge */
  type: 'sponsored' | 'featured';
  /** Optional class names to apply to the badge */
  className?: string;
}

export interface ModerationCTAProps {
  /** Type of content */
  contentType: 'story' | 'author' | 'comment';
  /** ID of the content */
  contentId: number | string;
  /** Reason for moderation */
  reason: string;
  /** Optional class names to apply */
  className?: string;
}