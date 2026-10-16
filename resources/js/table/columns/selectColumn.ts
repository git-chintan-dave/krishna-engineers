import {h} from 'vue';

import {Checkbox} from '@/components/ui/checkbox';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SelectColumnOptions<TData = any> = {
    resourceName: string;
    isSelectable?: (row: TData) => boolean;
};

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createSelectColumn = <TData = any>({
                                                    resourceName,
                                                    isSelectable,
                                                }: SelectColumnOptions<TData>) => ({
    id: 'select',

    /*
    |--------------------------------------------------------------------------
    | Width
    |--------------------------------------------------------------------------
    */

    size: 48,
    minSize: 48,
    maxSize: 48,

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    header: ({table}: any) =>
        h(Checkbox, {
            modelValue:
                table.getIsAllPageRowsSelected() ||
                (table.getIsSomePageRowsSelected() && 'indeterminate'),

            'onUpdate:modelValue': (value: boolean | 'indeterminate') => {
                table.toggleAllPageRowsSelected(!!value);
            },

            ariaLabel: `Select all ${resourceName}`,

            class: 'cursor-pointer',
        }),

    /*
    |--------------------------------------------------------------------------
    | Cell
    |--------------------------------------------------------------------------
    */

    cell: ({row}: any) => {
        const selectable = isSelectable?.(row.original) ?? true;

        if (!selectable) {
            return null;
        }

        return h(Checkbox, {
            modelValue: row.getIsSelected(),

            'onUpdate:modelValue': (value: boolean | 'indeterminate') => {
                row.toggleSelected(!!value);
            },

            ariaLabel: `Select ${row.original.name}`,

            class: 'cursor-pointer',
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Row Selection
    |--------------------------------------------------------------------------
    */

    enableRowSelection: (row: any) => isSelectable?.(row.original) ?? true,

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    enableSorting: false,
});
