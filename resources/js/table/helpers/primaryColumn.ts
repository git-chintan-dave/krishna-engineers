import { h } from 'vue';

import { createSortableHeader } from '@/table/helpers/sortableHeader';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type BasePrimaryColumnOptions = {
    header: string;
    size?: number;
    sortable?: boolean;
};

type AccessorKeyPrimaryColumnOptions =
    BasePrimaryColumnOptions & {
    accessorKey: string;
    accessorFn?: never;
    id?: never;
};

type AccessorFnPrimaryColumnOptions<TData> =
    BasePrimaryColumnOptions & {
    accessorFn: (row: TData) => unknown;
    accessorKey?: never;
    id: string;
};

type PrimaryColumnOptions<TData = any> =
    | AccessorKeyPrimaryColumnOptions
    | AccessorFnPrimaryColumnOptions<TData>;

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createPrimaryColumn = <TData = any>({
                                                     header,
                                                     size,
                                                     sortable = false,
                                                     ...accessor
                                                 }: PrimaryColumnOptions<TData>) => ({
    ...accessor,

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: sortable
        ? createSortableHeader({
            label: header,
        })
        : header,

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell: ({ getValue }: any) =>
        h(
            'span',
            {
                class: 'block min-w-0 whitespace-normal break-words font-semibold',
            },
            getValue(),
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
