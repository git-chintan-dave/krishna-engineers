import {h} from 'vue';

import {Checkbox} from '@/components/ui/checkbox';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';

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
| Checkbox Styles
|--------------------------------------------------------------------------
*/

const CHECKBOX_CLASS = [
    'cursor-pointer',
    'border-muted-foreground/50',
    'data-[state=checked]:border-primary',
    'data-[state=checked]:bg-primary',
    'data-[state=indeterminate]:border-primary',
    'data-[state=indeterminate]:bg-primary',
].join(' ');

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

    size: COLUMN_DEFAULTS.select.size,
    minSize: COLUMN_DEFAULTS.select.minSize,
    maxSize: COLUMN_DEFAULTS.select.maxSize,

    header: ({ table }: any) =>
        h(Checkbox, {
            modelValue:
                table.getIsAllPageRowsSelected() ||
                (table.getIsSomePageRowsSelected() && 'indeterminate'),

            'onUpdate:modelValue': (value: boolean | 'indeterminate') => {
                table.toggleAllPageRowsSelected(!!value);
            },

            ariaLabel: `Select all ${resourceName}`,

            class: CHECKBOX_CLASS,
        }),

    cell: ({ row }: any) => {
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
            class: CHECKBOX_CLASS,
        });
    },

    enableRowSelection: (row: any) =>
        isSelectable?.(row.original) ?? true,

    enableSorting: false,
});
