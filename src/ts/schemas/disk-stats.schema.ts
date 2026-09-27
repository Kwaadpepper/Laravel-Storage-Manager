import * as z from 'zod/v4';

export const diskStatsSchema = z.object({
  disk: z.string(),
  driver: z.string(),
  supported: z.boolean(),
  total: z.number().nullable(),
  free: z.number().nullable(),
  used: z.number().nullable(),
  percentage: z.number().nullable(),
  mode: z.enum(['partition', 'quota', 'folder', 'unlimited', 'disabled']),
});

export type DiskStatsSchema = z.infer<typeof diskStatsSchema>;
