export const AUDIO_MIME_MAP: Record<string, string> = {
  mp3: 'audio/mpeg',
  wav: 'audio/wav',
  ogg: 'audio/ogg',
  flac: 'audio/flac',
  m4a: 'audio/mp4',
  aac: 'audio/aac',
  opus: 'audio/opus',
  weba: 'audio/webm',
};

export const VIDEO_MIME_MAP: Record<string, string> = {
  mp4: 'video/mp4',
  webm: 'video/webm',
  ogg: 'video/ogg',
  ogv: 'video/ogg',
  mov: 'video/quicktime',
  m4v: 'video/mp4',
  mkv: 'video/x-matroska',
};

export function getAudioMimeType(extension?: string | null): string | null {
  if (!extension) return null;
  const ext = extension.toLowerCase().replace(/^\./, '');
  return AUDIO_MIME_MAP[ext] ?? null;
}

export function getVideoMimeType(extension?: string | null): string | null {
  if (!extension) return null;
  const ext = extension.toLowerCase().replace(/^\./, '');
  return VIDEO_MIME_MAP[ext] ?? null;
}

/**
 * Ensures that a Blob has a valid audio or video MIME type so that browsers
 * decode and play the media stream reliably.
 */
export function ensureMediaBlob(blob: Blob, extension?: string | null, type: 'audio' | 'video' = 'audio'): Blob {
  const mimeResolver = type === 'audio' ? getAudioMimeType : getVideoMimeType;
  const resolvedMime = mimeResolver(extension);

  if (!resolvedMime) {
    return blob;
  }

  // If blob already has the expected type, return as-is
  if (blob.type && blob.type !== 'application/octet-stream' && blob.type.startsWith(`${type}/`)) {
    return blob;
  }

  return new Blob([blob], { type: resolvedMime });
}
