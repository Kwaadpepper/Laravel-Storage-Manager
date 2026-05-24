import { z } from 'zod'

export const domainValidationErrorSchema = z.object({
  errors: z.object({
    code: z.number().int(),
    message: z.string(),
  }),
})
