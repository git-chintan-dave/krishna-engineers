import { h } from 'vue';

import { createSortableHeader } from '@/table/helpers/sortableHeader';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SortableColumnOptions = {
    accessorKey: string;
    sortKey?: string;
    header: string;
    size?: number;
    cell?: (context: any) => unknown;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createSortableColumn = ({
                                         accessorKey,
                                         sortKey,
                                         header,
                                         size,
                                         cell,
                                     }: SortableColumnOptions) => ({
    accessorKey,

    ...(sortKey !== undefined
        ? {
            meta: {
                sortKey,
            },
        }
        : {}),

    header: createSortableHeader({
        label: header,
    }),

    cell:
        cell ??
        (({ getValue }: any) =>
            h(
                'span',
                {
                    class: 'block min-w-0 whitespace-normal break-words',
                },
                getValue() ?? '—',
            )),

    ...(size !== undefined
        ? {
            size,
            minSize: size,
            maxSize: size,
        }
        : {}),
});
