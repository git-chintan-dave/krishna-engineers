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

        ...(canManage
            ? [
                createColumn({
                    accessorKey: 'user.name',
                    sortKey: 'user.name',
                    header: 'Operator',
                    ...COLUMN_DEFAULTS.smallColumn,
                    align: 'center',
                    sortable: true,
                    primary: true,
                }),
            ]
            : []),

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        createColumn({
            accessorKey: 'shift_label',
            sortKey: 'shift',
            header: 'Shift',
            ...COLUMN_DEFAULTS.smallColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'machine.name',
            sortKey: 'machine.name',
            header: 'Machine',
            ...COLUMN_DEFAULTS.smallColumn,
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
            ...COLUMN_DEFAULTS.smallColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            ...COLUMN_DEFAULTS.smallColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn({
            accessorKey: 'rejection_quantity',
            header: 'Rejected',
            ...COLUMN_DEFAULTS.smallColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_seconds',
            header: 'Production Time',
            ...COLUMN_DEFAULTS.wideColumn,
            align: 'center',
            sortable: true,
            cell: ({ row }) => row.original.production_seconds_formatted ?? '—',
        }),

        createColumn<OperatorWorkLog, number | null>({
            accessorKey: 'production_efficiency',
            header: 'Daily Efficiency',
            ...COLUMN_DEFAULTS.mediumColumn,
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
