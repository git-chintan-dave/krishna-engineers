import { statusOptions } from '@/config/status';

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

export const toolPage = {
    title: 'Tools',
    description: 'Manage tools and their available quantities.',
    createLabel: 'Add Tool',

    searchPlaceholder: 'Search tools...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    typePlaceholder: 'All Types',
    statusPlaceholder: 'All Status',

    emptyText: 'No tools found.',
    itemLabel: 'tools',
    resourceLabel: 'tools',

    form: {
        toolTitle: 'Tool Information',
        toolDescription:
            'Enter the basic information and available quantity for the tool.',

        nameLabel: 'Tool Name',
        namePlaceholder: 'Enter tool name',

        typeLabel: 'Type',
        typePlaceholder: 'Select tool type',

        imageLabel: 'Image',
        imagePlaceholder: 'Enter image path or URL',

        quantityLabel: 'Quantity',
        quantityPlaceholder: 'Enter quantity',

        statusLabel: 'Status',

        createTitle: 'Create Tool',
        createDescription: 'Add a new tool to the inventory.',
        createSubmitLabel: 'Save Tool',

        editTitle: 'Edit Tool',
        editDescription: 'Update the tool information.',
        editSubmitLabel: 'Update Tool',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
