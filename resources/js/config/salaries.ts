export const salaryPage = {
    title: 'Salaries',
    description: 'Manage employee salaries and payroll records.',

    createLabel: 'Add Salary',

    searchPlaceholder: 'Search employees...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    employeePlaceholder: 'All Employees',
    statusPlaceholder: 'All Status',

    emptyText: 'No salary records found.',
    itemLabel: 'salary records',
    resourceLabel: 'salary records',

    form: {
        sectionTitle: 'Salary Information',
        sectionDescription:
            'Enter the salary period and employee payment details.',

        employeeLabel: 'Employee',
        employeePlaceholder: 'Select employee',

        salaryPeriodStartLabel: 'Period Start',
        salaryPeriodStartPlaceholder: 'Select start date',

        salaryPeriodEndLabel: 'Period End',
        salaryPeriodEndPlaceholder: 'Select end date',

        baseSalaryLabel: 'Base Salary',
        baseSalaryPlaceholder: 'Base salary',

        workingDaysLabel: 'Working Days',

        incentiveRateLabel: 'Incentive Per Day',
        incentiveLabel: 'Incentive',

        deductionRateLabel: 'Deduction Per Day',
        deductionLabel: 'Deduction',

        upadLabel: 'Upad',
        upadPlaceholder: 'Enter upad amount',

        paidLoanLabel: 'Paid Loan',
        paidLoanPlaceholder: 'Enter paid loan amount',

        adjustmentLabel: 'Adjustment',
        adjustmentPlaceholder: 'Enter adjustment amount',

        adjustmentReasonLabel: 'Adjustment Reason',
        adjustmentReasonPlaceholder:
            'Enter the reason for the adjustment',

        finalPayableLabel: 'Final Payable',

        statusLabel: 'Status',
        statusPlaceholder: 'Select status',

        createTitle: 'Create Salary',
        createDescription:
            'Generate a salary record for an employee and period.',
        createSubmitLabel: 'Save Salary',

        editTitle: 'Edit Salary',
        editDescription:
            'Update the salary adjustments and payment details.',
        editSubmitLabel: 'Update Salary',

        cancelLabel: 'Cancel',
    },
} as const;
