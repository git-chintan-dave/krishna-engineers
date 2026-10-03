export const bandsawWorkLogPage = {
    title: 'Bandsaw Work Logs',
    description: 'Manage and review work logs recorded by Bandsaw operators.',

    createLabel: 'Add Work Log',

    searchPlaceholder: 'Search work logs...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    workDatePlaceholder: 'All Work Dates',
    bandsawPlaceholder: 'All Bandsaw Operators',
    partPlaceholder: 'All Parts',
    sectionSizePlaceholder: 'All Section Sizes',

    emptyText: 'No Bandsaw work logs found.',
    itemLabel: 'work logs',
    resourceLabel: 'Bandsaw work log',

    form: {
        sectionTitle: 'Bandsaw Work',
        sectionDescription:
            'Enter the work completed by the Bandsaw operator.',

        workDateLabel: 'Work Date',
        workDatePlaceholder: 'Select work date',

        bandsawLabel: 'Bandsaw Operator',
        bandsawPlaceholder: 'Select Bandsaw operator',

        partLabel: 'Part',
        partPlaceholder: 'Select part',

        sectionSizeLabel: 'Section Size',
        sectionSizePlaceholder: 'Select section size',

        quantityLabel: 'Quantity',
        quantityPlaceholder: 'Enter quantity',

        remarksLabel: 'Remarks',
        remarksPlaceholder: 'Enter remarks',

        createTitle: 'Create Bandsaw Work Log',
        createDescription:
            'Add a new work log for a Bandsaw operator.',
        createSubmitLabel: 'Save Work Log',

        editTitle: 'Edit Bandsaw Work Log',
        editDescription:
            'Update the Bandsaw operator work log.',
        editSubmitLabel: 'Update Work Log',

        cancelLabel: 'Cancel',
    },
} as const;
