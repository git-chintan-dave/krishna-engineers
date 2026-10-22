import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/operator';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {OperatorWorkLog} from '@/types/operatorWorkLog';

const columnHelper = createColumnHelper<any, OperatorWorkLog>();

export const createOperatorWorkLogColumns = (
    handleDelete: (uuid: string) => void,
    canManage: boolean,
) =>
    columnHelper.columns([
        ...(canManage
            ? [
                createSelectColumn({
                    resourceName: 'operator-work-logs',
                }),
            ]
            : []),

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        ...(canManage
            ? [
                createColumn({
                    accessorKey: 'user.name',
                    header: 'Operator',
                    size: 180,
                    align: 'center',
                }),
            ]
            : []),

        createColumn({
            accessorKey: 'shift',
            header: 'Shift',
            size: 100,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'machine.name',
            header: 'Machine',
            size: 160,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'part.name',
            header: 'Part',
            size: 160,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'setup.name',
            header: 'Setup',
            size: 100,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'rejection_quantity',
            header: 'Rejected',
            size: 120,
            align: 'center',
            sortable: true,
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_seconds',
            header: 'Production Time',
            size: 180,
            align: 'center',
            sortable: true,
            cell: ({ row }) =>
                row.original.production_seconds_formatted ?? '—',
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_efficiency',
            header: 'Daily Efficiency',
            align: 'center',
            size: 140,
            cell: ({ getValue }) => {
                const efficiency = getValue();

                return efficiency == null
                    ? '—'
                    : `${efficiency.toFixed(2)}%`;
            },
        }),

        ...(canManage
            ? [
                createActionsColumn({
                    editUrl: (uuid) => edit(uuid).url,
                    resourceName: 'operator work log',
                    handleDelete,
                }),
            ]
            : []),
    ]);
