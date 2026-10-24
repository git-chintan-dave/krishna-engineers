import { z } from 'zod';

import {
    requiredDropdown,
    requiredNonNegativeNumber,
} from '@/schemas/commonSchema';

/*
|--------------------------------------------------------------------------
| Date Validation
|--------------------------------------------------------------------------
*/

const requiredDate = (label: string) =>
    z
        .string()
        .min(1, `${label} is required.`)
        .regex(/^\d{4}-\d{2}-\d{2}$/, `${label} must be a valid date.`)
        .refine((value) => {
            const [year, month, day] = value.split('-').map(Number);

            const date = new Date(Date.UTC(year, month - 1, day));

            return (
                date.getUTCFullYear() === year &&
                date.getUTCMonth() === month - 1 &&
                date.getUTCDate() === day
            );
        }, `${label} must be a valid date.`);

/*
|--------------------------------------------------------------------------
| Number Validation
|--------------------------------------------------------------------------
*/

const requiredNumber = (label: string) =>
    z.preprocess(
        (value) => {
            if (
                value === null ||
                value === undefined ||
                (typeof value === 'string' && value.trim() === '')
            ) {
                return undefined;
            }

            return typeof value === 'string' ? Number(value) : value;
        },
        z
            .number({
                error: (issue) =>
                    issue.input === undefined
                        ? `${label} is required.`
                        : `${label} must be a valid number.`,
            })
            .finite(`${label} must be a valid number.`),
    );

const requiredNonNegativeInteger = (label: string) =>
    requiredNumber(label).pipe(
        z
            .number()
            .int(`${label} must be a whole number.`)
            .nonnegative(`${label} cannot be negative.`),
    );

/*
|--------------------------------------------------------------------------
| Salary Schema
|--------------------------------------------------------------------------
*/

export const salarySchema = z
    .object({
        user_id: requiredDropdown('an employee'),

        salary_period_start: requiredDate('Salary period start date'),

        salary_period_end: requiredDate('Salary period end date'),

        late_days: requiredNonNegativeInteger('Late days'),

        upad: requiredNonNegativeNumber('UPAD'),

        paid_loan: requiredNonNegativeNumber('Paid loan'),

        adjustment: requiredNumber('Adjustment'),

        adjustment_reason: z
            .string()
            .max(5000, 'Adjustment reason must not exceed 5000 characters.')
            .nullable()
            .optional(),
    })
    .superRefine((data, ctx) => {
        /*
        |--------------------------------------------------------------------------
        | Salary Period
        |--------------------------------------------------------------------------
        */

        const startDate = new Date(
            `${data.salary_period_start}T00:00:00`,
        );

        const endDate = new Date(
            `${data.salary_period_end}T00:00:00`,
        );

        if (
            !Number.isNaN(startDate.getTime()) &&
            !Number.isNaN(endDate.getTime()) &&
            endDate < startDate
        ) {
            ctx.addIssue({
                code: 'custom',
                path: ['salary_period_end'],
                message:
                    'Salary period end date must be on or after the start date.',
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Adjustment Reason
        |--------------------------------------------------------------------------
        */

        if (data.adjustment > 0 && !data.adjustment_reason?.trim()) {
            ctx.addIssue({
                code: 'custom',
                path: ['adjustment_reason'],
                message:
                    'Adjustment reason is required for a positive adjustment.',
            });
        }
    });

export type SalarySchemaData = z.infer<typeof salarySchema>;
