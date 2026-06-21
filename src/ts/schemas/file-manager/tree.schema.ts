import { Path } from '@ts/types';
import * as z from 'zod/v4';

export const treeResponseSchema = z.object({
  directories: z.array(z.object({
    path: z.string().transform((path) => path as Path),
    hasSubDirectories: z.boolean(),
    visibility: z.enum(['public', 'private']).nullable(),
  })),
  files: z.array(z.object({
    path: z.string().transform((path) => path as Path),
    size: z.number().nonnegative(),
    extension: z.string().nonempty().nullable(),
    visibility: z.enum(['public', 'private']).nullable(),
  }))
})

export type TreeResponse = z.infer<typeof treeResponseSchema>
