import { Path } from '@ts/types';
import * as z from 'zod/v4';

export const propertiesResponseSchema = z.object({
  path: z.string().transform((path) => path as Path),
  basename: z.string(),
  dirname: z.string(),
  timestamp: z.string(),
  visibility: z.enum(['public', 'private']).nullable().optional(),
  isFile: z.boolean().optional(),
  isDirectory: z.boolean().optional(),
  extension: z.string().nullable().optional(),
  size: z.number().nullable().optional(),
  mimeType: z.string().nullable().optional(),
  publicUrl: z.string().nullable().optional(),
  checksum: z.string().nullable().optional(),
  dimensions: z
    .object({
      width: z.number(),
      height: z.number(),
      aspectRatio: z.string(),
    })
    .nullable()
    .optional(),
  exif: z
    .object({
      make: z.string().nullable().optional(),
      model: z.string().nullable().optional(),
      lens: z.string().nullable().optional(),
      aperture: z.string().nullable().optional(),
      exposureTime: z.string().nullable().optional(),
      iso: z.number().nullable().optional(),
      focalLength: z.string().nullable().optional(),
      focalLengthIn35mm: z.string().nullable().optional(),
      flash: z.string().nullable().optional(),
      whiteBalance: z.string().nullable().optional(),
      meteringMode: z.string().nullable().optional(),
      dateTaken: z.string().nullable().optional(),
      gps: z
        .object({
          latitude: z.number(),
          longitude: z.number(),
          altitude: z.number().nullable().optional(),
          mapsUrl: z.string(),
          googleMapsUrl: z.string(),
        })
        .nullable()
        .optional(),
      software: z.string().nullable().optional(),
      artist: z.string().nullable().optional(),
      copyright: z.string().nullable().optional(),
    })
    .nullable()
    .optional(),
  rawExif: z.record(z.string(), z.union([z.string(), z.number(), z.boolean()])).nullable().optional(),
});

export type PropertiesResponse = z.infer<typeof propertiesResponseSchema>;
