import {z} from 'zod';

import {
    optionalImage,
    removeImageSchema,
    requiredStringDropdown,
    requiredText,
    statusSchema,
} from '@/schemas/commonSchema';

export const toolSchema = z.object({
    type: requiredStringDropdown('tool type'),
    name: requiredText('Tool name'),
    image: optionalImage,
    remove_image: removeImageSchema,
    status: statusSchema,
});

export type ToolSchemaData = z.infer<typeof toolSchema>;
