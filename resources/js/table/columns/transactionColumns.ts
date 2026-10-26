import { h } from 'vue';

import { Badge } from '@/components/ui/badge';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';
import { createColumn } from '@/table/helpers/createColumn';
import type { ColumnAlign } from '@/table/types';
import type { ToolTransactionType } from '@/types/toolTransaction';

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
    getType: (row: TData) => TransactionTypeOption<TType> | null | undefined;
    getVariant?: (
        type: TType,
    ) => 'default' | 'secondary' | 'destructive' | 'outline';
};

type TransactionQuantityColumnOptions<TData> = {
    accessorKey?: string;
    header?: string;
    align?: ColumnAlign;
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
            return 'default';

        case 'purchase':
            return 'secondary';

        case 'issue':
            return 'destructive';

        case 'adjustment':
            return 'outline';

        default:
            return 'secondary';
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
        cell: ({ row }) => {
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
                            class: 'text-sm! font-normal! capitalize',
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
        cell: ({ row }) => {
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
