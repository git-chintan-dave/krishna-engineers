import { z } from 'zod';

import {
    requiredDropdown,
    requiredStringDropdown,
} from '@/schemas/commonSchema';

const MAX_UNSIGNED_INTEGER = 4_294_967_295;
const MIN_SIGNED_INTEGER = -2_147_483_648;

export const rawMaterialTransactionSchema = z
    .object({
        section_id: requiredDropdown('a section'),
        section_size_id: requiredDropdown('a section size'),
        type: requiredStringDropdown('a transaction type'),
        quantity: z.number().int().nullable(),
        remarks: z
            .string()
            .max(5000, 'Remarks must not exceed 5000 characters.')
            .nullable()
            .optional(),
    })
    .superRefine((data, ctx) => {
        if (data.quantity === null) {
            ctx.addIssue({
                code: 'custom',
                path: ['quantity'],
                message: 'Quantity is required.',
            });
        } else {
            if (data.quantity > MAX_UNSIGNED_INTEGER) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['quantity'],
                    message: `Quantity must not exceed ${MAX_UNSIGNED_INTEGER}.`,
                });
            }

            if (data.type === 'adjustment') {
                if (data.quantity === 0) {
                    ctx.addIssue({
                        code: 'custom',
                        path: ['quantity'],
                        message: 'Adjustment quantity cannot be zero.',
                    });
                }

                if (data.quantity < MIN_SIGNED_INTEGER) {
                    ctx.addIssue({
                        code: 'custom',
                        path: ['quantity'],
                        message: `Adjustment quantity must be at least ${MIN_SIGNED_INTEGER}.`,
                    });
                }
            } else if (data.quantity < 1) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['quantity'],
                    message: 'Quantity must be at least 1.',
                });
            }
        }
    });

export type RawMaterialTransactionValidationField =
    | 'section_id'
    | 'section_size_id'
    | 'type'
    | 'quantity'
    | 'remarks';
