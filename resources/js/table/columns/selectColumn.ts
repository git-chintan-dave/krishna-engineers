import {h} from 'vue';

import {Checkbox} from '@/components/ui/checkbox';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import type {ColumnPin} from '@/table/helpers/createColumn';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SelectColumnOptions<TData = unknown> = {
    resourceName: string;
    canSelectRow?: (row: TData) => boolean;
    pin?: ColumnPin;
};

/*
|--------------------------------------------------------------------------
| Checkbox Styles
|--------------------------------------------------------------------------
*/

const CHECKBOX_CLASS = [
    'size-4 shrink-0',
    'cursor-pointer',
    'border-muted-foreground/50',
    'rounded-[4px]',
    'shadow-none',
    'transition-colors',

    'data-[state=checked]:border-primary',
    'data-[state=checked]:bg-primary',
    'data-[state=checked]:text-primary-foreground',

    'data-[state=indeterminate]:border-primary',
    'data-[state=indeterminate]:bg-primary',
    'data-[state=indeterminate]:text-primary-foreground',

    'focus-visible:ring-2',
    'focus-visible:ring-ring/50',
    'focus-visible:ring-offset-1',

    'disabled:cursor-not-allowed',
    'disabled:opacity-50',
].join(' ');

/*
|--------------------------------------------------------------------------
| Column
|--------------------------------------------------------------------------
*/

export const createSelectColumn = <TData = unknown>({
                                                        resourceName,
                                                        canSelectRow,
                                                        pin,
                                                    }: SelectColumnOptions<TData>) => ({
    id: 'select',

    size: COLUMN_DEFAULTS.select.size,
    minSize: COLUMN_DEFAULTS.select.minSize,
    maxSize: COLUMN_DEFAULTS.select.maxSize,

    meta: {
        align: 'center' as const,
        ...(pin !== undefined ? {pin} : {}),
    },

    header: ({table}: { table: any }) =>
        h(Checkbox, {
            modelValue:
                table.getIsAllPageRowsSelected() ||
                (table.getIsSomePageRowsSelected() && 'indeterminate'),

            'onUpdate:modelValue': (value: boolean | 'indeterminate') => {
                table.toggleAllPageRowsSelected(value === true);
            },

            ariaLabel: `Select all ${resourceName}`,
            class: CHECKBOX_CLASS,
        }),

    cell: ({row}: { row: any }) => {
        const selectable = canSelectRow?.(row.original) ?? true;

        const label =
            typeof row.original?.name === 'string' &&
            row.original.name.trim()
                ? row.original.name
                : resourceName;

        return h(Checkbox, {
            modelValue: row.getIsSelected(),
            disabled: !selectable,

            'onUpdate:modelValue': (value: boolean | 'indeterminate') => {
                if (!selectable) {
                    return;
                }

                row.toggleSelected(value === true);
            },

            ariaLabel: `Select ${label}`,
            class: CHECKBOX_CLASS,
        });
    },

    enableRowSelection: (row: any) =>
        canSelectRow?.(row.original) ?? true,

    enableSorting: false,
});
