import { AUDIO_MIME_MAP, VIDEO_MIME_MAP } from './media-mime';
import { SUPPORTED_LANGUAGES } from './supported-languages';

export const SUPPORTED_AUDIO_EXTENSIONS: readonly string[] = Object.keys(AUDIO_MIME_MAP);

export const SUPPORTED_IMAGE_EXTENSIONS: readonly string[] = [
  'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif', 'tiff', 'tif',
];

export const SUPPORTED_TEXT_EXTENSIONS: readonly string[] = [
  ...new Set(Object.values(SUPPORTED_LANGUAGES).flat()),
  'txt', 'text', 'log', 'env',
  'gitignore', 'dockerignore', 'editorconfig',
  'lock', 'ini', 'cfg', 'conf', 'csv', 'tsv'
];

export const SUPPORTED_VIDEO_EXTENSIONS: readonly string[] = Object.keys(VIDEO_MIME_MAP);

export const SUPPORTED_PDF_EXTENSIONS: readonly string[] = ['pdf'];
