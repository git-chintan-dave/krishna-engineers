import {z} from 'zod';

export const machineSchema = z.object({
    name: z.string()
        .trim()
        .min(1, 'Machine name is required.')
        .max(255, 'Machine name must not exceed 255 characters.'),

    machine_number: z.string()
        .max(255, 'Machine number must not exceed 255 characters.')
        .nullable()
        .optional(),

    status: z.boolean().default(true),
});
