import z from 'zod/v4';

export const apiResponseSchema = z.object({
  timestamp: z.coerce.date(),
  status: z.coerce.number(),
  message: z.string().nonempty(),
  data: z.record(z.string(), z.unknown()),
})

export type ApiResponse = z.infer<typeof apiResponseSchema>
