import { Path } from '@ts/types';

export interface FileDimensions {
  width: number;
  height: number;
  aspectRatio: string;
}

export interface FileGpsMetadata {
  latitude: number;
  longitude: number;
  altitude: number | null;
  mapsUrl: string;
  googleMapsUrl: string;
}

export interface FileExifMetadata {
  make: string | null;
  model: string | null;
  lens: string | null;
  aperture: string | null;
  exposureTime: string | null;
  iso: number | null;
  focalLength: string | null;
  focalLengthIn35mm: string | null;
  flash: string | null;
  whiteBalance: string | null;
  meteringMode: string | null;
  dateTaken: string | null;
  gps: FileGpsMetadata | null;
  software: string | null;
  artist: string | null;
  copyright: string | null;
}

export interface FileMetadata {
  path: Path;
  basename: string;
  dirname: string;
  timestamp: string;
  visibility: 'public' | 'private' | null;
  isFile: boolean;
  isDirectory?: boolean;
  extension: string | null;
  size: number | null;
  mimeType: string | null;
  publicUrl: string | null;
  checksum: string | null;
  dimensions: FileDimensions | null;
  exif: FileExifMetadata | null;
  rawExif: Record<string, string | number | boolean> | null;
}
