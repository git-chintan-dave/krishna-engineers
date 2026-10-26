export const COLUMN_DEFAULTS = {
    select: {
        size: 32,
        minSize: 32,
        maxSize: 32,
    },

    primaryColumn: {
        size: 200,
        minSize: 200,
        maxSize: 200,
    },

    flexiblePrimaryColumn: {
        minSize: 200,
    },

    smallColumn: {
        size: 120,
        minSize: 120,
        maxSize: 120,
    },

    secondaryColumn: {
        size: 140,
        minSize: 140,
        maxSize: 140,
    },

    mediumColumn: {
        size: 160,
        minSize: 160,
        maxSize: 160,
    },

    wideColumn: {
        size: 180,
        minSize: 180,
        maxSize: 180,
    },

    extraWideColumn: {
        size: 200,
        minSize: 200,
        maxSize: 200,
    },

    ultraWideColumn: {
        size: 220,
        minSize: 220,
        maxSize: 220,
    },

    extraExtraWideColumn: {
        size: 240,
        minSize: 240,
        maxSize: 240,
    },

    image: {
        header: 'Image',
        size: 80,
        minSize: 80,
        maxSize: 80,
    },

    status: {
        header: 'Status',
        size: 100,
        minSize: 100,
        maxSize: 100,
    },

    quantity: {
        header: 'Quantity',
        size: 120,
        minSize: 120,
        maxSize: 120,
    },

    date: {
        header: 'Created',
        size: 140,
        minSize: 140,
        maxSize: 140,
    },

    actions: {
        header: 'Actions',
        size: 140,
        minSize: 140,
        maxSize: 140,
    },

    actionButton: {
        size: 32,
    },

    actionGap: 4,
} as const;
