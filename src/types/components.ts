/**
 * Common interfaces for story-related data from Strapi
 */
export interface StrapiImage {
  url: string;
  alternativeText?: string;
  width: number;
  height: number;
}

export interface StrapiAuthor {
  id: number;
  name: string;
  bio?: string;
  avatar?: StrapiImage;
  slug: string;
}

export interface StrapiStory {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  content: string;
  coverImage?: StrapiImage;
  author: StrapiAuthor;
  publishedAt: string;
  tags: string[];
  rating?: number;
  readingTime?: number;
}

/**
 * Component Props Interfaces
 */

export interface CardStoryProps {
  /** The story data from Strapi */
  story: StrapiStory;
  /** Optional class names to apply to the card */
  className?: string;
}

export interface CardAuthorProps {
  /** The author data from Strapi */
  author: StrapiAuthor;
  /** Optional class names to apply to the card */
  className?: string;
}

export interface TagBadgeProps {
  /** The tag text to display */
  tag: string;
  /** Optional class names to apply to the badge */
  className?: string;
}

export interface RatingStarsProps {
  /** The rating value (0-5) */
  rating: number;
  /** Optional class names to apply to the container */
  className?: string;
}

export interface AIRecommendationBoxProps {
  /** The recommended story data */
  story: StrapiStory;
  /** The AI-generated recommendation text */
  recommendationText: string;
  /** Optional class names to apply to the box */
  className?: string;
}

export interface EducatorSectionProps {
  /** The educator's profile data */
  educator: {
    name: string;
    title: string;
    institution: string;
    avatar?: StrapiImage;
  };
  /** Optional class names to apply to the section */
  className?: string;
}

export interface PartnerBadgesProps {
  /** Array of partner data */
  partners: Array<{
    name: string;
    logo: StrapiImage;
    url: string;
  }>;
  /** Optional class names to apply to the container */
  className?: string;
}

export interface StoryCarouselProps {
  /** Array of stories to display */
  stories: StrapiStory[];
  /** Optional title for the carousel */
  title?: string;
  /** Optional class names to apply to the container */
  className?: string;
}

export interface SignUpPromptsProps {
  /** The type of prompt to show */
  type: 'author' | 'reader' | 'educator';
  /** Optional class names to apply to the container */
  className?: string;
}

export interface ReviewSectionProps {
  /** The story being reviewed */
  story: StrapiStory;
  /** Array of reviews */
  reviews: Array<{
    id: number;
    author: StrapiAuthor;
    content: string;
    rating: number;
    createdAt: string;
  }>;
  /** Optional class names to apply to the section */
  className?: string;
}

export interface SponsoredBadgeProps {
  /** Optional sponsor name */
  sponsor?: string;
  /** Optional class names to apply to the badge */
  className?: string;
}