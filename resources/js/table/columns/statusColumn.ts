import { h } from 'vue';

import { Badge } from '@/components/ui/badge';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type StatusColumnOptions = {
    accessorKey?: string;
    header?: string;
    size?: number;
    getVariant?: (
        status: string,
    ) => 'default' | 'secondary' | 'destructive' | 'outline';
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createStatusColumn = ({
    accessorKey = 'status',
    header = 'Status',
    size = 120,
    getVariant,
}: StatusColumnOptions = {}) => ({
    accessorKey,

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header,

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell: ({ row }: any) => {
        const status =
            (row.getValue(accessorKey) as string | null | undefined) ?? '';

        const variant = getVariant
            ? getVariant(status)
            : status === 'active'
              ? 'default'
              : 'secondary';

        return h(
            Badge,
            {
                variant,
                class: 'capitalize',
            },
            () => status || '—',
        );
    },

    /*
    |--------------------------------------------------------------------------
    | Width
    |--------------------------------------------------------------------------
    */

    size,
    minSize: size,
    maxSize: size,

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    enableSorting: false,
});
