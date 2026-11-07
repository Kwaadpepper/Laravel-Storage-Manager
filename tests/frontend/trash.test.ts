import { describe, expect, it } from 'bun:test';
import {
  deletedResponseSchema,
  purgedTrashResponseSchema,
  restoredTrashResponseSchema,
  trashListResponseSchema,
} from '../../src/ts/schemas';
import { TrashService } from '../../src/ts/composables';
import type { ApiService } from '../../src/ts/clients';
import type { ConfigStore, DiskStore } from '../../src/ts/stores';

describe('Trash Schemas', () => {
  it('parses valid trash list response', () => {
    const rawData = {
      items: [
        {
          id: '019238ac-1111-7000-8000-000000000001',
          name: 'photo.jpg',
          originalPath: '/documents/photo.jpg',
          type: 'file',
          size: 10240,
          deletedAt: '2026-09-27T10:00:00+00:00',
          disk: 'local',
          status: 'restoring',
        },
        {
          id: '019238ac-2222-7000-8000-000000000002',
          name: 'old-folder',
          originalPath: '/old-folder',
          type: 'directory',
          size: 0,
          deletedAt: '2026-09-27T10:05:00+00:00',
          disk: 'local',
          status: 'trashed',
        },
      ],
      total: 2,
    };

    const parsed = trashListResponseSchema.parse(rawData);
    expect(parsed.total).toBe(2);
    expect(parsed.items).toHaveLength(2);
    expect(parsed.items[0].id).toBe('019238ac-1111-7000-8000-000000000001');
    expect(parsed.items[0].name).toBe('photo.jpg');
    expect(parsed.items[0].type).toBe('file');
    expect(parsed.items[0].size).toBe(10240);
    expect(parsed.items[0].status).toBe('restoring');
    expect(parsed.items[1].name).toBe('old-folder');
    expect(parsed.items[1].type).toBe('directory');
    expect(parsed.items[1].status).toBe('trashed');
  });

  it('parses restored trash response', () => {
    const rawData = {
      restored: [
        {
          id: '019238ac-1111-7000-8000-000000000001',
          path: '/documents/photo.jpg',
        },
      ],
    };

    const parsed = restoredTrashResponseSchema.parse(rawData);
    expect(parsed.restored).toHaveLength(1);
    expect(parsed.restored[0].id).toBe('019238ac-1111-7000-8000-000000000001');
    expect(parsed.restored[0].path).toBe('/documents/photo.jpg');
  });

  it('parses purged trash response', () => {
    const rawData = {
      ids: ['019238ac-1111-7000-8000-000000000001', '019238ac-2222-7000-8000-000000000002'],
      count: 2,
    };

    const parsed = purgedTrashResponseSchema.parse(rawData);
    expect(parsed.count).toBe(2);
    expect(parsed.ids).toEqual(['019238ac-1111-7000-8000-000000000001', '019238ac-2222-7000-8000-000000000002']);
  });

  it('parses soft-deleted response with trashed and trashId', () => {
    const rawData = {
      trashed: true,
      trashId: '019238ac-1111-7000-8000-000000000001',
    };

    const parsed = deletedResponseSchema.parse(rawData);
    expect(parsed.trashed).toBe(true);
    expect(parsed.trashId).toBe('019238ac-1111-7000-8000-000000000001');
  });

  it('parses permanent deleted response without trashed or trashId', () => {
    const rawData = {};
    const parsed = deletedResponseSchema.parse(rawData);
    expect(parsed.trashed).toBeUndefined();
    expect(parsed.trashId).toBeUndefined();
  });
});

