import { z } from 'zod'

export const authorizationErrorSchema = z.object({
  reason: z.string(),
})
