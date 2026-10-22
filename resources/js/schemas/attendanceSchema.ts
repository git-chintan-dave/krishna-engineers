import { z } from 'zod';

const requiredDate = (label: string) =>
    z
        .string()
        .min(1, `${label} is required.`)
        .refine(
            (value) => !Number.isNaN(Date.parse(value)),
            `${label} must be a valid date and time.`,
        );

const optionalDate = (label: string) =>
    z
        .string()
        .nullable()
        .optional()
        .refine(
            (value) => !value || !Number.isNaN(Date.parse(value)),
            `${label} must be a valid date and time.`,
        );

const breakSchema = z.object({
    uuid: z.string().optional(),
    start_at: requiredDate('Break start time'),
    end_at: requiredDate('Break end time'),
});

const isValidDateTime = (value: string): boolean => {
    const normalizedValue = value.replace(' ', 'T');

    return (
        /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/.test(normalizedValue) &&
        !Number.isNaN(Date.parse(normalizedValue))
    );
};

export const createAttendanceSchema = z.object({
    user_id: z.union([
        z.number().int().positive(),
        z.string().regex(/^[1-9]\d*$/, 'Please select an employee.'),
    ]),
    attendance_date: z.string().min(1, 'Attendance date is required.'),
    start_at: requiredDate('Clock-in time'),
    end_at: optionalDate('Clock-out time'),
    breaks: z.array(breakSchema),
});

export const editAttendanceSchema = createAttendanceSchema.extend({
    start_at: requiredDate('Clock-in time'),
    end_at: z
        .string()
        .nullable()
        .optional()
        .refine(
            (value) => !value || isValidDateTime(value),
            'Clock-out time must be a valid date and time.',
        ),
});

export const attendanceSchema = createAttendanceSchema;

export type AttendanceFormData = z.infer<typeof createAttendanceSchema>;
