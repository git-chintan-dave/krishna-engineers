import { createColumnHelper } from '@tanstack/vue-table';
import { h } from 'vue';

import { edit } from '@/routes/tool-transactions';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';
import type {
    ToolTransaction,
    ToolTransactionType,
} from '@/types/toolTransaction';

const columnHelper = createColumnHelper<any, ToolTransaction>();

/*
|--------------------------------------------------------------------------
| Type Badge
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Columns
|--------------------------------------------------------------------------
*/

export const createToolTransactionColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'tool transactions',
        }),

        createSortableColumn({
            accessorKey: 'tool.name',
            header: 'Tool',
            size: 240,

            cell: ({ row }) => {
                const value = row.original.tool?.name ?? '—';

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value !== '—' ? value : undefined,
                    },
                    value,
                );
            },
        }),

        createSortableColumn({
            accessorKey: 'type',
            header: 'Type',
            size: 140,

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

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,

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

        createSortableColumn({
            accessorKey: 'from_user.name',
            header: 'From User',
            size: 180,

            cell: ({ row }) => {
                const user = row.original.from_user;

                if (!user?.name) {
                    return '—';
                }

                const value = user.employee_code
                    ? `${user.name} (${user.employee_code})`
                    : user.name;

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value,
                    },
                    value,
                );
            },
        }),

        createSortableColumn({
            accessorKey: 'to_user.name',
            header: 'To User',
            size: 180,

            cell: ({ row }) => {
                const user = row.original.to_user;

                if (!user?.name) {
                    return '—';
                }

                const value = user.employee_code
                    ? `${user.name} (${user.employee_code})`
                    : user.name;

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value,
                    },
                    value,
                );
            },
        }),

        createSortableColumn({
            accessorKey: 'part.reference_name',
            header: 'Part',
            size: 200,

            cell: ({ row }) => {
                const part = row.original.part;

                if (!part?.name) {
                    return '—';
                }

                const value = part.new_part_name
                    ? `${part.name} (${part.new_part_name})`
                    : part.name;

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value,
                    },
                    value,
                );
            },
        }),

        createSortableColumn({
            accessorKey: 'setup.name',
            header: 'Setup',
            size: 130,

            cell: ({ row }) => row.original.setup?.name ?? '—',
        }),

        createSortableColumn({
            accessorKey: 'return_scrap',
            header: 'Return / Scrap',
            size: 130,

            cell: ({ row }) => {
                const value = row.original.return_scrap;

                if (value === null || value === undefined) {
                    return '—';
                }

                return value ? 'Scrap' : 'Not Scrap';
            },
        }),

        createSortableColumn({
            accessorKey: 'created_by.name',
            header: 'Created By',
            size: 180,

            cell: ({ row }) => {
                const user = row.original.created_by;

                if (!user?.name) {
                    return '—';
                }

                const value = user.employee_code
                    ? `${user.name} (${user.employee_code})`
                    : user.name;

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value,
                    },
                    value,
                );
            },
        }),

        createSortableColumn({
            accessorKey: 'remarks',
            header: 'Remarks',
            size: 240,

            cell: ({ row }) => {
                const value = row.original.remarks ?? '—';

                return h(
                    'div',
                    {
                        class: 'truncate',
                        title: value !== '—' ? value : undefined,
                    },
                    value,
                );
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
