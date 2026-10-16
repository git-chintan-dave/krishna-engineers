import {statusOptions} from '@/config/status';

export const machinePage = {
    title: 'Machines',
    description: 'Manage machines used for production.',

    createLabel: 'Add Machine',

    searchPlaceholder: 'Search machines...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    statusPlaceholder: 'All Status',

    emptyText: 'No machines found.',
    itemLabel: 'machines',
    resourceLabel: 'machines',

    form: {
        sectionTitle: 'Machine Information',
        sectionDescription: 'Enter the basic information for the machine.',

        nameLabel: 'Machine Name',
        namePlaceholder: 'Enter machine name',

        machineNumberLabel: 'Machine Number',
        machineNumberPlaceholder: 'Enter machine number',

        statusLabel: 'Status',
        statusPlaceholder: 'Select status',

        createTitle: 'Create Machine',
        createDescription: 'Add a new machine to the system.',
        createSubmitLabel: 'Save Machine',

        editTitle: 'Edit Machine',
        editDescription: 'Update the machine information.',
        editSubmitLabel: 'Update Machine',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
