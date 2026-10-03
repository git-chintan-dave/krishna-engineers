import {statusOptions} from '@/config/status';

export const employeePage = {
    title: 'Employees',
    description:
        'Manage employees, roles, shifts, and salary configuration.',

    createLabel: 'Add Employee',

    searchPlaceholder: 'Search employees...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    rolePlaceholder: 'All Roles',
    shiftPlaceholder: 'All Shifts',
    statusPlaceholder: 'All Status',

    emptyText: 'No employees found.',
    itemLabel: 'employees',
    resourceLabel: 'employees',

    form: {
        employeeSectionTitle: 'Employee Information',
        employeeSectionDescription:
            'Enter the basic information for the employee.',

        nameLabel: 'Name',
        namePlaceholder: 'Enter employee name',

        employeeCodeLabel: 'Employee Code',
        employeeCodePlaceholder:
            'Enter employee code',

        joiningDateLabel: 'Joining Date',
        joiningDatePlaceholder:
            'Select joining date',

        phoneNumberLabel: 'Phone Number',
        phoneNumberPlaceholder:
            'Enter phone number',

        alternatePhoneNumberLabel:
            'Alternate Phone Number',
        alternatePhoneNumberPlaceholder:
            'Enter alternate phone number',

        emailLabel: 'Email',
        emailPlaceholder:
            'Enter email address',

        aadharNumberLabel: 'Aadhaar Number',
        aadharNumberPlaceholder:
            'Enter Aadhaar number',

        authenticationSectionTitle:
            'Authentication',
        authenticationSectionDescription:
            'Configure the employee login credentials.',

        passwordLabel: 'Password',
        passwordPlaceholder:
            'Enter password',

        passwordConfirmationLabel:
            'Confirm Password',
        passwordConfirmationPlaceholder:
            'Confirm password',

        configurationSectionTitle:
            'Employee Configuration',
        configurationSectionDescription:
            'Configure the employee role and work shift.',

        roleLabel: 'Role',
        rolePlaceholder: 'Select role',

        shiftLabel: 'Shift',
        shiftPlaceholder: 'Select shift',

        salarySectionTitle:
            'Salary Configuration',
        salarySectionDescription:
            'Configure the employee salary and daily adjustments.',

        salaryLabel: 'Salary',
        salaryPlaceholder: 'Enter salary',

        incentivePerDayLabel:
            'Incentive Per Day',
        incentivePerDayPlaceholder:
            'Enter incentive per day',

        deductionPerDayLabel:
            'Deduction Per Day',
        deductionPerDayPlaceholder:
            'Enter deduction per day',

        accountSectionTitle:
            'Account Status',
        accountSectionDescription:
            'Control whether the employee account is active.',

        statusLabel: 'Status',
        statusPlaceholder: 'Select status',

        createTitle: 'Create Employee',
        createDescription:
            'Add a new employee to the system.',
        createSubmitLabel: 'Save Employee',

        editTitle: 'Edit Employee',
        editDescription:
            'Update the employee information.',
        editSubmitLabel: 'Update Employee',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
