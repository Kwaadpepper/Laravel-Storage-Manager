import { SUPPORTED_LANGUAGES } from './supported-languages';

export const SUPPORTED_AUDIO_EXTENSIONS: readonly string[] = [
  'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'weba',
];

export const SUPPORTED_IMAGE_EXTENSIONS: readonly string[] = [
  'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif', 'tiff', 'tif',
];

export const SUPPORTED_TEXT_EXTENSIONS: readonly string[] = [
  ...new Set(Object.values(SUPPORTED_LANGUAGES).flat()),
  'txt', 'text', 'log', 'env',
  'gitignore', 'dockerignore', 'editorconfig',
  'lock', 'ini', 'cfg', 'conf', 'csv', 'tsv'
];
