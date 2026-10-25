import {h} from 'vue';

import {createColumn} from '@/table/helpers/createColumn';
import type {ToolTransactionType,} from '@/types/toolTransaction';

type TransactionTypeOption<TType extends string> = {
    value?: TType | null;
    label?: string | null;
};

const getTypeClass = (type: ToolTransactionType) => {
    switch (type) {
        case 'opening':
            return [
                'bg-blue-50 text-blue-700',
                'dark:bg-blue-950/40 dark:text-blue-400',
            ];

        case 'purchase':
            return [
                'bg-green-50 text-green-700',
                'dark:bg-green-950/40 dark:text-green-400',
            ];

        case 'issue':
            return [
                'bg-orange-50 text-orange-700',
                'dark:bg-orange-950/40 dark:text-orange-400',
            ];

        case 'adjustment':
            return [
                'bg-slate-50 text-slate-700',
                'dark:bg-slate-950/40 dark:text-slate-400',
            ];
    }
};

export const createTransactionTypeColumn = <
    TData,
    TType extends string = ToolTransactionType,
>(
    options: {
        accessorKey: string;
        header?: string;
        size?: number;
        getType: (row: TData) => TransactionTypeOption<TType> | null | undefined;
    },
) =>
    createColumn<TData, string>({
        accessorKey: options.accessorKey,
        header: options.header ?? 'Type',
        ...(options.size !== undefined
            ? {
                size: options.size,
                minSize: options.size,
                maxSize: options.size,
            }
            : {}),
        sortable: true,
        cell: ({ row }) => {
            const typeOption = options.getType(row.original);
            const type = typeOption?.value;

            if (!type) {
                return '—';
            }

            return h(
                'span',
                {
                    class: [
                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                        getTypeClass(type as ToolTransactionType),
                    ],
                },
                typeOption.label ?? type,
            );
        },
    });

export const createTransactionQuantityColumn = <TData>(
    options: {
        getQuantity: (row: TData) => number;
        size?: number;
    },
) =>
    createColumn<TData, number>({
        accessorKey: 'quantity',
        header: 'Quantity',
        ...(options.size !== undefined
            ? {
                size: options.size,
                minSize: options.size,
                maxSize: options.size,
            }
            : {}),
        sortable: true,
        cell: ({ row }) => {
            const quantity = options.getQuantity(row.original);

            return h(
                'span',
                {
                    class:
                        quantity < 0
                            ? 'font-medium text-destructive'
                            : undefined,
                },
                String(quantity),
            );
        },
    });
