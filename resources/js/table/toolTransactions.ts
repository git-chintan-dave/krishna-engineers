import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/tool-transactions';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createTransactionQuantityColumn, createTransactionTypeColumn,} from '@/table/columns/transactionColumns';
import {createColumn} from '@/table/helpers/createColumn';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import type {ToolTransaction} from '@/types/toolTransaction';

const columnHelper = createColumnHelper<any, ToolTransaction>();

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
            ...COLUMN_DEFAULTS.primaryColumn,
            sortable: true,
        }),

        createTransactionTypeColumn<ToolTransaction>({
            accessorKey: 'type',
            size: 140,
            getType: (row) => row.type,
        }),

        createTransactionQuantityColumn<ToolTransaction>({
            getQuantity: (row) => row.quantity,
            size: 120,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'from_user.name',
            sortKey: 'fromUser.name',
            header: 'From User',
            size: 180,
            minSize: 180,
            maxSize: 180,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'to_user.name',
            sortKey: 'toUser.name',
            header: 'To User',
            size: 180,
            minSize: 180,
            maxSize: 180,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'part.name',
            sortKey: 'part.name',
            header: 'Part',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn<ToolTransaction, string>({
            accessorKey: 'setup.name',
            sortKey: 'setup.name',
            header: 'Setup',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn<ToolTransaction, boolean | null>({
            accessorKey: 'return_scrap',
            header: 'Return / Scrap',
            size: 120,
            minSize: 120,
            maxSize: 120,
            sortable: true,
            cell: ({ row }) => {
                const value = row.original.return_scrap;

                if (value === null || value === undefined) {
                    return '—';
                }

                return value ? 'Yes' : 'No';
            },
        }),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'tool transaction',
            handleDelete,
        }),
    ]);
