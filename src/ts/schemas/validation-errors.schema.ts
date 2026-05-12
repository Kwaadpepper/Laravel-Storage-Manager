import { z } from 'zod'

export const validationErrors = z.object({
  errors: z.record(z.string(), z.string()),
})
