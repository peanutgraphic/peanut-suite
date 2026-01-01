import '@testing-library/jest-dom';
import { cleanup } from '@testing-library/react';
import { afterEach, vi } from 'vitest';

// Cleanup after each test
afterEach(() => {
  cleanup();
});

// Mock window.peanutData for tests
Object.defineProperty(window, 'peanutData', {
  value: {
    nonce: 'test-nonce',
    restUrl: 'http://localhost/wp-json/peanut/v1/',
    adminUrl: 'http://localhost/wp-admin/',
    version: '4.2.24',
    brandName: 'Marketing Suite',
    license: {
      tier: 'agency',
      status: 'active',
    },
    user: {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
    },
    features: {
      links: true,
      utms: true,
      contacts: true,
      analytics: true,
      popups: true,
      visitors: true,
      attribution: true,
      webhooks: true,
      sequences: true,
      keywords: true,
      backlinks: true,
      woocommerce: true,
      performance: true,
      security: true,
      monitor: true,
    },
    demoMode: false,
  },
  writable: true,
});

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}));
