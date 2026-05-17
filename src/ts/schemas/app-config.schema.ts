import { Disk } from '@ts/types';
import z from 'zod/v4';

export const appConfigSchema = z.object({
  packageName: z.string().nonempty(),
  packageVersion: z.string().nonempty(),
  composerPackageName: z.string().nonempty(),
  appDescription: z.string().nonempty(),
  appAuthors: z.array(
    z.object({
      name: z.string().nonempty(),
      email: z.email()
    })
  ),
  disks: z.record(z.string(), z.string()).transform((disksMap) => Object.values(disksMap) as Disk[]),
  routes: z.object({
    fmInit: z.url(),
    fmTree: z.url(),
    fmContent: z.url(),
    fmProperties: z.url(),
    fmCreateDirectory: z.url(),
    fmCreateFile: z.url(),
    fmDelete: z.url(),
    fmRename: z.url(),
    disksList: z.url(),
    disksSelect: z.url()
  })
})

export type AppConfig = z.infer<typeof appConfigSchema>
