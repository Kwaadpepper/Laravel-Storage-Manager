import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { rmSync } from 'node:fs';
import { resolve } from 'node:path';
import { type Plugin, defineConfig } from 'vite';

const cleanOutputDirs = (): Plugin => ({
  name: 'clean-output-dirs',
  buildStart() {
    for (const dir of ['resources/js', 'resources/css']) {
      rmSync(resolve(import.meta.dirname, dir), { recursive: true, force: true });
    }
  },
});

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
    cleanOutputDirs(),
    react(),
    tailwindcss(),
  ],
  optimizeDeps: {
    exclude: ['prism-react-editor'],
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
        chunkFileNames: (chunk) => {
          // Stable names (no hash) for known lazy chunks loaded by the blade
          if (chunk.name === 'text-editor') return 'js/[name].js';
          return 'js/[name]-[hash].js';
        },
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