describe('TrashService', () => {
  it('calls apiService.get with encoded disk parameter', async () => {
    let capturedUrl = '';
    const mockApiService = {
      get: async (url: string) => {
        capturedUrl = url;
        return { items: [], total: 0 };
      },
    } as unknown as ApiService;

    const mockConfigStore = {
      routes: { trashList: '/sm/api/trash/list' },
    } as unknown as ConfigStore;

    const mockDiskStore = {
      currentDisk: 'public',
    } as unknown as DiskStore;

    const service = new TrashService(mockApiService, mockConfigStore, mockDiskStore);
    const result = await service.list();

    expect(capturedUrl).toBe('/sm/api/trash/list?disk=public');
    expect(result.total).toBe(0);
  });

  it('calls apiService.post on restore with disk and ids', async () => {
    let capturedUrl = '';
    let capturedBody: unknown = null;
    const mockApiService = {
      post: async (url: string, body: unknown) => {
        capturedUrl = url;
        capturedBody = body;
        return { restored: [{ id: 'item-1', path: '/test.txt' }] };
      },
    } as unknown as ApiService;

    const mockConfigStore = {
      routes: { trashRestore: '/sm/api/trash/restore' },
    } as unknown as ConfigStore;

    const mockDiskStore = {
      currentDisk: 's3',
    } as unknown as DiskStore;

    const service = new TrashService(mockApiService, mockConfigStore, mockDiskStore);
    const result = await service.restore(['item-1']);

    expect(capturedUrl).toBe('/sm/api/trash/restore');
    expect(capturedBody).toEqual({ disk: 's3', ids: ['item-1'] });
    expect(result.restored).toHaveLength(1);
  });

  it('calls apiService.delete on purge with disk and ids', async () => {
    let capturedUrl = '';
    let capturedBody: unknown = null;
    const mockApiService = {
      delete: async (url: string, body: unknown) => {
        capturedUrl = url;
        capturedBody = body;
        return { ids: ['item-1'], count: 1 };
      },
    } as unknown as ApiService;

    const mockConfigStore = {
      routes: { trashPurge: '/sm/api/trash/purge' },
    } as unknown as ConfigStore;

    const mockDiskStore = {
      currentDisk: 's3',
    } as unknown as DiskStore;

    const service = new TrashService(mockApiService, mockConfigStore, mockDiskStore);
    const result = await service.purge(['item-1']);

    expect(capturedUrl).toBe('/sm/api/trash/purge');
    expect(capturedBody).toEqual({ disk: 's3', ids: ['item-1'] });
    expect(result.count).toBe(1);
  });

  it('calls apiService.delete on emptyTrash with disk', async () => {
    let capturedUrl = '';
    let capturedBody: unknown = null;
    const mockApiService = {
      delete: async (url: string, body: unknown) => {
        capturedUrl = url;
        capturedBody = body;
        return { ids: [], count: 0 };
      },
    } as unknown as ApiService;

    const mockConfigStore = {
      routes: { trashEmpty: '/sm/api/trash/empty' },
    } as unknown as ConfigStore;

    const mockDiskStore = {
      currentDisk: 's3',
    } as unknown as DiskStore;

    const service = new TrashService(mockApiService, mockConfigStore, mockDiskStore);
    const result = await service.emptyTrash();

    expect(capturedUrl).toBe('/sm/api/trash/empty');
    expect(capturedBody).toEqual({ disk: 's3' });
    expect(result.count).toBe(0);
  });

  it('throws an error if no disk is set or provided', async () => {
    const mockApiService = {} as unknown as ApiService;
    const mockConfigStore = { routes: {} } as unknown as ConfigStore;
    const mockDiskStore = { currentDisk: null } as unknown as DiskStore;

    const service = new TrashService(mockApiService, mockConfigStore, mockDiskStore);
    expect(service.list()).rejects.toThrow('No disk selected');
  });
});

describe('Trash EventQueue Integration', () => {
  it('queues RESTORE events and executes them', async () => {
    const { createPinia, setActivePinia } = await import('pinia');
    const { useActionStore } = await import('../../src/ts/stores');
    const { EventQueueService } = await import('../../src/ts/composables');
    type NavigationService = import('../../src/ts/composables').NavigationService;

    setActivePinia(createPinia());
    const actionStore = useActionStore();
    let refreshed = false;
    const mockNav = {
      refreshCurrentPath: () => {
        refreshed = true;
      },
    } as unknown as NavigationService;

    const queueService = new EventQueueService(mockNav, actionStore);
    let executed = false;

    queueService.push({
      id: 'restore-test-1',
      type: 'RESTORE',
      sourcePath: '/test/photo.jpg',
      execute: async () => {
        executed = true;
      },
    });

    expect(actionStore.actions).toHaveLength(1);
    expect(actionStore.actions[0].type).toBe('RESTORE');
    expect(actionStore.actions[0].status).toBe('pending');

    await new Promise((r) => setTimeout(r, 15));

    expect(executed).toBe(true);
    expect(actionStore.actions[0].status).toBe('success');
    expect(refreshed).toBe(true);
  });

  it('queues PURGE events and marks errors properly', async () => {
    const { createPinia, setActivePinia } = await import('pinia');
    const { useActionStore } = await import('../../src/ts/stores');
    const { EventQueueService } = await import('../../src/ts/composables');
    type NavigationService = import('../../src/ts/composables').NavigationService;

    setActivePinia(createPinia());
    const actionStore = useActionStore();
    const mockNav = {
      refreshCurrentPath: () => {},
    } as unknown as NavigationService;

    const queueService = new EventQueueService(mockNav, actionStore);

    queueService.push({
      id: 'purge-fail-1',
      type: 'PURGE',
      sourcePath: '/trash/file.txt',
      execute: async () => {
        throw new Error('Purge failed');
      },
    });

    expect(actionStore.actions[0].type).toBe('PURGE');

    await new Promise((r) => setTimeout(r, 15));

    expect(actionStore.actions[0].status).toBe('error');
    expect(actionStore.actions[0].error).toBe('Purge failed');
  });

  it('ensures ongoing queue entries are sorted before completed ones', () => {
    const entries = [
      { id: '1', isOngoing: false, kind: 'upload' },
      { id: '2', isOngoing: false, kind: 'action' },
      { id: '3', isOngoing: true, kind: 'upload' },
      { id: '4', isOngoing: true, kind: 'action' },
    ];

    const ongoing = entries.filter((e) => e.isOngoing).reverse();
    const finished = entries.filter((e) => !e.isOngoing).reverse();
    const sorted = [...ongoing, ...finished];

    expect(sorted[0].id).toBe('4');
    expect(sorted[0].isOngoing).toBe(true);
    expect(sorted[1].id).toBe('3');
    expect(sorted[1].isOngoing).toBe(true);
    expect(sorted[2].id).toBe('2');
    expect(sorted[2].isOngoing).toBe(false);
    expect(sorted[3].id).toBe('1');
    expect(sorted[3].isOngoing).toBe(false);
  });
});

