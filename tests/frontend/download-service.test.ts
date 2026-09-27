import { describe, expect, it } from 'bun:test';
import { DownloadService } from '../../src/ts/services/download-service';
import type { DiskStore } from '../../src/ts/stores';

describe('DownloadService', () => {
  it('generates correct stream and download URLs for current disk', () => {
    const fakeDiskStore = {
      currentDisk: 'local',
    } as unknown as DiskStore;

    const service = new DownloadService(fakeDiskStore);

    expect(service.getStreamUrl('/videos/sample-1.mp4')).toBe('/sm/stream/local/videos/sample-1.mp4');
    expect(service.getDownloadUrl('/videos/sample-1.mp4')).toBe('/sm/download/local/videos/sample-1.mp4');
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
});
