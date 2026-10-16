export const partSetupPage = {
    title: 'Part Setups',
    description: 'Manage machine setup configurations for parts.',
    createLabel: 'Add Part Setup',
    searchPlaceholder: 'Search parts or setups...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',
    partPlaceholder: 'All Parts',
    setupPlaceholder: 'All Setups',
    emptyText: 'No part setups found.',
    itemLabel: 'part setups',
    resourceLabel: 'part setup',

    form: {
        sectionTitle: 'Part Setup Information',
        sectionDescription: 'Configure the setup and cycle times for a part.',

        partLabel: 'Part',
        partPlaceholder: 'Select part',

        setupLabel: 'Setup',
        setupPlaceholder: 'Select setup',

        imageLabel: 'Image',
        imagePlaceholder: 'Enter image path or filename',

        machineCycleTimeLabel: 'Machine Cycle Time (Seconds)',
        machineCycleTimePlaceholder: 'Enter seconds',

        machineCycleTimeHelp:
            'Enter the machine cycle time in seconds. Example: 5130 seconds = 01:25:30.',

        actualCycleTimeLabel: 'Actual Cycle Time (Seconds)',
        actualCycleTimePlaceholder: 'Enter seconds',

        actualCycleTimeHelp:
            'Enter the default actual cycle time in seconds. Example: 5130 seconds = 01:25:30. This value can be adjusted on individual employee reports without changing this master value.',

        createTitle: 'Create Part Setup',
        createDescription: 'Add a setup configuration for a part.',
        createSubmitLabel: 'Save Part Setup',

        editTitle: 'Edit Part Setup',
        editDescription: 'Update the part setup configuration.',
        editSubmitLabel: 'Update Part Setup',

        cancelLabel: 'Cancel',
    },
} as const;
