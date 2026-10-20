import {z} from 'zod';

import {requiredText, statusSchema} from '@/schemas/commonSchema';

export const setupSchema = z.object({
    name: requiredText('Setup name'),
    status: statusSchema,
});

export type SetupSchemaData = z.infer<typeof setupSchema>;
