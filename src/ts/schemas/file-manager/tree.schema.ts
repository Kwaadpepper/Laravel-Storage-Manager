import { Path } from '@ts/types';
import z from 'zod/v4';

export const treeResponseSchema = z.object({
  directories: z.array(z.object({
    path: z.string().nonempty().transform(v => v as Path),
    hasSubDirectories: z.boolean(),
  })),
  files: z.array(z.object({
    path: z.string().nonempty().transform(v => v as Path),
    size: z.number().nonnegative(),
    extension: z.string().nonempty().nullable(),
  }))
})

export type TreeResponse = z.infer<typeof treeResponseSchema>
