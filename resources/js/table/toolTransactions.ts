import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';

import { edit } from '@/routes/tool-transactions';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createColumn } from '@/table/helpers/createColumn';
import type {
    ToolTransaction,
    ToolTransactionType,
} from '@/types/toolTransaction';

const columnHelper = createColumnHelper<any, ToolTransaction>();

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

export const createToolTransactionColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'tool transactions',
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'tool.name',
            sortKey: 'tool.name',
            header: 'Tool',
            size: 240,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'type',
            header: 'Type',
            size: 140,
            sortable: true,
            cell: ({ row }) => {
                const type = row.original.type?.value as
                    | ToolTransactionType
                    | undefined;

                if (!type) {
                    return '—';
                }

                const label = row.original.type?.label ?? type;

                return h(
                    'span',
                    {
                        class: [
                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                            getTypeClass(type),
                        ],
                    },
                    label,
                );
            },
        }),

        createColumn<ToolTransaction, number>({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
            sortable: true,
            cell: ({ row }) => {
                const quantity = row.original.quantity;

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
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'from_user.name',
            sortKey: 'fromUser.name',
            header: 'From User',
            size: 180,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'to_user.name',
            sortKey: 'toUser.name',
            header: 'To User',
            size: 180,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'part.name',
            sortKey: 'part.name',
            header: 'Part',
            size: 120,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'setup.name',
            sortKey: 'setup.name',
            header: 'Setup',
            size: 120,
            sortable: true,
        }),

        createColumn<ToolTransaction, boolean | null>({
            accessorKey: 'return_scrap',
            header: 'Return / Scrap',
            size: 120,
            sortable: true,
            cell: ({ row }) => {
                const value = row.original.return_scrap;

                if (value === null || value === undefined) {
                    return '—';
                }

                return value ? 'Yes' : 'No';
            },
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'tool transaction',
            handleDelete,
        }),
    ]);
