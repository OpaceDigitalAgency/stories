
/**
 * Simple wrapper around fetch() for your flat JSON API.
 * Never touches `window` or `document`, so it's safe in SSR.
 */
export async function fetchFromApi<T = any>(
  endpoint: string,
  params: Record<string, string | number | boolean> = {}
): Promise<T> {
  // Build the URL
  const base = import.meta.env.PUBLIC_API_URL;
  if (!base) {
    throw new Error('PUBLIC_API_URL is not defined');
  }
  const url = new URL(`${base}/${endpoint}`);

  // Append query params
  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.set(key, String(value));
  });

  // Perform the request
  const res = await fetch(url.toString());
  if (!res.ok) {
    const text = await res.text().catch(() => res.statusText);
    throw new Error(`API error ${res.status}: ${text}`);
  }
  return (await res.json()) as T;
}




// Define API response types

/* export interface ApiResponse<T> {
  data: ApiData<T>[];
  meta: {
    pagination: {
      page: number;
      pageSize: number;
      pageCount: number;
      total: number;
    };
  };
}

export interface ApiData<T> {
  id: number;
  attributes: T;
}

export interface ApiImage {
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
  featured: boolean;
  averageRating: number;
  cover: ApiImage;
  author: ApiResponse<Author>;
  tags: ApiResponse<Tag>;
}

export interface Author {
  name: string;
  slug: string;
  bio: string;
  avatar: ApiImage;
  stories: ApiResponse<Story>;
}

export interface BlogPost {
  title: string;
  slug: string;
  content: string;
  excerpt: string;
  publishedAt: string;
  cover: ApiImage;
  author: ApiResponse<Author>;
}

export interface DirectoryItem {
  name: string;
  description: string;
  url: string;
  logo: ApiImage;
  category: string;
}

export interface Game {
  title: string;
  description: string;
  url: string;
  thumbnail: ApiImage;
  category: string;
}

export interface AiTool {
  name: string;
  description: string;
  url: string;
  logo: ApiImage;
  category: string;
}

export interface Tag {
  name: string;
  slug: string;
}

// API URL for the custom backend
const API_URL = import.meta.env.PUBLIC_API_URL || 'https://api.storiesfromtheweb.org/api/v1';

export const getMediaUrl = (url: string) => {
  if (url.startsWith('http')) {
    return url;
  }
  return `${API_URL}/uploads/${url}`;
};

// Define params interface
interface ApiParams {
  filters?: Record<string, any>;
  sort?: string;
  page?: number;
  pageSize?: number;
  [key: string]: any;
}

// Function to get the current auth token from cookies
const getAuthToken = (): string | undefined => {
  return document.cookie
    .split('; ')
    .find(row => row.startsWith('auth_token='))
    ?.split('=')[1];
};

// Function to set the auth token in cookies
const setAuthToken = (token: string, expiresIn: number = 86400): void => {
  const expiryDate = new Date();
  expiryDate.setTime(expiryDate.getTime() + (expiresIn * 1000));
  document.cookie = `auth_token=${token}; expires=${expiryDate.toUTCString()}; path=/; SameSite=Strict`;
};

// Function to refresh the auth token
const refreshAuthToken = async (): Promise<boolean> => {
  try {
    console.log('Attempting to refresh auth token');
    
    // Get the current token
    const currentToken = getAuthToken();
    if (!currentToken) {
      console.error('Cannot refresh token: No token available');
      return false;
    }
    
    // Extract user ID from token
    let userId: number | null = null;
    try {
      const tokenParts = currentToken.split('.');
      if (tokenParts.length === 3) {
        const payload = JSON.parse(atob(tokenParts[1].replace(/-/g, '+').replace(/_/g, '/')));
        userId = payload.user_id;
      }
    } catch (e) {
      console.error('Error decoding token:', e);
    }
    
    if (!userId) {
      console.error('Cannot refresh token: Unable to extract user ID');
      return false;
    }
    
    // Make refresh request
    const url = `${API_URL}/auth/refresh`;
    console.log(`Refresh token URL: ${url}`);
    
    const requestData = {
      user_id: userId,
      force: true,
      threshold: 60 // 1 minute threshold
    };
    
    console.log(`Refresh token request data:`, requestData);
    
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${currentToken}`
      },
      body: JSON.stringify(requestData)
    });
    
    // Log the response status
    console.log(`Token refresh response status: ${response.status} ${response.statusText}`);
    
    // Try to get the response body as text first for debugging
    const responseText = await response.text();
    console.log(`Token refresh response body: ${responseText}`);
    
    if (!response.ok) {
      console.error(`Token refresh failed: ${response.status} ${response.statusText}`);
      console.error(`Response body: ${responseText}`);
      return false;
    }
    
    // Parse the response JSON
    let data;
    try {
      data = JSON.parse(responseText);
      console.log('Token refresh parsed response:', data);
    } catch (e) {
      console.error('Error parsing token refresh response:', e);
      return false;
    }
    
    // Check if token was refreshed
    if (data.refreshed === false) {
      console.log('Token is still valid, no refresh needed');
      return true;
    }
    
    // Check if we have a new token
    if (data.token) {
      console.log('Token refreshed successfully');
      console.log(`New token expires in: ${data.expires_in} seconds`);
      setAuthToken(data.token, data.expires_in);
      return true;
    }
    
    return false;
  } catch (error) {
    console.error('Error refreshing token:', error);
    return false;
  }
};

export const fetchFromApi = async (endpoint: string, params: ApiParams = {}, retryCount = 0): Promise<any> => {
  try {
    // Build query parameters
    const queryParams = new URLSearchParams();
    
    if (params.page) {
      queryParams.append('page', params.page.toString());
    }
    
    if (params.pageSize) {
      queryParams.append('pageSize', params.pageSize.toString());
    }
    
    if (params.sort) {
      queryParams.append('sort', params.sort);
    }
    
    // Add any filters
    if (params.filters) {
      Object.entries(params.filters).forEach(([key, value]) => {
        queryParams.append(key, value.toString());
      });
    }
    
    // Make the API call
    const queryString = queryParams.toString();
    const url = `${API_URL}/${endpoint}${queryString ? `?${queryString}` : ''}`;
    
    console.log(`Fetching from API: ${url}`);
    
    const headers: HeadersInit = {
      'Content-Type': 'application/json'
    };
    
    // Get auth token from cookie if available
    const authToken = getAuthToken();
    
    if (authToken) {
      headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const res = await fetch(url, { headers });
    
    // Check for new token in response headers
    const newToken = res.headers.get('X-New-Token');
    if (newToken) {
      console.log('Received new token in response headers');
      setAuthToken(newToken);
    }
    
    if (!res.ok) {
      const errorMessage = `Error fetching from API: ${res.status} ${res.statusText}`;
      console.error(errorMessage);
      console.error(`URL: ${url}`);
      console.error(`Headers: ${JSON.stringify(headers)}`);
      
      // If we get a 401 Unauthorized error, try to refresh the token and retry
      if (res.status === 401 && retryCount < 1) {
        console.log('Received 401 Unauthorized, attempting to refresh token and retry');
        
        const refreshed = await refreshAuthToken();
        if (refreshed) {
          console.log('Token refreshed, retrying original request');
          return fetchFromApi(endpoint, params, retryCount + 1);
        } else {
          console.error('Token refresh failed, cannot retry request');
        }
      }
      
      try {
        const errorResponse = await res.text();
        console.error(`Error response: ${errorResponse}`);
      } catch (textError) {
        console.error('Could not read error response body');
      }
      
      // Check API status if we get an error
      try {
        console.log('Checking API status...');
        const statusRes = await fetch(`${API_URL.replace('/api/v1', '')}/api-status.php`);
        if (statusRes.ok) {
          const statusData = await statusRes.json();
          console.log('API Status:', statusData);
        } else {
          console.error('API status check failed:', statusRes.status, statusRes.statusText);
        }
      } catch (statusError) {
        console.error('Error checking API status:', statusError);
      }
      
      throw new Error(`Failed to fetch from API: ${res.status} ${res.statusText}`);
    }
    
    return res.json();
  } catch (error) {
    console.error('Error fetching from API:', error);
    console.error(`Endpoint: ${endpoint}`);
    console.error(`Params: ${JSON.stringify(params)}`);
    
    // Create a detailed error object
    const apiError = {
      message: error instanceof Error ? error.message : 'Unknown error',
      endpoint,
      params,
      timestamp: new Date().toISOString(),
      apiUrl: API_URL
    };
    
    // Log the detailed error
    console.error('API Error Details:', apiError);
    
    // On error, return empty data with error information
    if (endpoint.includes('/') && endpoint.split('/').length > 1) {
      return { data: null, error: apiError };
    } else {
      return { 
        data: [], 
        meta: { pagination: { page: 1, pageSize: 25, pageCount: 0, total: 0 } },
        error: apiError
      };
    }
  }
}; */