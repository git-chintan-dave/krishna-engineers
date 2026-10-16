import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/part-setups';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createImageColumn} from '@/table/columns/imageColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {PartSetup} from '@/types/partSetup';

const columnHelper = createColumnHelper<any, PartSetup>();

/*
|--------------------------------------------------------------------------
| Columns
|--------------------------------------------------------------------------
*/

export const createPartSetupColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'part setups',
        }),

        createColumn({
            id: 'part',
            accessorFn: (row: PartSetup) => row.part?.name ?? '—',
            header: 'Part',
            size: 260,
            sortable: true,
            primary: true,
        }),

        createColumn({
            id: 'setup',
            accessorFn: (row: PartSetup) => row.setup?.name ?? '—',
            header: 'Setup',
            size: 180,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'machine_cycle_time_seconds',
            header: 'Machine Cycle Time',
            size: 180,
            sortable: true,
            cell: ({ row }: { row: { original: PartSetup } }) =>
                row.original.machine_cycle_time_formatted ?? '—',
        }),

        createColumn({
            accessorKey: 'actual_cycle_time_seconds',
            header: 'Actual Cycle Time',
            size: 180,
            sortable: true,
            cell: ({ row }: { row: { original: PartSetup } }) =>
                row.original.actual_cycle_time_formatted ?? '—',
        }),

        createImageColumn<PartSetup>({
            getImage: (partSetup) => partSetup.image,
            getAlt: (partSetup) =>
                `${partSetup.part?.name ?? 'Part'}-${partSetup.setup?.name ?? 'Setup'}`,
            size: 80,
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'part setup',
            handleDelete,
        }),
    ]);
