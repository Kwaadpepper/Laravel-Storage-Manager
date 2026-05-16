import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    watch: {
      ignored: [
        '**/.git/**',
        '**/node_modules/**',
        '**/vendor/**',
        '**/coverage/**',
        '**/storage/**',
      ],
    },
  },
  resolve: {
    alias: {
      '@css': resolve(import.meta.dirname, 'src/css'),
      '@assets': resolve(import.meta.dirname, 'src/assets'),
      '@ts': resolve(import.meta.dirname, 'src/ts'),
    },
  },
  plugins: [
    react(),
    tailwindcss(),
  ],
  optimizeDeps: {
    rollupOptions: {},
  },
  build: {
    outDir: 'resources',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        main: 'src/ts/main.tsx',
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.names?.some((n) => n.endsWith('.css'))) {
            return 'css/[name][extname]'
          }
          return 'assets/[name]-[hash][extname]'
        },
      },
    },
  },
})
