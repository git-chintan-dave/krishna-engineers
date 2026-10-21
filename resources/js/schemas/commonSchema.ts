import {z} from 'zod';

export const optionalText = (label: string) =>
    z.string()
        .max(255, `${label} must not exceed 255 characters.`)
        .nullable()
        .optional();

export const requiredText = (label: string) =>
    z.string()
        .trim()
        .min(1, `${label} is required.`)
        .max(255, `${label} must not exceed 255 characters.`);

export const statusSchema = z.boolean().default(true);

export const requiredInteger = (label: string) =>
    z.preprocess(
        (value) => {
            if (
                value === null ||
                value === undefined ||
                (typeof value === 'string' && value.trim() === '')
            ) {
                return undefined;
            }

            return typeof value === 'string'
                ? Number(value)
                : value;
        },
        z
            .number({
                error: (issue) =>
                    issue.input === undefined
                        ? `${label} is required.`
                        : `${label} must be a valid number.`,
            })
            .int(`${label} must be a whole number.`)
            .finite(`${label} must be a valid number.`),
    );

export const requiredIntegerInRange = (
    label: string,
    min: number,
    max: number,
) =>
    z.preprocess(
        (value) => {
            if (
                value === null ||
                value === undefined ||
                (typeof value === 'string' && value.trim() === '')
            ) {
                return undefined;
            }

            return typeof value === 'string'
                ? Number(value)
                : value;
        },
        z
            .number({
                error: (issue) =>
                    issue.input === undefined
                        ? `${label} is required.`
                        : `${label} must be a valid number.`,
            })
            .int(`${label} must be a whole number.`)
            .finite(`${label} must be a valid number.`)
            .min(min, `${label} must be at least ${min}.`)
            .max(max, `${label} must not exceed ${max}.`),
    );

export const requiredDropdown = (label: string) =>
    z.number({
        error: `Please select ${label}.`,
    }).int().positive(`Please select a ${label}.`);

export const requiredStringDropdown = (label: string) =>
    z.string({
        error: `Please select ${label}.`,
    }).min(1, `Please select a ${label}.`);

export const requiredNonNegativeInteger = (label: string) =>
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
            .finite(`${label} must be a valid number.`)
            .int(`${label} must be a whole number.`)
            .min(0, `${label} must be at least 0.`),
    );

export const requiredNonNegativeNumber = (label: string) =>
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
            .finite(`${label} must be a valid number.`)
            .min(0, `${label} must be at least 0.`),
    );

export const timeSchema = (message = 'Invalid time format. Expected HH:MM:SS') =>
    z.string().time({
        precision: 0,
        message,
    });

export const optionalImage = z
    .file()
    .max(5 * 1024 * 1024, 'Image must not exceed 5 MB.')
    .mime(
        ['image/jpeg', 'image/png', 'image/webp'],
        'Image must be a JPG, PNG, or WebP file.',
    )
    .optional()
    .nullable();

export const removeImageSchema = z.boolean().optional();
