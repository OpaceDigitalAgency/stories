const STRAPI_URL = 'http://localhost:1337';

export const getMediaUrl = (url: string) => `${STRAPI_URL}${url}`;

export const fetchFromStrapi = async (endpoint: string, params = {}) => {
  const queryString = new URLSearchParams(params).toString();
  const url = `${STRAPI_URL}/api/${endpoint}${queryString ? `?${queryString}` : ''}`;
  const res = await fetch(url);
  return res.json();
};

// Strapi response interfaces
export interface StrapiResponse<T> {
  data: StrapiData<T>[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

export interface StrapiData<T> {
  id: number;
  attributes: T;
}

export interface StrapiImage {
  data: {
    id: number;
    attributes: {
      url: string;
      width: number;
      height: number;
      formats: {
        thumbnail: { url: string; width: number; height: number; };
        small: { url: string; width: number; height: number; };
        medium: { url: string; width: number; height: number; };
        large: { url: string; width: number; height: number; };
      };
    };
  };
}

export interface Story {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  cover: StrapiImage;
  author: StrapiResponse<Author>;
  tags: StrapiResponse<Tag>;
}

export interface Author {
  name: string;
  slug: string;
  bio: string;
  avatar: StrapiImage;
  stories: StrapiResponse<Story>;
}

export interface BlogPost {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  cover: StrapiImage;
  author: StrapiResponse<Author>;
}

export interface DirectoryItem {
  name: string;
  description: string;
  url: string;
  logo: StrapiImage;
  category: string;
}

export interface Game {
  title: string;
  description: string;
  url: string;
  thumbnail: StrapiImage;
  category: string;
}

export interface AiTool {
  name: string;
  description: string;
  url: string;
  logo: StrapiImage;
  category: string;
}

export interface Tag {
  name: string;
  slug: string;
}