import { describe, expect, it } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
import {
  getAncestorPaths,
  getDiskAndPath,
  getParentPath,
  getRootPath,
  pushLocation,
  replaceLocation,
  useEventQueue,
  useLocation,
  useTrash,
} from '../../src/ts/composables';
import { useActionStore } from '../../src/ts/stores';
import type { ApiClient } from '../../src/ts/clients';
import type { ConfigStore, DiskStore } from '../../src/ts/stores';
import type { Path } from '../../src/ts/types';

describe('useLocation composable and utilities', () => {
  it('parses empty or root hash as null disk and root path', () => {
    (globalThis as any).location = { hash: '', href: 'http://localhost' };
    expect(getDiskAndPath()).toEqual({ disk: null, path: '/' as Path });

    (globalThis as any).location = { hash: '#/', href: 'http://localhost' };
    expect(getDiskAndPath()).toEqual({ disk: null, path: '/' as Path, searchParams: undefined });
  });

  it('parses disk without path as disk and root path', () => {
    (globalThis as any).location = { hash: '#/local', href: 'http://localhost' };
    expect(getDiskAndPath()).toEqual({ disk: 'local' as any, path: '/' as Path, searchParams: undefined });

    (globalThis as any).location = { hash: '#/local/', href: 'http://localhost' };
    expect(getDiskAndPath()).toEqual({ disk: 'local' as any, path: '/' as Path, searchParams: undefined });
  });

  it('parses disk and nested path', () => {
    (globalThis as any).location = { hash: '#/s3/documents/reports/2026', href: 'http://localhost' };
    expect(getDiskAndPath()).toEqual({
      disk: 's3' as any,
      path: '/documents/reports/2026' as Path,
      searchParams: undefined,
    });
  });

  it('parses query search parameters inside hash', () => {
    (globalThis as any).location = { hash: '#/s3/search?query=test&page=1', href: 'http://localhost' };
    const result = getDiskAndPath();
    expect(result.disk).toBe('s3' as any);
    expect(result.path).toBe('/search' as Path);
    expect(result.searchParams?.get('query')).toBe('test');
    expect(result.searchParams?.get('page')).toBe('1');
  });

  it('calculates parent path and ancestor paths correctly', () => {
    expect(getRootPath()).toBe('/' as Path);
    expect(getParentPath('/' as Path)).toBeNull();
    expect(getParentPath('/documents' as Path)).toBe('/' as Path);
    expect(getParentPath('/documents/invoices' as Path)).toBe('/documents' as Path);

    expect(getAncestorPaths('/' as Path)).toEqual(['/' as Path]);
    expect(getAncestorPaths('/documents/invoices/2026' as Path)).toEqual([
      '/' as Path,
      '/documents' as Path,
      '/documents/invoices' as Path,
    ]);
  });

  it('pushes and replaces location in history', () => {
    let pushedUrl = '';
    let replacedUrl = '';
    (globalThis as any).location = { href: 'http://localhost/app' };
    (globalThis as any).history = {
      pushState: (_data: any, _title: string, url: string) => {
        pushedUrl = url;
      },
      replaceState: (_data: any, _title: string, url: string) => {
        replacedUrl = url;
      },
    };

    pushLocation('local' as any, '/docs/file.txt' as Path);
    expect(pushedUrl).toContain('#/local/docs/file.txt');

    const params = new URLSearchParams({ search: 'query' });
    replaceLocation('s3' as any, '/photos' as Path, params);
    expect(replacedUrl).toContain('#/s3/photos?search=query');

    const loc = useLocation();
    expect(typeof loc.getDiskAndPath).toBe('function');
    expect(typeof loc.push).toBe('function');
  });
});

