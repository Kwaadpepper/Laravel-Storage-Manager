import FilePdf from '../components/shared/icons/file-pdf';
import {
  File,
  FileArchive,
  FileCode,
  FileCog,
  FileImage,
  FileKey,
  FileMusic,
  FilePlay,
  FileSpreadsheet,
  FileTerminal,
  FileText,
  FileType,
  Presentation,
} from 'lucide-vue-next';
import type { Component } from 'vue';

export type FileCategory =
  | 'audio'
  | 'video'
  | 'image'
  | 'pdf'
  | 'word'
  | 'excel'
  | 'presentation'
  | 'archive'
  | 'code'
  | 'terminal'
  | 'security'
  | 'config'
  | 'font'
  | 'text'
  | 'generic';

export interface FileTypeDefinition {
  category: FileCategory;
  label: string;
  colorClass: string;
  component: Component;
}

export const CATEGORY_DEFINITIONS: Record<FileCategory, FileTypeDefinition> = {
  audio: {
    category: 'audio',
    label: 'Audio',
    colorClass: 'text-purple-500',
    component: FileMusic,
  },
  video: {
    category: 'video',
    label: 'Vidéo',
    colorClass: 'text-rose-500',
    component: FilePlay,
  },
  image: {
    category: 'image',
    label: 'Image',
    colorClass: 'text-cyan-500',
    component: FileImage,
  },
  pdf: {
    category: 'pdf',
    label: 'PDF',
    colorClass: 'text-red-500',
    component: FilePdf,
  },
  word: {
    category: 'word',
    label: 'Word',
    colorClass: 'text-blue-500',
    component: FileText,
  },
  excel: {
    category: 'excel',
    label: 'Excel',
    colorClass: 'text-emerald-500',
    component: FileSpreadsheet,
  },
  presentation: {
    category: 'presentation',
    label: 'Présentation',
    colorClass: 'text-orange-500',
    component: Presentation,
  },
  archive: {
    category: 'archive',
    label: 'Archive',
    colorClass: 'text-amber-500',
    component: FileArchive,
  },
  code: {
    category: 'code',
    label: 'Code',
    colorClass: 'text-amber-400',
    component: FileCode,
  },
  terminal: {
    category: 'terminal',
    label: 'Script',
    colorClass: 'text-lime-500',
    component: FileTerminal,
  },
  security: {
    category: 'security',
    label: 'Sécurité',
    colorClass: 'text-zinc-400',
    component: FileKey,
  },
  config: {
    category: 'config',
    label: 'Config',
    colorClass: 'text-zinc-400',
    component: FileCog,
  },
  font: {
    category: 'font',
    label: 'Police',
    colorClass: 'text-indigo-400',
    component: FileType,
  },
  text: {
    category: 'text',
    label: 'Texte',
    colorClass: 'text-slate-400',
    component: FileText,
  },
  generic: {
    category: 'generic',
    label: 'Fichier',
    colorClass: 'text-info',
    component: File,
  },
};

