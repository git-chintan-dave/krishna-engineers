import {h} from 'vue';

import {Badge} from '@/components/ui/badge';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import {ColumnPin, createColumn} from '@/table/helpers/createColumn';
import type {ColumnAlign} from '@/table/types';
import type {ToolTransactionType} from '@/types/toolTransaction';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type TransactionTypeOption<TType extends string> = {
    value?: TType | null;
    label?: string | null;
};

type TransactionTypeColumnOptions<TData, TType extends string> = {
    accessorKey?: string;
    header?: string;
    align?: ColumnAlign;
    pin?: ColumnPin;
    getType: (row: TData) => TransactionTypeOption<TType> | null | undefined;
    getVariant?: (
        type: TType,
    ) => 'default' | 'secondary' | 'destructive' | 'outline';
};

type TransactionQuantityColumnOptions<TData> = {
    accessorKey?: string;
    header?: string;
    align?: ColumnAlign;
    pin?: ColumnPin;
    getQuantity: (row: TData) => number | null | undefined;
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getTypeVariant = (
    type: ToolTransactionType,
): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (type) {
        case 'opening':
            return 'secondary';

        case 'purchase':
            return 'default';

        case 'issue':
            return 'destructive';

        case 'adjustment':
            return 'outline';

        default:
            return 'secondary';
    }
};

const getTypeClass = (type: ToolTransactionType): string => {
    switch (type) {
        case 'opening':
            return [
                'border-slate-200/80 bg-slate-500/[0.06] text-slate-700',
                'dark:border-slate-700/70 dark:bg-slate-400/[0.08] dark:text-slate-300',
            ].join(' ');

        case 'purchase':
            return [
                'border-blue-200/80 bg-blue-500/[0.06] text-blue-700',
                'dark:border-blue-800/70 dark:bg-blue-400/[0.08] dark:text-blue-300',
            ].join(' ');

        case 'issue':
            return [
                'border-red-200/80 bg-red-500/[0.06] text-red-700',
                'dark:border-red-800/70 dark:bg-red-400/[0.08] dark:text-red-300',
            ].join(' ');

        case 'adjustment':
            return [
                'border-amber-200/80 bg-amber-500/[0.06] text-amber-700',
                'dark:border-amber-800/70 dark:bg-amber-400/[0.08] dark:text-amber-300',
            ].join(' ');

        default:
            return [
                'border-border/70 bg-muted/50 text-foreground',
                'dark:bg-muted/40',
            ].join(' ');
    }
};

/*
|--------------------------------------------------------------------------
| Transaction Type Column
|--------------------------------------------------------------------------
*/

export const createTransactionTypeColumn = <
    TData,
    TType extends string = ToolTransactionType,
>(
    options: TransactionTypeColumnOptions<TData, TType>,
) => {
    const {
        accessorKey = 'type',
        header = 'Type',
        align = 'center',
        pin,
        getType,
        getVariant,
    } = options;

    return createColumn<TData, string>({
        accessorKey,
        header,
        size: COLUMN_DEFAULTS.status.size,
        minSize: COLUMN_DEFAULTS.status.minSize,
        maxSize: COLUMN_DEFAULTS.status.maxSize,
        align,
        sortable: true,
        ...(pin ? {pin} : {}),
        cell: ({row}) => {
            const typeOption = getType(row.original);
            const type = typeOption?.value;

            if (!type) {
                return '—';
            }

            const variant = getVariant
                ? getVariant(type)
                : getTypeVariant(type as ToolTransactionType);

            return h(
                'div',
                {
                    class: [
                        'flex w-full',
                        align === 'left'
                            ? 'justify-start'
                            : align === 'right'
                                ? 'justify-end'
                                : 'justify-center',
                    ],
                },
                [
                    h(
                        Badge,
                        {
                            variant,
                            class: [
                                'border px-2 py-0.5 text-xs! font-medium! capitalize shadow-none',
                                'transition-colors',
                                getTypeClass(type as ToolTransactionType),
                            ],
                        },
                        () => typeOption.label ?? type,
                    ),
                ],
            );
        },
    });
};

/*
|--------------------------------------------------------------------------
| Transaction Quantity Column
|--------------------------------------------------------------------------
*/

export const createTransactionQuantityColumn = <TData>(
    options: TransactionQuantityColumnOptions<TData>,
) => {
    const {
        accessorKey = 'quantity',
        header = 'Quantity',
        align = 'center',
        pin,
        getQuantity,
    } = options;

    return createColumn<TData, number>({
        accessorKey,
        header,
        size: COLUMN_DEFAULTS.quantity.size,
        minSize: COLUMN_DEFAULTS.quantity.minSize,
        maxSize: COLUMN_DEFAULTS.quantity.maxSize,
        align,
        sortable: true,
        ...(pin ? {pin} : {}),
        cell: ({row}) => {
            const quantity = getQuantity(row.original);

            if (quantity === null || quantity === undefined) {
                return '—';
            }

            return h(
                'span',
                {
                    class: [
                        'block min-w-0 whitespace-normal break-words text-sm',
                        align === 'left'
                            ? 'text-left'
                            : align === 'right'
                                ? 'text-right'
                                : 'text-center',
                        quantity < 0
                            ? 'font-medium text-destructive'
                            : 'font-normal',
                    ],
                },
                String(quantity),
            );
        },
    });
};
