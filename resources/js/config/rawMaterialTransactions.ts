import { rawMaterialTransactionTypeOptions } from '@/config/rawMaterialTransactionTypes';

export const rawMaterialTransactionPage = {
    title: 'Raw Material Transactions',
    description:
        'Manage raw material stock transactions and inventory movements.',

    createLabel: 'Add Transaction',

    searchPlaceholder: 'Search transactions...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    sectionPlaceholder: 'All Sections',
    sectionSizePlaceholder: 'All Section Sizes',
    typePlaceholder: 'All Transaction Types',
    userPlaceholder: 'All Users',

    emptyText: 'No raw material transactions found.',
    itemLabel: 'transactions',
    resourceLabel: 'raw material transaction',

    form: {
        sectionTitle: 'Transaction Information',
        sectionDescription:
            'Enter the details of the raw material transaction.',

        sectionSizeLabel: 'Section Size',
        sectionSizePlaceholder: 'Select section size',

        typeLabel: 'Transaction Type',
        typePlaceholder: 'Select transaction type',

        quantityLabel: 'Quantity',
        quantityPlaceholder: 'Enter quantity',

        referenceTypeLabel: 'Reference Type',
        referenceTypePlaceholder: 'Enter reference type',

        referenceIdLabel: 'Reference ID',
        referenceIdPlaceholder: 'Enter reference ID',

        remarksLabel: 'Remarks',
        remarksPlaceholder: 'Enter remarks',

        createTitle: 'Create Raw Material Transaction',
        createDescription:
            'Add a new raw material stock transaction.',
        createSubmitLabel: 'Save Transaction',

        editTitle: 'Edit Raw Material Transaction',
        editDescription:
            'Update the raw material transaction.',
        editSubmitLabel: 'Update Transaction',

        cancelLabel: 'Cancel',
    },

    transactionTypeOptions:
    rawMaterialTransactionTypeOptions,
} as const;
