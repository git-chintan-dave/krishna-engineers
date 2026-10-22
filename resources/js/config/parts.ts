import { statusOptions } from '@/config/status';

export const partPage = {
    title: 'Parts',
    description: 'Manage parts used for production.',
    createLabel: 'Add Part',
    searchPlaceholder: 'Search parts...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',
    sectionPlaceholder: 'All Sections',
    statusPlaceholder: 'All Status',
    emptyText: 'No parts found.',
    itemLabel: 'parts',
    resourceLabel: 'parts',

    form: {
        sectionTitle: 'Part Information',
        sectionDescription: 'Enter the basic information for the part.',

        nameLabel: 'Part Name',
        namePlaceholder: 'Enter part name',

        sectionLabel: 'Section',
        sectionPlaceholder: 'Select section',

        newPartNameLabel: 'New Part Name',
        newPartNamePlaceholder: 'Enter new part name',

        imageLabel: 'Image',
        imagePlaceholder: 'Enter image path or filename',

        cuttingSizeLabel: 'Cutting Size',
        cuttingSizePlaceholder: 'Enter cutting size',

        partWeightLabel: 'Part Weight',
        partWeightPlaceholder: 'Enter part weight',

        rawMaterialLabel: 'Raw Material Weight',
        rawMaterialPlaceholder: 'Enter raw material weight',

        statusLabel: 'Status',
        statusPlaceholder: 'Select status',

        createTitle: 'Create Part',
        createDescription: 'Add a new part to the system.',
        createSubmitLabel: 'Save Part',

        editTitle: 'Edit Part',
        editDescription: 'Update the part information.',
        editSubmitLabel: 'Update Part',

        cancelLabel: 'Cancel',
    },

    statusOptions,
} as const;
