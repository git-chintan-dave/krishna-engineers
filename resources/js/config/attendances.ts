export const attendancePage = {
    title: 'Attendance',
    description: 'Manage employee attendance and working hours.',

    createLabel: 'Add Attendance',

    searchPlaceholder: 'Search employees...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    employeePlaceholder: 'All Employees',

    emptyText: 'No attendance records found.',
    itemLabel: 'attendance records',
    resourceLabel: 'attendance records',

    form: {
        sectionTitle: 'Attendance Information',
        sectionDescription:
            'Enter employee attendance details and working times.',

        employeeLabel: 'Employee',
        employeePlaceholder: 'Select employee',

        attendanceDateLabel: 'Attendance Date',
        attendanceDatePlaceholder: 'Select attendance date',

        startAtLabel: 'Clock In',
        endAtLabel: 'Clock Out',

        createTitle: 'Add Attendance',
        createDescription:
            'Record an employee attendance entry.',
        createSubmitLabel: 'Save Attendance',

        editTitle: 'Edit Attendance',
        editDescription:
            'Update employee attendance details.',
        editSubmitLabel: 'Update Attendance',

        cancelLabel: 'Cancel',
    },
} as const;
