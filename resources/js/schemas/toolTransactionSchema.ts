import {z} from 'zod';

const MAX_UNSIGNED_INTEGER = 4_294_967_295;
const MIN_SIGNED_INTEGER = -2_147_483_648;

export const toolTransactionSchema = z
    .object({
        toolId: z.number().int().positive().nullable(),
        type: z.string().trim(),
        quantity: z.number().int().nullable(),
        fromUserId: z.number().int().positive().nullable(),
        toUserId: z.number().int().positive().nullable(),
        partId: z.number().int().positive().nullable(),
        setupId: z.number().int().positive().nullable(),
        returnScrap: z.boolean().nullable(),
        remarks: z.string().max(
            5000,
            'Remarks must not exceed 5000 characters.',
        ),
    })
    .superRefine((data, ctx) => {
        if (!data.type) {
            ctx.addIssue({
                code: 'custom',
                path: ['type'],
                message: 'Please select a transaction type.',
            });
        }

        if (data.toolId === null) {
            ctx.addIssue({
                code: 'custom',
                path: ['toolId'],
                message: 'Please select a tool.',
            });
        }

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

        if (data.type === 'issue' && data.toUserId === null) {
            ctx.addIssue({
                code: 'custom',
                path: ['toUserId'],
                message: 'Please select a recipient.',
            });
        }

        if (
            data.fromUserId !== null &&
            data.toUserId !== null &&
            data.fromUserId === data.toUserId
        ) {
            ctx.addIssue({
                code: 'custom',
                path: ['toUserId'],
                message: 'From User and To User must be different.',
            });
        }
    });

export type ToolTransactionValidationField =
    | 'toolId'
    | 'type'
    | 'quantity'
    | 'fromUserId'
    | 'toUserId'
    | 'partId'
    | 'setupId'
    | 'returnScrap'
    | 'remarks';
