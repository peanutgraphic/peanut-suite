import { defineConfig, Plugin } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

/**
 * Plugin to externalize React and use WordPress's bundled version.
 * This prevents "Invalid hook call" errors caused by multiple React instances.
 *
 * The import map is injected by PHP (class-peanut-admin-assets.php) to resolve
 * bare module specifiers to data: URLs that re-export from window.React/ReactDOM.
 */
function wordpressReactExternals(): Plugin {
  return {
    name: 'wordpress-react-externals',
    config() {
      return {
        build: {
          rollupOptions: {
            // Mark React packages as external so they're not bundled
            external: ['react', 'react-dom', 'react/jsx-runtime', 'react-dom/client'],
          },
        },
      };
    },
  };
}

export default defineConfig({
  plugins: [
    react({
      // Use the classic runtime since we're externalizing React
      jsxRuntime: 'classic',
    }),
    tailwindcss(),
    wordpressReactExternals(),
  ],
  // Base path for WordPress plugin - chunks load from this URL
  base: '/wp-content/plugins/peanut-suite/assets/dist/',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    outDir: '../assets/dist',
    emptyOutDir: true,
    manifest: true,
    sourcemap: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src/main.tsx'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
        // Keep libraries that use React context in single chunks to prevent
        // context issues when code-splitting creates multiple module instances
        manualChunks: {
          'vendor-react': ['@tanstack/react-query', 'react-router-dom'],
        },
      },
    },
  },
  server: {
    port: 3000,
    cors: true,
  },
});