const EXTENSION_MAP: Record<string, FileCategory> = {
  // Audio
  mp3: 'audio',
  wav: 'audio',
  ogg: 'audio',
  flac: 'audio',
  m4a: 'audio',
  aac: 'audio',
  wma: 'audio',
  opus: 'audio',
  weba: 'audio',
  mid: 'audio',
  midi: 'audio',
  aiff: 'audio',
  alac: 'audio',

  // Video
  mp4: 'video',
  webm: 'video',
  mkv: 'video',
  avi: 'video',
  mov: 'video',
  wmv: 'video',
  flv: 'video',
  m4v: 'video',
  '3gp': 'video',
  ogv: 'video',
  vob: 'video',
  m2ts: 'video',

  // Image
  png: 'image',
  jpg: 'image',
  jpeg: 'image',
  gif: 'image',
  webp: 'image',
  svg: 'image',
  bmp: 'image',
  ico: 'image',
  avif: 'image',
  tiff: 'image',
  tif: 'image',
  psd: 'image',
  ai: 'image',
  eps: 'image',
  raw: 'image',
  cr2: 'image',
  nef: 'image',
  heic: 'image',
  heif: 'image',

  // PDF
  pdf: 'pdf',

  // Excel / Spreadsheet
  xls: 'excel',
  xlsx: 'excel',
  xlsm: 'excel',
  xlsb: 'excel',
  csv: 'excel',
  tsv: 'excel',
  ods: 'excel',
  numbers: 'excel',
  sxc: 'excel',

  // Word / Document
  doc: 'word',
  docx: 'word',
  docm: 'word',
  dot: 'word',
  dotx: 'word',
  odt: 'word',
  pages: 'word',
  rtf: 'word',

  // Presentation
  ppt: 'presentation',
  pptx: 'presentation',
  pptm: 'presentation',
  odp: 'presentation',
  keynote: 'presentation',
  pps: 'presentation',
  ppsx: 'presentation',
  pot: 'presentation',
  potx: 'presentation',

  // Archive
  zip: 'archive',
  rar: 'archive',
  '7z': 'archive',
  tar: 'archive',
  gz: 'archive',
  bz2: 'archive',
  xz: 'archive',
  tgz: 'archive',
  iso: 'archive',
  dmg: 'archive',
  pkg: 'archive',
  deb: 'archive',
  rpm: 'archive',
  apk: 'archive',
  z: 'archive',

  // Code / Web
  js: 'code',
  mjs: 'code',
  cjs: 'code',
  mts: 'code',
  cts: 'code',
  jsx: 'code',
  tsx: 'code',
  vue: 'code',
  svelte: 'code',
  astro: 'code',
  php: 'code',
  py: 'code',
  pyw: 'code',
  html: 'code',
  htm: 'code',
  xhtml: 'code',
  css: 'code',
  scss: 'code',
  sass: 'code',
  less: 'code',
  json: 'code',
  json5: 'code',
  xml: 'code',
  yaml: 'code',
  yml: 'code',
  toml: 'code',
  sql: 'code',
  c: 'code',
  h: 'code',
  cpp: 'code',
  hpp: 'code',
  cc: 'code',
  cs: 'code',
  java: 'code',
  class: 'code',
  jar: 'code',
  go: 'code',
  rs: 'code',
  rb: 'code',
  swift: 'code',
  kt: 'code',
  kts: 'code',
  dart: 'code',
  lua: 'code',
  r: 'code',
  pl: 'code',
  pm: 'code',
  graphql: 'code',
  gql: 'code',
  prisma: 'code',

  // Terminal / Scripts
  sh: 'terminal',
  bash: 'terminal',
  zsh: 'terminal',
  fish: 'terminal',
  bat: 'terminal',
  cmd: 'terminal',
  ps1: 'terminal',
  psm1: 'terminal',

  // Security / Certs / Keys
  key: 'security',
  pem: 'security',
  crt: 'security',
  cer: 'security',
  p12: 'security',
  pfx: 'security',
  gpg: 'security',
  asc: 'security',
  pub: 'security',
  sig: 'security',
  lock: 'security',

  // Config
  env: 'config',
  ini: 'config',
  conf: 'config',
  cfg: 'config',
  cnf: 'config',
  properties: 'config',

  // Font
  ttf: 'font',
  otf: 'font',
  woff: 'font',
  woff2: 'font',
  eot: 'font',
  fon: 'font',

  // Text / Docs
  txt: 'text',
  text: 'text',
  md: 'text',
  markdown: 'text',
  rst: 'text',
  log: 'text',
  tex: 'text',
  diff: 'text',
  patch: 'text',
};

const SPECIAL_FILENAMES: Record<string, FileCategory> = {
  dockerfile: 'code',
  containerfile: 'code',
  makefile: 'terminal',
  rakefile: 'terminal',
  gemfile: 'code',
  vagrantfile: 'code',
  procfile: 'code',
  license: 'text',
  licence: 'text',
  copying: 'text',
  readme: 'text',
  '.gitignore': 'config',
  '.gitattributes': 'config',
  '.editorconfig': 'config',
  '.env': 'config',
  '.env.example': 'config',
  '.env.local': 'config',
  '.env.production': 'config',
};

/**
 * Determines file category, label, styling and icon component based on filename and/or extension.
 */
export function getFileTypeInfo(
  name?: string | null,
  extension?: string | null
): FileTypeDefinition {
  const normalizedName = (name ?? '').toLowerCase().trim();

  // 1. Check exact special filenames
  if (normalizedName in SPECIAL_FILENAMES) {
    return CATEGORY_DEFINITIONS[SPECIAL_FILENAMES[normalizedName]];
  }

  // 2. Disambiguate '.ts' extension (could be video MPEG transport stream or TypeScript)
  // If the filename ends with .d.ts or typical TS patterns, it's code. But by default .ts is code in web dev context:
  let ext = '';
  if (extension && extension.trim() !== '') {
    ext = extension.replace(/^\./, '').toLowerCase().trim();
  } else if (normalizedName.includes('.')) {
    ext = normalizedName.split('.').pop() ?? '';
  }

  // Override .ts: in a dev/web file manager context, .ts is most commonly TypeScript (code) unless specifically intended
  if (ext === 'ts') {
    return CATEGORY_DEFINITIONS.code;
  }

  if (ext && ext in EXTENSION_MAP) {
    return CATEGORY_DEFINITIONS[EXTENSION_MAP[ext]];
  }

  return CATEGORY_DEFINITIONS.generic;
}
