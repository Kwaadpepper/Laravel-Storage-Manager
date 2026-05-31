import * as z from 'zod/v4';

export const validationErrors = z.object({
  errors: z.record(z.string(), z.string()),
})
