import * as z from 'zod/v4';

export const authorizationErrorSchema = z.object({
  reason: z.string(),
})
