import * as z from 'zod/v4';

export const domainValidationErrorSchema = z.object({
  errors: z.object({
    code: z.number().int(),
    message: z.string(),
  }),
})
