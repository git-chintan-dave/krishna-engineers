import {z} from 'zod';

import {requiredDropdown, requiredNonNegativeNumber, requiredStringDropdown, timeSchema,} from '@/schemas/commonSchema';

export const operatorWorkLogSchema = z.object({
    user_id: requiredDropdown('a operator'),
    work_date: z.string()
        .min(1, 'Please select a work date.'),
    shift: requiredStringDropdown('shift'),
    machine_id: requiredDropdown('a machine'),
    part_id: requiredDropdown('a part'),
    setup_id: requiredDropdown('a setup'),
    part_setup_id: requiredDropdown('a setup'),
    machine_cycle_time: timeSchema('Please enter a valid cycle time in HH:MM:SS format.'),
    actual_cycle_time: timeSchema('Please enter a valid cycle time in HH:MM:SS format.'),
    quantity: requiredNonNegativeNumber('Quantity'),
    rejection_quantity: requiredNonNegativeNumber('Rejection quantity'),
    remarks: z.string()
        .max(5000, 'Remarks must not exceed 5000 characters.')
        .nullable()
        .optional(),
}).superRefine((data, ctx) => {
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
