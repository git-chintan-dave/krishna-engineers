export const operatorWorkLogPage = {
    title: 'Operator Work Logs',
    description: 'Manage and review production work logs recorded by operators.',

    createLabel: 'Add Work Log',

    searchPlaceholder: 'Search work logs...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    workDatePlaceholder: 'All Work Dates',
    shiftPlaceholder: 'All Shifts',
    operatorPlaceholder: 'All Operators',
    machinePlaceholder: 'All Machines',
    partPlaceholder: 'All Parts',
    setupPlaceholder: 'All Setups',

    emptyText: 'No operator work logs found.',
    itemLabel: 'work logs',
    resourceLabel: 'operator work log',

    form: {
        sectionTitle: 'Production Work',
        sectionDescription:
            'Enter the production work completed by the operator.',

        workDateLabel: 'Work Date',
        workDatePlaceholder: 'Select work date',

        shiftLabel: 'Shift',
        shiftPlaceholder: 'Select shift',

        operatorLabel: 'Operator',
        operatorPlaceholder: 'Select operator',

        machineLabel: 'Machine',
        machinePlaceholder: 'Select machine',

        partLabel: 'Part',
        partPlaceholder: 'Select part',

        setupLabel: 'Setup',
        setupPlaceholder: 'Select setup',

        machineCycleTimeLabel: 'Machine Cycle Time',
        actualCycleTimeLabel: 'Actual Cycle Time',

        quantityLabel: 'Quantity',
        quantityPlaceholder: 'Enter quantity',

        rejectionQuantityLabel: 'Rejection Quantity',
        rejectionQuantityPlaceholder: 'Enter rejection quantity',

        remarksLabel: 'Remarks',
        remarksPlaceholder: 'Enter remarks',

        productionTimeLabel: 'Production Time',

        createTitle: 'Create Operator Work Log',
        createDescription:
            'Add a new production work log for an operator.',
        createSubmitLabel: 'Save Work Log',

        editTitle: 'Edit Operator Work Log',
        editDescription:
            'Update the operator production work log.',
        editSubmitLabel: 'Update Work Log',

        cancelLabel: 'Cancel',
    },
} as const;
