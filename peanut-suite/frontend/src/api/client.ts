import axios, { AxiosInstance, AxiosError } from 'axios';
import type { ApiResponse } from '@/types';

// WordPress passes these via wp_localize_script
declare global {
  interface Window {
    peanutSuite?: {
      apiUrl: string;
      nonce: string;
      version: string;
      isPro: boolean;
      tier: string;
    };
  }
}

// Get config from WordPress or use defaults for development
const getConfig = () => {
  if (window.peanutSuite) {
    return {
      baseURL: window.peanutSuite.apiUrl,
      nonce: window.peanutSuite.nonce,
    };
  }

  // Development fallback
  return {
    baseURL: '/wp-json/peanut/v1',
    nonce: '',
  };
};

const config = getConfig();

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: config.baseURL,
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': config.nonce,
  },
});

// Response interceptor
api.interceptors.response.use(
  (response) => {
    // Handle Peanut API response format
    const data = response.data;
    if (data && typeof data === 'object' && 'success' in data) {
      if (!data.success) {
        return Promise.reject(new Error(data.message || 'Request failed'));
      }
      // Preserve meta for paginated responses
      if ('meta' in data) {
        return { ...response, data: { data: data.data, meta: data.meta } };
      }
      return { ...response, data: data.data };
    }
    return response;
  },
  (error: AxiosError<ApiResponse<unknown>>) => {
    const message = error.response?.data?.message || error.message || 'An error occurred';
    return Promise.reject(new Error(message));
  }
);

export default api;

/**
 * GET request as POST alternative
 *
 * Some hosting environments (especially those with ModSecurity or WAF)
 * block POST requests to certain URL patterns. This helper uses a GET
 * request with query parameters as a workaround. The backend must register
 * the route to accept both GET and POST.
 */
export const getAsPost = async <T>(url: string, data?: Record<string, unknown>): Promise<{ data: T }> => {
  const response = await api.request<T>({
    method: 'GET',
    url,
    params: data,
  });
  return response;
};

// Helper to check if we're in WordPress admin
export const isWordPressAdmin = (): boolean => {
  return typeof window.peanutSuite !== 'undefined';
};

// Helper to get current tier
export const getCurrentTier = (): string => {
  return window.peanutSuite?.tier || 'free';
};

// Helper to check if Pro features available
export const isPro = (): boolean => {
  return window.peanutSuite?.isPro || false;
};
