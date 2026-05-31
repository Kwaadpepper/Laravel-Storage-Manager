import * as z from 'zod/v4';

export const existsResponseSchema = z.object({
    exists: z.boolean(),
})

export type ExistsResponse = z.infer<typeof existsResponseSchema>
