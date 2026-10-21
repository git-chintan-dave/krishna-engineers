import {z} from 'zod';

import {requiredDropdown, requiredInteger, requiredStringDropdown,} from '@/schemas/commonSchema';

const MAX_UNSIGNED_INTEGER = 4_294_967_295;

export const rawMaterialTransactionSchema = z
    .object({
        section_id: requiredDropdown('a section'),
        section_size_id: requiredDropdown('a section size'),
        type: requiredStringDropdown('a transaction type'),
        quantity: requiredInteger('Quantity'),
        remarks: z
            .string()
            .max(5000, 'Remarks must not exceed 5000 characters.')
            .nullable()
            .optional(),
    })
    .superRefine((data, ctx) => {
        if (typeof data.quantity !== 'number') {
            return;
        }

        if (data.quantity < 1) {
            ctx.addIssue({
                code: 'custom',
                path: ['quantity'],
                message: 'Quantity must be at least 1.',
            });
        }

        if (data.quantity > MAX_UNSIGNED_INTEGER) {
            ctx.addIssue({
                code: 'custom',
                path: ['quantity'],
                message: `Quantity must not exceed ${MAX_UNSIGNED_INTEGER}.`,
            });
        }
    });

export type RawMaterialTransactionValidationField =
    | 'section_id'
    | 'section_size_id'
    | 'type'
    | 'quantity'
    | 'remarks';
