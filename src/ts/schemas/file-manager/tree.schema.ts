import { Path } from '@ts/types';
import * as z from 'zod/v4';

export const paginationMetaSchema = z.object({
  currentPage: z.number(),
  perPage: z.number(),
  totalFiles: z.number(),
  totalDirectories: z.number(),
  totalPages: z.number(),
  hasMore: z.boolean(),
});

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
    publicUrl: z.string().nullable().optional(),
  })),
  pagination: paginationMetaSchema.optional(),
});

export const searchResponseSchema = z.object({
  directoriesToScan: z.array(z.string().transform((path) => path as Path)),
  matchedDirectories: z.array(z.object({
    path: z.string().transform((path) => path as Path),
    hasSubDirectories: z.boolean(),
    visibility: z.enum(['public', 'private']).nullable(),
  })),
  matchedFiles: z.array(z.object({
    path: z.string().transform((path) => path as Path),
    size: z.number().nonnegative(),
    extension: z.string().nonempty().nullable(),
    visibility: z.enum(['public', 'private']).nullable(),
    publicUrl: z.string().nullable().optional(),
  })),
});

export type TreeResponse = z.infer<typeof treeResponseSchema>;
export type SearchResponse = z.infer<typeof searchResponseSchema>;
export type PaginationMetaSchema = z.infer<typeof paginationMetaSchema>;
