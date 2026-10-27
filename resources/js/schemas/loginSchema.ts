import {z} from 'zod';

import {passwordSchema, requiredText} from '@/schemas/commonSchema';

export const loginSchema = z.object({
    employee_code: requiredText('Employee code'),
    password: passwordSchema,
    remember: z.boolean().default(false),
});

export type LoginSchemaData = z.infer<typeof loginSchema>;
