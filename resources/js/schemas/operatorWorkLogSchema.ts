import {z} from 'zod';

import {
    durationSecondsSchema,
    optionalLongText,
    requiredDropdown,
    requiredNonNegativeNumber,
    requiredStringDropdown,
} from '@/schemas/commonSchema';

export const operatorWorkLogSchema = z
    .object({
        user_id: requiredDropdown('a operator'),
        work_date: z.string().min(1, 'Please select a work date.'),
        shift: requiredStringDropdown('shift'),
        machine_id: requiredDropdown('a machine'),
        part_id: requiredDropdown('a part'),
        setup_id: requiredDropdown('a setup'),
        part_setup_id: requiredDropdown('a setup'),
        actual_cycle_time: durationSecondsSchema(
            'Please enter a valid cycle time.',
        ),
        quantity: requiredNonNegativeNumber('Quantity'),
        rejection_quantity: requiredNonNegativeNumber('Rejection quantity'),
        remarks: optionalLongText('Remarks'),
    })
    .superRefine((data, ctx) => {
        if (
            data.rejection_quantity != null &&
            data.rejection_quantity > data.quantity
        ) {
            ctx.addIssue({
                code: 'custom',
                path: ['rejection_quantity'],
                message: 'Rejection quantity cannot exceed total quantity.',
            });
        }
    });

export type OperatorWorkLogSchemaData = z.infer<typeof operatorWorkLogSchema>;
