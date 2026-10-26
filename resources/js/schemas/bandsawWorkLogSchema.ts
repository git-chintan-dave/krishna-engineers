import { z } from 'zod';

import {
    optionalLongText,
    requiredDropdown,
    requiredNonNegativeInteger,
} from '@/schemas/commonSchema';

export const bandsawWorkLogSchema = z.object({
    user_id: requiredDropdown('a bandsaw operator'),
    work_date: z.string().min(1, 'Please select a work date.'),
    part_id: requiredDropdown('a part'),
    section_id: requiredDropdown('a section'),
    section_size_id: requiredDropdown('a section size'),
    quantity: requiredNonNegativeInteger('Quantity'),
    remarks: optionalLongText('Remarks'),
});
