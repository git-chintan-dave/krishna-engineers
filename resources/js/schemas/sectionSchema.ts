import {z} from 'zod';

import {requiredText, statusSchema} from '@/schemas/commonSchema';

export const sectionSchema = z.object({
    name: requiredText('Section name'),
    status: statusSchema,
});

export type SectionSchemaData = z.infer<typeof sectionSchema>;
