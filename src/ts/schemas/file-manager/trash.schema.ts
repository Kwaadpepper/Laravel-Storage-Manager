import { Path } from '@ts/types';
import * as z from 'zod/v4';

export const trashedItemSchema = z.object({
  id: z.string(),
  name: z.string(),
  originalPath: z.string().transform((p) => p as Path),
  type: z.enum(['file', 'directory']),
  size: z.number(),
  deletedAt: z.string(),
  disk: z.string(),
  status: z.enum(['trashed', 'restoring', 'purging']).optional(),
});

export const trashListResponseSchema = z.object({
  items: z.array(trashedItemSchema),
  total: z.number(),
});

export const restoredTrashResponseSchema = z.object({
  restored: z.array(
    z.object({
      id: z.string(),
      path: z.string(),
    })
  ),
});

export const purgedTrashResponseSchema = z.object({
  ids: z.array(z.string()),
  count: z.number(),
});

export const deletedResponseSchema = z.object({
  trashed: z.boolean().optional(),
  trashId: z.string().nullable().optional(),
});
