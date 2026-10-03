export type UserRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

export type UserShift = 'day' | 'night' | '8_hr' | '8_hr_total';

export type UserStatus = 'Active' | 'Inactive';

export type User = {
    id: number;
    uuid: string;
    referenceId: string;
    name: string;
    phoneNumber: string;
    email: string;

    role: UserRole;
    shift: UserShift;

    salary: number;
    incentivePerDay: number;
    deductionPerDay: number;

    emailVerifiedAt: string | null;
    status: UserStatus;
};
