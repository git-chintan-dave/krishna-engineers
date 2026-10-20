import {z} from 'zod';

import {requiredNonNegativeNumber, requiredStringDropdown, requiredText, statusSchema,} from '@/schemas/commonSchema';

const optionalDigits = (length: number, label: string) =>
    z
        .string()
        .trim()
        .refine(
            (value) => value === '' || new RegExp(`^\\d{${length}}$`).test(value),
            `${label} must be exactly ${length} digits.`,
        )
        .nullable()
        .optional();

const optionalDate = (label: string) =>
    z
        .string()
        .trim()
        .refine(
            (value) => value === '' || !Number.isNaN(Date.parse(value)),
            `${label} must be a valid date.`,
        )
        .nullable()
        .optional();

const passwordSchema = z
    .string()
    .nullable()
    .optional();

const baseEmployeeSchema = z.object({
    name: requiredText('Employee name'),

    employee_code: requiredText('Employee code'),

    phone_number: optionalDigits(10, 'Phone number'),

    alternate_phone_number: optionalDigits(
        10,
        'Alternate phone number',
    ),

    email: z
        .email('Please enter a valid email address.')
        .max(255, 'Email must not exceed 255 characters.')
        .nullable()
        .optional()
        .or(z.literal('')),

    joining_date: optionalDate('Joining date'),

    aadhar_number: optionalDigits(
        12,
        'Aadhaar number',
    ),

    password: passwordSchema,

    password_confirmation: passwordSchema,

    role: requiredStringDropdown('role'),

    shift: z
        .string()
        .nullable()
        .optional()
        .or(z.literal('')),

    salary: requiredNonNegativeNumber('Salary'),

    incentive_per_day: requiredNonNegativeNumber(
        'Incentive per day',
    ),

    deduction_per_day: requiredNonNegativeNumber(
        'Deduction per day',
    ),

    status: statusSchema,
});

const validatePassword = (
    data: z.infer<typeof baseEmployeeSchema>,
    ctx: z.RefinementCtx,
) => {
    const password = data.password ?? '';
    const passwordConfirmation = data.password_confirmation ?? '';

    if (password === '') {
        return;
    }

    if (password.length < 8) {
        ctx.addIssue({
            code: 'custom',
            path: ['password'],
            message: 'Password must be at least 8 characters.',
        });
    }

    if (password !== passwordConfirmation) {
        ctx.addIssue({
            code: 'custom',
            path: ['password_confirmation'],
            message: 'Password confirmation does not match.',
        });
    }
};

export const createEmployeeSchema = baseEmployeeSchema
    .extend({
        password: z
            .string({
                error: (issue) =>
                    issue.input === undefined ||
                    issue.input === null ||
                    issue.input === ''
                        ? 'Password is required.'
                        : 'Password must be a valid string.',
            })
            .min(8, 'Password must be at least 8 characters.'),

        password_confirmation: z
            .string({
                error: (issue) =>
                    issue.input === undefined ||
                    issue.input === null ||
                    issue.input === ''
                        ? 'Password confirmation is required.'
                        : 'Password confirmation must be a valid string.',
            })
            .min(1, 'Password confirmation is required.'),
    })
    .superRefine(validatePassword);

export const editEmployeeSchema = baseEmployeeSchema.superRefine(
    validatePassword,
);

export const employeeSchema = createEmployeeSchema;

export type EmployeeFormData = z.infer<typeof employeeSchema>;
