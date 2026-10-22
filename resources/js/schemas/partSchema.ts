import { z } from 'zod';

import {
    optionalImage,
    optionalText,
    removeImageSchema,
    requiredDropdown,
    requiredText,
    statusSchema,
} from '@/schemas/commonSchema';

export const partSchema = z.object({
    name: requiredText('Part name'),
    new_part_name: optionalText('New part name'),
    section_id: requiredDropdown('section'),
    cutting_size: optionalText('Cutting size'),
    part_weight: optionalText('Part weight'),
    raw_material_weight: optionalText('Raw material weight'),
    image: optionalImage,
    remove_image: removeImageSchema,
    status: statusSchema,
});

export type PartSchemaData = z.infer<typeof partSchema>;
