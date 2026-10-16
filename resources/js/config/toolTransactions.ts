import type { ToolTransactionType } from '@/types/toolTransaction';

export const toolTransactionPage = {
    title: 'Tool Transactions',

    description:
        'Manage tool stock, issue tools to users, record adjustments, and track transaction history.',

    createLabel: 'Add Transaction',

    searchPlaceholder: 'Search tool transactions...',
    searchButtonLabel: 'Search',
    clearButtonLabel: 'Clear',

    toolPlaceholder: 'All Tools',
    typePlaceholder: 'All Types',
    fromUserPlaceholder: 'All From Users',
    toUserPlaceholder: 'All To Users',
    partPlaceholder: 'All Parts',
    setupPlaceholder: 'All Setups',

    emptyText: 'No tool transactions found.',

    itemLabel: 'transactions',
    resourceLabel: 'tool transaction',

    typeOptions: [
        {
            value: 'opening',
            label: 'Opening',
        },
        {
            value: 'purchase',
            label: 'Purchase',
        },
        {
            value: 'issue',
            label: 'Issue',
        },
        {
            value: 'adjustment',
            label: 'Adjustment',
        },
    ] satisfies ReadonlyArray<{
        value: ToolTransactionType;
        label: string;
    }>,

    table: {
        toolLabel: 'Tool',
        typeLabel: 'Type',
        quantityLabel: 'Quantity',
        fromUserLabel: 'From User',
        toUserLabel: 'To User',
        partLabel: 'Part',
        setupLabel: 'Setup',
        returnScrapLabel: 'Return / Scrap',
        createdByLabel: 'Created By',
        remarksLabel: 'Remarks',
        dateLabel: 'Date',
    },

    form: {
        sectionTitle: 'Transaction Information',

        sectionDescription:
            'Enter the details for the tool transaction.',

        toolLabel: 'Tool',
        toolPlaceholder: 'Select tool',

        typeLabel: 'Transaction Type',
        typePlaceholder: 'Select transaction type',

        quantityLabel: 'Quantity',
        quantityPlaceholder: 'Enter quantity',

        fromUserLabel: 'From User',
        fromUserPlaceholder: 'Select user',

        toUserLabel: 'To User',
        toUserPlaceholder: 'Select user',

        partLabel: 'Part',
        partPlaceholder: 'Select part',

        setupLabel: 'Setup',
        setupPlaceholder: 'Select setup',

        returnScrapLabel: 'Returned Tool Is Scrap',

        returnScrapTitle: 'Returned Tool Condition',

        returnScrapDescription:
            'Indicate whether the returned tool is being marked as scrap.',

        returnScrapActiveText:
            'The returned tool is marked as scrap.',

        returnScrapInactiveText:
            'The returned tool is not marked as scrap.',

        remarksLabel: 'Remarks',
        remarksPlaceholder: 'Enter remarks',

        createTitle: 'Create Tool Transaction',
        createDescription: 'Add a new tool transaction.',
        createSubmitLabel: 'Save Transaction',

        editTitle: 'Edit Tool Transaction',
        editDescription: 'Update the tool transaction.',
        editSubmitLabel: 'Update Transaction',

        cancelLabel: 'Cancel',
    },
} as const;
