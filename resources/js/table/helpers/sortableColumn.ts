import {h} from 'vue';

import {createSortableHeader} from '@/table/helpers/sortableHeader';
import type {ColumnAlign} from '@/table/types';

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
    align?: ColumnAlign;
    cell?: (context: any) => unknown;
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getAlignmentClass = (align: ColumnAlign): string => {
    switch (align) {
        case 'center':
            return 'text-center';
        case 'right':
            return 'text-right';
        default:
            return 'text-left';
    }
};

const formatCellValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
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
                                         align = 'left',
                                         cell,
                                     }: SortableColumnOptions) => ({
    accessorKey,

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */

    meta: {
        ...(sortKey !== undefined ? { sortKey } : {}),
        align,
    },

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: createSortableHeader({
        label: header,
        align,
    }),

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell:
        cell ??
        (({ getValue }: { getValue: () => unknown }) =>
            h(
                'span',
                {
                    class: [
                        'block min-w-0 whitespace-normal break-words',
                        getAlignmentClass(align),
                    ],
                },
                formatCellValue(getValue()),
            )),

    /*
    |--------------------------------------------------------------------------
    | Width
    |--------------------------------------------------------------------------
    */

    ...(size !== undefined
        ? {
            size,
            minSize: size,
            maxSize: size,
        }
        : {}),
});
