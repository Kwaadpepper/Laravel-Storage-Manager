import { z } from 'zod/v4';

export const uploadInitSchema = z.object({
  upload_id: z.string().uuid(),
});

export const uploadStatusSchema = z.object({
  status: z.enum(['waiting', 'assembling', 'transferring', 'completed', 'error']),
  progress: z.number().min(0).max(100),
});

export const uploadSuccessSchema = z.object({
  // usually empty, but we can accept any
}).passthrough();