describe('useEventQueue composable', () => {
  it('executes events sequentially in FIFO order', async () => {
    setActivePinia(createPinia());
    const actionStore = useActionStore();
    let refreshed = false;
    const mockNav = {
      refreshCurrentPath: () => {
        refreshed = true;
      },
    };

    const eventQueue = useEventQueue(mockNav, actionStore);
    const order: number[] = [];

    eventQueue.push({
      id: 'event-1',
      type: 'CREATE',
      sourcePath: '/dir1',
      execute: async () => {
        await new Promise((r) => setTimeout(r, 10));
        order.push(1);
      },
    });

    eventQueue.push({
      id: 'event-2',
      type: 'CREATE',
      sourcePath: '/dir2',
      execute: async () => {
        order.push(2);
      },
    });

    await new Promise((r) => setTimeout(r, 35));

    expect(order).toEqual([1, 2]);
    expect(actionStore.actions).toHaveLength(2);
    expect(actionStore.actions[0].status).toBe('success');
    expect(actionStore.actions[1].status).toBe('success');
    expect(refreshed).toBe(true);
  });

  it('pushAndWait resolves on success and rejects on error', async () => {
    setActivePinia(createPinia());
    const actionStore = useActionStore();
    const mockNav = { refreshCurrentPath: () => {} };
    const eventQueue = useEventQueue(mockNav, actionStore);

    let resolved = false;
    await eventQueue.pushAndWait({
      id: 'wait-ok',
      type: 'RENAME',
      sourcePath: '/old.txt',
      execute: async () => {
        resolved = true;
      },
    });
    expect(resolved).toBe(true);

    const failingEvent = {
      id: 'wait-fail',
      type: 'DELETE' as const,
      sourcePath: '/blocked.txt',
      execute: async () => {
        throw new Error('Permission denied');
      },
    };

    expect(eventQueue.pushAndWait(failingEvent)).rejects.toThrow('Permission denied');
  });
});

describe('useTrash composable', () => {
  it('invokes api client methods for list, restore, purge, and empty', async () => {
    let capturedUrl = '';
    let capturedMethod = '';
    let capturedBody: unknown = null;

    const mockApiClient: Partial<ApiClient> = {
      get: async (url: string) => {
        capturedUrl = url;
        capturedMethod = 'GET';
        return { items: [], total: 0 };
      },
      post: async (url: string, body: unknown) => {
        capturedUrl = url;
        capturedMethod = 'POST';
        capturedBody = body;
        return { restored: [{ id: 'trash-1', path: '/restored.txt' }] };
      },
      delete: async (url: string, body: unknown) => {
        capturedUrl = url;
        capturedMethod = 'DELETE';
        capturedBody = body;
        return { ids: ['trash-1'], count: 1 };
      },
    };

    const mockConfigStore = {
      routes: {
        trashList: '/sm/api/trash/list',
        trashRestore: '/sm/api/trash/restore',
        trashPurge: '/sm/api/trash/purge',
        trashEmpty: '/sm/api/trash/empty',
      },
    } as unknown as ConfigStore;

    const mockDiskStore = {
      currentDisk: 'public',
    } as unknown as DiskStore;

    const trash = useTrash(
      mockApiClient as ApiClient,
      mockConfigStore,
      mockDiskStore
    );

    const listRes = await trash.list();
    expect(capturedMethod).toBe('GET');
    expect(capturedUrl).toBe('/sm/api/trash/list?disk=public');
    expect(listRes.total).toBe(0);

    const restoreRes = await trash.restore(['trash-1']);
    expect(capturedMethod).toBe('POST');
    expect(capturedUrl).toBe('/sm/api/trash/restore');
    expect(capturedBody).toEqual({ disk: 'public', ids: ['trash-1'] });
    expect(restoreRes.restored).toHaveLength(1);

    const purgeRes = await trash.purge(['trash-1']);
    expect(capturedMethod).toBe('DELETE');
    expect(capturedUrl).toBe('/sm/api/trash/purge');
    expect(capturedBody).toEqual({ disk: 'public', ids: ['trash-1'] });
    expect(purgeRes.count).toBe(1);

    await trash.emptyTrash();
    expect(capturedMethod).toBe('DELETE');
    expect(capturedUrl).toBe('/sm/api/trash/empty');
    expect(capturedBody).toEqual({ disk: 'public' });
  });

  it('throws an error if no disk is configured', () => {
    const mockApiClient = {} as ApiClient;
    const mockConfigStore = { routes: {} } as unknown as ConfigStore;
    const mockDiskStore = { currentDisk: null } as unknown as DiskStore;

    const trash = useTrash(mockApiClient, mockConfigStore, mockDiskStore);
    expect(trash.list()).rejects.toThrow('No disk selected');
  });
});
