import {h} from 'vue';

import {createSortableHeader} from '@/table/helpers/sortableHeader';
import type {ColumnAlign} from '@/table/types';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type BasePrimaryColumnOptions = {
    header: string;
    size?: number;
    sortable?: boolean;
    align?: ColumnAlign;
};

type AccessorKeyPrimaryColumnOptions = BasePrimaryColumnOptions & {
    accessorKey: string;
    accessorFn?: never;
    id?: never;
};

type AccessorFnPrimaryColumnOptions<TData> = BasePrimaryColumnOptions & {
    accessorFn: (row: TData) => unknown;
    accessorKey?: never;
    id: string;
};

type PrimaryColumnOptions<TData = unknown> =
    | AccessorKeyPrimaryColumnOptions
    | AccessorFnPrimaryColumnOptions<TData>;

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

export const createPrimaryColumn = <TData = unknown>({
                                                         header,
                                                         size,
                                                         sortable = false,
                                                         align = 'left',
                                                         ...accessor
                                                     }: PrimaryColumnOptions<TData>) => ({
    ...accessor,

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */

    meta: {
        align,
    },

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: sortable
        ? createSortableHeader({
            label: header,
            align,
        })
        : header,

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell: ({getValue}: { getValue: () => unknown }) =>
        h(
            'span',
            {
                class: [
                    'block min-w-0 whitespace-normal break-words font-semibold',
                    getAlignmentClass(align),
                ],
            },
            formatCellValue(getValue()),
        ),

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
