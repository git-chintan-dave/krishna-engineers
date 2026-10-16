import {statusOptions} from '@/config/status';

export const sectionPage = {
    title: 'Sections',
    description: 'Manage raw material sections and their availability.',
    createLabel: 'Add Section',
    searchPlaceholder: 'Search sections...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',
    statusPlaceholder: 'All Status',
    emptyText: 'No sections found.',
    itemLabel: 'sections',
    resourceLabel: 'sections',

    form: {
        sectionTitle: 'Section Information',
        sectionDescription:
            'Enter the basic information for the raw material section.',

        nameLabel: 'Section Name',
        namePlaceholder: 'Enter section name',

        statusLabel: 'Status',

        createTitle: 'Create Section',
        createDescription: 'Add a new raw material section.',
        createSubmitLabel: 'Save Section',

        editTitle: 'Edit Section',
        editDescription: 'Update the section information.',
        editSubmitLabel: 'Update Section',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
