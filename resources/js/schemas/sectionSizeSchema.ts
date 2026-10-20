import {z} from 'zod';

import {requiredDropdown, requiredNonNegativeNumber, requiredText,} from '@/schemas/commonSchema';

export const sectionSizeSchema = z.object({
    section_id: requiredDropdown('a section'),
    size: requiredText('Size'),
    weight: requiredNonNegativeNumber('Weight'),
});

export type SectionSizeSchemaData = z.infer<typeof sectionSizeSchema>;
