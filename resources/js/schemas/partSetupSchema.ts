import { z } from 'zod';

import {
    optionalImage,
    removeImageSchema,
    requiredDropdown,
    requiredNonNegativeInteger,
} from '@/schemas/commonSchema';

export const partSetupSchema = z.object({
    part_id: requiredDropdown('a part'),
    setup_id: requiredDropdown('a setup'),
    machine_cycle_time: requiredNonNegativeInteger('Machine cycle time'),
    actual_cycle_time: requiredNonNegativeInteger('Actual cycle time'),
    image: optionalImage,
    remove_image: removeImageSchema,
});

export type PartSetupSchemaData = z.infer<typeof partSetupSchema>;