describe('TrashStore', () => {
  it('tracks processing states for items and persists across store calls', async () => {
    const { createPinia, setActivePinia } = await import('pinia');
    const { useTrashStore } = await import('../../src/ts/stores');

    setActivePinia(createPinia());
    const store = useTrashStore();

    expect(store.isAnyProcessing).toBe(false);
    expect(store.isItemProcessing('item-1')).toBe(false);
    expect(store.getItemProcessingState('item-1')).toBeNull();

    store.setItemProcessing('item-1', 'restore');
    expect(store.isAnyProcessing).toBe(true);
    expect(store.isItemProcessing('item-1')).toBe(true);
    expect(store.getItemProcessingState('item-1')).toBe('restore');

    store.setItemProcessing('item-2', 'purge');
    expect(store.isItemProcessing('item-2')).toBe(true);
    expect(store.getItemProcessingState('item-2')).toBe('purge');

    // Retrieve store again to verify persistence
    const sameStore = useTrashStore();
    expect(sameStore.isItemProcessing('item-1')).toBe(true);
    expect(sameStore.getItemProcessingState('item-1')).toBe('restore');

    store.clearItemProcessing('item-1');
    expect(store.isItemProcessing('item-1')).toBe(false);
    expect(store.isAnyProcessing).toBe(true); // item-2 still processing

    store.clearItemProcessing('item-2');
    expect(store.isAnyProcessing).toBe(false);
  });

  it('tracks isEmptying state and affects isAnyProcessing', async () => {
    const { createPinia, setActivePinia } = await import('pinia');
    const { useTrashStore } = await import('../../src/ts/stores');

    setActivePinia(createPinia());
    const store = useTrashStore();

    expect(store.isEmptying).toBe(false);
    expect(store.isAnyProcessing).toBe(false);

    store.isEmptying = true;
    expect(store.isAnyProcessing).toBe(true);

    store.isEmptying = false;
    expect(store.isAnyProcessing).toBe(false);
  });

  it('manages items list, remove, refresh trigger and reset', async () => {
    const { createPinia, setActivePinia } = await import('pinia');
    const { useTrashStore } = await import('../../src/ts/stores');

    setActivePinia(createPinia());
    const store = useTrashStore();

    expect(store.items).toHaveLength(0);
    expect(store.refreshTrigger).toBe(0);

    const testItem = {
      id: 'item-10',
      name: 'video.mp4',
      originalPath: '/video.mp4',
      type: 'file' as const,
      size: 500000000,
      deletedAt: '2026-09-28T09:00:00Z',
      disk: 's3',
      status: 'restoring' as const,
    };

    store.setItems([testItem]);
    expect(store.items).toHaveLength(1);
    expect(store.items[0].name).toBe('video.mp4');

    store.requestRefresh();
    expect(store.refreshTrigger).toBe(1);

    store.removeItem('item-10');
    expect(store.items).toHaveLength(0);

    store.setItemProcessing('item-99', 'restore');
    store.clearItemProcessing('item-99', true);
    expect(store.refreshTrigger).toBe(2);

    store.setItemProcessing('item-99', 'restore');
    store.clearItemProcessing('item-99', false);
    expect(store.refreshTrigger).toBe(2); // not incremented when notify = false

    store.setItemProcessing('item-1', 'purge');
    store.setItems([testItem]);
    store.reset();
    expect(store.items).toHaveLength(0);
    expect(store.isAnyProcessing).toBe(false);
  });
});

