import { describe, expect, it } from 'bun:test';
import { DownloadService } from '../../src/ts/composables';
import type { ConfigStore, DiskStore } from '../../src/ts/stores';

describe('DownloadService', () => {
  it('uses custom routePrefix from configStore in generated URLs', () => {
    const fakeDiskStore = {
      currentDisk: 'local',
    } as unknown as DiskStore;

    const fakeConfigStore = {
      routePrefix: '/admin/media',
    } as unknown as ConfigStore;

    const service = new DownloadService(fakeDiskStore, fakeConfigStore);

    expect(service.getStreamUrl('/videos/sample-1.mp4')).toBe('/admin/media/stream/local/videos/sample-1.mp4');
    expect(service.getDownloadUrl('/videos/sample-1.mp4')).toBe('/admin/media/download/local/videos/sample-1.mp4');
    expect(service.getThumbnailUrl('/images/photo.png')).toBe('/admin/media/thumb/local/images/photo.png');
  });

  it('generates correct stream and download URLs for current disk', () => {
    const fakeDiskStore = {
      currentDisk: 'local',
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);

    expect(service.getStreamUrl('/videos/sample-1.mp4')).toBe('/sm/stream/local/videos/sample-1.mp4');
    expect(service.getDownloadUrl('/videos/sample-1.mp4')).toBe('/sm/download/local/videos/sample-1.mp4');
    expect(service.getThumbnailUrl('/images/photo.png')).toBe('/sm/thumb/local/images/photo.png');
  });

  it('falls back to public disk if currentDisk is not set', () => {
    const fakeDiskStore = {
      currentDisk: null,
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);

    expect(service.getStreamUrl('/music/track.mp3')).toBe('/sm/stream/public/music/track.mp3');
    expect(service.getDownloadUrl('/music/track.mp3')).toBe('/sm/download/public/music/track.mp3');
  });

  it('correctly encodes special characters in paths', () => {
    const fakeDiskStore = {
      currentDisk: 's3',
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);

    expect(service.getStreamUrl('/dossier test/vidéo (1).mp4')).toBe(
      '/sm/stream/s3/dossier%20test/vid%C3%A9o%20(1).mp4'
    );
  });

  it('triggers download via anchor element and URL.revokeObjectURL', async () => {
    const fakeDiskStore = {
      currentDisk: 'local',
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);
    const mockBlob = new Blob(['test']);
    service.downloadFile = async () => mockBlob;

    let clicked = false;
    let downloadAttr = '';
    let revokedUrl = '';

    const origCreateObjectURL = globalThis.URL.createObjectURL;
    const origRevokeObjectURL = globalThis.URL.revokeObjectURL;
    const origDocument = (globalThis as unknown as { document?: Document }).document;

    globalThis.URL.createObjectURL = () => 'blob:mock-url';
    globalThis.URL.revokeObjectURL = (url: string) => {
      revokedUrl = url;
    };

    const mockAnchor = {
      href: '',
      download: '',
      click() {
        clicked = true;
        downloadAttr = this.download;
      },
    };

    (globalThis as unknown as { document: unknown }).document = {
      createElement: (tagName: string) => {
        if (tagName === 'a') {
          return mockAnchor;
        }
        return {};
      },
    };

    try {
      await service.triggerDownload('/docs/test.txt', 'test.txt');
      expect(clicked).toBeTrue();
      expect(downloadAttr).toBe('test.txt');
      expect(revokedUrl).toBe('blob:mock-url');
    } finally {
      (globalThis as unknown as { document?: Document }).document = origDocument;
      globalThis.URL.createObjectURL = origCreateObjectURL;
      globalThis.URL.revokeObjectURL = origRevokeObjectURL;
    }
  });

  it('handles paths without leading slash without truncating the filename', () => {
    const fakeDiskStore = {
      currentDisk: 'local',
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);
    expect(service.getStreamUrl('videos/sample-1.mp4' as any)).toBe('/sm/stream/local/videos/sample-1.mp4');
  });
});

describe('useDownload composable', () => {
  it('generates stream, download, and thumbnail URLs', () => {
    const { useDownload } = require('../../src/ts/composables');
    const fakeDiskStore = { currentDisk: 's3' } as unknown as DiskStore;
    const fakeConfigStore = { routePrefix: '/storage' } as unknown as ConfigStore;

    const { getStreamUrl, getDownloadUrl, getThumbnailUrl } = useDownload(fakeDiskStore, fakeConfigStore);

    expect(getStreamUrl('/images/logo.png')).toBe('/storage/stream/s3/images/logo.png');
    expect(getDownloadUrl('/images/logo.png')).toBe('/storage/download/s3/images/logo.png');
    expect(getThumbnailUrl('/images/logo.png')).toBe('/storage/thumb/s3/images/logo.png');
  });
});

