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
  /** Rating value (0-5) */
  rating?: number;
  /** Number of reviews */
  reviewCount?: number;
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

export interface PartnerBadgesProps {
  /** Array of partner data */
  partners: Array<{
    name: string;
    href: string;
    logo: {
      url: string;
      alternativeText?: string;
    };
  }>;
  /** Optional class names to apply */
  className?: string;
}
export interface AIRecommendationBoxProps {
  /** The recommended story data (optional with default) */
  story?: {
    title: string;
    slug: string;
    author: {
      name: string;
    };
  };
  /** Recommendation text (optional with default) */
  recommendationText?: string;
  /** Optional class names to apply */
  className?: string;
}

export interface SignUpPromptsProps {
  /** The type of prompt to display */
  type: 'author' | 'reader' | 'educator';
  /** Optional class names to apply */
  className?: string;
}

export interface EducatorSectionProps {
  /** The educator data */
  educator: {
    name: string;
    title?: string;
    institution?: string;
    avatar?: {
      url: string;
    };
  };
  /** Optional class names to apply */
  className?: string;
}
