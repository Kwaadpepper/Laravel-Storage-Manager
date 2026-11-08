import { describe, expect, it } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
import { paginationMetaSchema, treeResponseSchema } from '../../src/ts/schemas';
import { useFileManagerStore } from '../../src/ts/stores';
import { useFileManager } from '../../src/ts/composables';
import type { ApiClient } from '../../src/ts/clients';
import type { Path, TreeNodeFile } from '../../src/ts/types';

describe('Pagination Frontend Tests', () => {
  describe('paginationMetaSchema', () => {
    it('parses valid pagination metadata', () => {
      const payload = {
        currentPage: 1,
        perPage: 50,
        totalFiles: 120,
        totalDirectories: 5,
        totalPages: 3,
        hasMore: true,
      };

      const result = paginationMetaSchema.parse(payload);

      expect(result.currentPage).toBe(1);
      expect(result.perPage).toBe(50);
      expect(result.totalFiles).toBe(120);
      expect(result.totalDirectories).toBe(5);
      expect(result.totalPages).toBe(3);
      expect(result.hasMore).toBe(true);
    });

    it('parses treeResponseSchema with pagination', () => {
      const payload = {
        directories: [
          { path: '/photos', hasSubDirectories: false, visibility: 'public' },
        ],
        files: [
          { path: '/test.txt', size: 100, extension: 'txt', visibility: 'private', publicUrl: null },
        ],
        pagination: {
          currentPage: 1,
          perPage: 50,
          totalFiles: 1,
          totalDirectories: 1,
          totalPages: 1,
          hasMore: false,
        },
      };

      const result = treeResponseSchema.parse(payload);

      expect(result.directories).toHaveLength(1);
      expect(result.files).toHaveLength(1);
      expect(result.pagination?.currentPage).toBe(1);
      expect(result.pagination?.hasMore).toBe(false);
    });
  });

  describe('useFileManagerStore pagination', () => {
    it('manages pagination and fileNodes correctly on setFileNodes and appendFileNodes', () => {
      setActivePinia(createPinia());
      const store = useFileManagerStore();

      const file1: TreeNodeFile = {
        path: '/a.txt' as Path,
        name: 'a.txt',
        size: 10,
        extension: 'txt',
        visibility: 'public',
      };
      const file2: TreeNodeFile = {
        path: '/b.txt' as Path,
        name: 'b.txt',
        size: 20,
        extension: 'txt',
        visibility: 'public',
      };
      const metaPage1 = {
        currentPage: 1,
        perPage: 1,
        totalFiles: 2,
        totalDirectories: 0,
        totalPages: 2,
        hasMore: true,
      };
      const metaPage2 = {
        currentPage: 2,
        perPage: 1,
        totalFiles: 2,
        totalDirectories: 0,
        totalPages: 2,
        hasMore: false,
      };

      store.setFileNodes([file1], metaPage1);
      expect(store.fileNodes).toHaveLength(1);
      expect(store.pagination?.currentPage).toBe(1);
      expect(store.pagination?.hasMore).toBe(true);

      store.setIsLoadingMore(true);
      expect(store.isLoadingMore).toBe(true);

      store.appendFileNodes([file2], metaPage2);
      store.setIsLoadingMore(false);

      expect(store.fileNodes).toHaveLength(2);
      expect(store.fileNodes[0].path).toBe('/a.txt');
      expect(store.fileNodes[1].path).toBe('/b.txt');
      expect(store.pagination?.currentPage).toBe(2);
      expect(store.pagination?.hasMore).toBe(false);
      expect(store.isLoadingMore).toBe(false);
    });

    it('resets pagination when setCurrentPath is called', () => {
      setActivePinia(createPinia());
      const store = useFileManagerStore();

      store.setPagination({
        currentPage: 2,
        perPage: 50,
        totalFiles: 100,
        totalDirectories: 0,
        totalPages: 2,
        hasMore: false,
      });
      expect(store.pagination).not.toBeNull();

      store.setCurrentPath('/documents' as Path);
      expect(store.pagination).toBeNull();
      expect(store.currentPath).toBe('/documents');
    });
  });

  describe('useFileManager listFiles with pagination', () => {
    it('passes pagination and sort query parameters to /content', async () => {
      let requestedUrl = '';
      const mockApiClient = {
        get: async (url: string) => {
          requestedUrl = url;
          return {
            directories: [],
            files: [],
            pagination: {
              currentPage: 2,
              perPage: 25,
              totalFiles: 60,
              totalDirectories: 0,
              totalPages: 3,
              hasMore: true,
            },
          };
        },
      } as unknown as ApiClient;

      const mockDiskStore = {
        currentDisk: 's3',
      } as any;

      const fileManager = useFileManager(mockApiClient, mockDiskStore);
      const result = await fileManager.listFiles(
        '/uploads' as Path,
        2,
        25,
        'size',
        'desc'
      );

      expect(requestedUrl).toContain('/sm/fm/content?');
      expect(requestedUrl).toContain('disk=s3');
      expect(requestedUrl).toContain('path=%2Fuploads');
      expect(requestedUrl).toContain('page=2');
      expect(requestedUrl).toContain('per_page=25');
      expect(requestedUrl).toContain('sort_by=size');
      expect(requestedUrl).toContain('sort_order=desc');

      expect(result.pagination?.currentPage).toBe(2);
      expect(result.pagination?.hasMore).toBe(true);
    });
  });
});
