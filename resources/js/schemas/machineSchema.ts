import { z } from 'zod';

import {
    optionalText,
    requiredText,
    statusSchema,
} from '@/schemas/commonSchema';

export const machineSchema = z.object({
    name: requiredText('Machine name'),
    machine_number: optionalText('Machine number'),
    status: statusSchema,
});

export type MachineSchemaData = z.infer<typeof machineSchema>;
