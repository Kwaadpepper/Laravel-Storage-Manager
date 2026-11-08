import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
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
    cors: true,
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
    vue(),
    tailwindcss(),
  ],
  build: {
    outDir: 'resources',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        main: 'src/ts/main.ts',
        'storage-manager-picker': 'src/ts/integrations/standalone.ts',
        'storage-manager-ckeditor5': 'src/ts/integrations/ckeditor5.ts',
        'storage-manager-tiptap': 'src/ts/integrations/tiptap.ts',
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
