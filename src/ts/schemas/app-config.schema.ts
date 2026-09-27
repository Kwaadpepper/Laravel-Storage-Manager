import { Disk } from '@ts/types';
import * as z from 'zod/v4';

export const appConfigSchema = z.object({
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
  readOnlyDisks: z.array(z.string()).transform((arr) => arr as Disk[]),
  chunkMinSize: z.number(),
  chunkMaxSize: z.number(),
  duplicatePolicy: z.enum(['overwrite', 'auto_rename', 'error']),
  sanitizeFilenames: z.boolean(),
  supportsZip: z.boolean().default(false),
  routes: z.object({
    disksSelect: z.url(),
    fmUploadInit: z.url(),
    fmUploadChunk: z.url(),
    fmUploadComplete: z.url(),
  }).catchall(z.string())
})

export type AppConfig = z.infer<typeof appConfigSchema>
