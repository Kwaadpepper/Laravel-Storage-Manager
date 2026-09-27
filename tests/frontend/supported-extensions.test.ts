import { describe, expect, it } from 'bun:test';
import {
  SUPPORTED_AUDIO_EXTENSIONS,
  SUPPORTED_IMAGE_EXTENSIONS,
  SUPPORTED_PDF_EXTENSIONS,
  SUPPORTED_TEXT_EXTENSIONS,
  SUPPORTED_VIDEO_EXTENSIONS,
} from '../../src/ts/utils/supported-extensions';
import {
  ensureMediaBlob,
  getAudioMimeType,
  getVideoMimeType,
} from '../../src/ts/utils/media-mime';

describe('supported-extensions', () => {
  it('defines supported video extensions', () => {
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('mp4');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('webm');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('ogg');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('ogv');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('mov');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('m4v');
    expect(SUPPORTED_VIDEO_EXTENSIONS).toContain('mkv');
  });

  it('defines supported audio extensions', () => {
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('mp3');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('wav');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('ogg');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('flac');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('m4a');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('aac');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('opus');
    expect(SUPPORTED_AUDIO_EXTENSIONS).toContain('weba');
  });

  it('preserves other extension categories', () => {
    expect(SUPPORTED_IMAGE_EXTENSIONS).toContain('png');
    expect(SUPPORTED_PDF_EXTENSIONS).toContain('pdf');
    expect(SUPPORTED_TEXT_EXTENSIONS).toContain('txt');
  });
});

describe('media-mime', () => {
  it('resolves audio MIME types correctly', () => {
    expect(getAudioMimeType('mp3')).toBe('audio/mpeg');
    expect(getAudioMimeType('.MP3')).toBe('audio/mpeg');
    expect(getAudioMimeType('wav')).toBe('audio/wav');
    expect(getAudioMimeType('ogg')).toBe('audio/ogg');
    expect(getAudioMimeType('flac')).toBe('audio/flac');
    expect(getAudioMimeType('m4a')).toBe('audio/mp4');
    expect(getAudioMimeType('aac')).toBe('audio/aac');
    expect(getAudioMimeType('opus')).toBe('audio/opus');
    expect(getAudioMimeType('weba')).toBe('audio/webm');
    expect(getAudioMimeType('unknown')).toBeNull();
    expect(getAudioMimeType(null)).toBeNull();
  });

  it('resolves video MIME types correctly', () => {
    expect(getVideoMimeType('mp4')).toBe('video/mp4');
    expect(getVideoMimeType('.MP4')).toBe('video/mp4');
    expect(getVideoMimeType('webm')).toBe('video/webm');
    expect(getVideoMimeType('ogg')).toBe('video/ogg');
    expect(getVideoMimeType('ogv')).toBe('video/ogg');
    expect(getVideoMimeType('mov')).toBe('video/quicktime');
    expect(getVideoMimeType('m4v')).toBe('video/mp4');
    expect(getVideoMimeType('mkv')).toBe('video/x-matroska');
    expect(getVideoMimeType('unknown')).toBeNull();
    expect(getVideoMimeType(null)).toBeNull();
  });

  it('ensures media blob has correct audio MIME type', () => {
    const rawBlob = new Blob(['sample audio data'], { type: 'application/octet-stream' });
    const typedBlob = ensureMediaBlob(rawBlob, 'mp3', 'audio');

    expect(typedBlob.type).toBe('audio/mpeg');
    expect(typedBlob.size).toBe(rawBlob.size);
  });

  it('ensures media blob has correct video MIME type', () => {
    const rawBlob = new Blob(['sample video data'], { type: '' });
    const typedBlob = ensureMediaBlob(rawBlob, 'mp4', 'video');

    expect(typedBlob.type).toBe('video/mp4');
    expect(typedBlob.size).toBe(rawBlob.size);
  });

  it('preserves existing valid media MIME type', () => {
    const originalBlob = new Blob(['sample video data'], { type: 'video/webm' });
    const preservedBlob = ensureMediaBlob(originalBlob, 'webm', 'video');

    expect(preservedBlob.type).toBe('video/webm');
  });

  it('falls back to original blob when extension is unknown', () => {
    const rawBlob = new Blob(['sample data'], { type: 'application/octet-stream' });
    const result = ensureMediaBlob(rawBlob, 'xyz', 'audio');

    expect(result).toBe(rawBlob);
  });
});
