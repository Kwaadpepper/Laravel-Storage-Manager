import { Disk } from '@ts/types';
import * as z from 'zod/v4';

export const diskConfigSchema = z.object({
  name: z.string(),
  driver: z.string().optional(),
  readOnly: z.boolean().default(false),
  supportsTrash: z.boolean().default(true),
  allowedExtensions: z.array(z.string()).optional(),
  maxFileSize: z.number().nullable().optional(),
  filenamePolicy: z.enum(['strict', 'permissive']).default('strict'),
  duplicatePolicy: z.enum(['overwrite', 'auto_rename', 'error']).optional(),
});

export type DiskConfig = z.infer<typeof diskConfigSchema>;

export const appConfigSchema = z.object({
  cspNonce: z.string().nullable().optional(),
  locale: z.string().default('en'),
  translations: z.record(z.string(), z.string()).default({}),
  routePrefix: z.string().default('/sm'),
  packageName: z.string().nonempty(),
  packageVersion: z.string().nonempty(),
  packageLogo: z.url(),
  composerPackageName: z.string().nonempty(),
  appDescription: z.string().nonempty(),
  appAuthors: z.array(
    z.object({
      name: z.string().nonempty(),
      email: z.email()
    })
  ),
  disks: z.record(z.string(), z.string()).transform((disksMap) => Object.values(disksMap) as Disk[]),
  disksConfig: z.record(z.string(), diskConfigSchema).optional().default({}),
  readOnlyDisks: z.array(z.string()).transform((arr) => arr as Disk[]),
  allowedExtensions: z.array(z.string()).default([]),
  chunkMinSize: z.number(),
  chunkMaxSize: z.number(),
  duplicatePolicy: z.enum(['overwrite', 'auto_rename', 'error']),
  sanitizeFilenames: z.boolean(),
  supportsZip: z.boolean().default(false),
  supportsThumbnails: z.boolean().default(true),
  supportsDiskStats: z.boolean().default(true),
  supportsTrash: z.boolean().default(true),
  routes: z.object({
    disksSelect: z.url(),
    disksStats: z.url().optional(),
    fmUploadInit: z.url(),
    fmUploadChunk: z.url(),
    fmUploadComplete: z.url(),
  }).catchall(z.string())
})

export type AppConfig = z.infer<typeof appConfigSchema>
