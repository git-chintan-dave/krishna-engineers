import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/operator';

import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';

import { createPrimaryColumn } from '@/table/helpers/primaryColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';

import type { OperatorWorkLog } from '@/types/operatorWorkLog';

const columnHelper =
    createColumnHelper<any, OperatorWorkLog>();

export const createOperatorWorkLogColumns = (
    handleDelete: (uuid: string) => void,
    canManage: boolean,
) =>
    columnHelper.columns([
        ...(canManage
            ? [
                createSelectColumn({
                    resourceName:
                        'operator-work-logs',
                }),
            ]
            : []),

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        createPrimaryColumn({
            accessorKey: 'user.name',
            header: 'Operator',
            size: 180,
            sortable: false,
        }),

        createPrimaryColumn({
            accessorKey: 'machine.name',
            header: 'Machine',
            size: 160,
            sortable: false,
        }),

        createPrimaryColumn({
            accessorKey: 'part.name',
            header: 'Part',
            size: 160,
            sortable: false,
        }),

        createPrimaryColumn({
            accessorKey: 'setup.name',
            header: 'Setup',
            size: 100,
            sortable: false,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,
        }),

        createSortableColumn({
            accessorKey: 'rejection_quantity',
            header: 'Rejected',
            size: 100,
        }),

        createSortableColumn({
            accessorKey: 'production_seconds',
            header: 'Production Time',
            size: 140,
            cell: ({ row }) =>
                row.original.production_seconds_formatted ??
                '—',
        }),

        ...(canManage
            ? [
                createActionsColumn({
                    editUrl: (uuid) =>
                        edit(uuid).url,
                    resourceName:
                        'operator work log',
                    handleDelete,
                }),
            ]
            : []),
    ]);
