import {statusOptions} from '@/config/status';

export const setupPage = {
    title: 'Setups',
    description: 'Manage production setups.',

    createLabel: 'Add Setup',

    searchPlaceholder: 'Search setups...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    statusPlaceholder: 'All Status',

    emptyText: 'No setups found.',
    itemLabel: 'setups',
    resourceLabel: 'setups',

    form: {
        sectionTitle: 'Setup Information',
        sectionDescription: 'Enter the basic information for the setup.',

        nameLabel: 'Setup Name',
        namePlaceholder: 'Enter setup name',

        statusLabel: 'Status',
        statusPlaceholder: 'Select status',

        createTitle: 'Create Setup',
        createDescription: 'Add a new setup to the system.',
        createSubmitLabel: 'Save Setup',

        editTitle: 'Edit Setup',
        editDescription: 'Update the setup information.',
        editSubmitLabel: 'Update Setup',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
