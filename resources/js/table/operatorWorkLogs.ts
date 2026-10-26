import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/operator';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
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
                    sortKey: 'user.name',
                    header: 'Operator',
                    size: 180,
                    minSize: 180,
                    maxSize: 180,
                    align: 'center',
                    sortable: true,
                }),
            ]
            : []),

        createColumn({
            accessorKey: 'shift_label',
            sortKey: 'shift',
            header: 'Shift',
            size: 100,
            minSize: 100,
            maxSize: 100,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'machine.name',
            sortKey: 'machine.name',
            header: 'Machine',
            size: 160,
            minSize: 160,
            maxSize: 160,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'part.name',
            sortKey: 'part.name',
            header: 'Part',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'setup.name',
            sortKey: 'setup.name',
            header: 'Setup',
            size: 100,
            minSize: 100,
            maxSize: 100,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
            minSize: 120,
            maxSize: 120,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'rejection_quantity',
            header: 'Rejected',
            size: 120,
            minSize: 120,
            maxSize: 120,
            align: 'center',
            sortable: true,
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_seconds',
            header: 'Production Time',
            size: 180,
            minSize: 180,
            maxSize: 180,
            align: 'center',
            sortable: true,
            cell: ({ row }) => row.original.production_seconds_formatted ?? '—',
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_efficiency',
            header: 'Daily Efficiency',
            size: 140,
            minSize: 140,
            maxSize: 140,
            align: 'center',
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
