import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/part-setups';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createImageColumn} from '@/table/columns/imageColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {PartSetup} from '@/types/partSetup';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';

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
            accessorKey: 'part.name',
            sortKey: 'part.name',
            header: 'Part',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'setup.name',
            sortKey: 'setup.name',
            header: 'Setup',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'machine_cycle_time_seconds',
            header: 'Machine Cycle Time',
            size: 180,
            minSize: 180,
            maxSize: 180,
            sortable: true,
            cell: ({ row }: { row: { original: PartSetup } }) =>
                row.original.machine_cycle_time_formatted ?? '—',
        }),

        createColumn({
            accessorKey: 'actual_cycle_time_seconds',
            header: 'Actual Cycle Time',
            size: 180,
            minSize: 180,
            maxSize: 180,
            sortable: true,
            cell: ({ row }: { row: { original: PartSetup } }) =>
                row.original.actual_cycle_time_formatted ?? '—',
        }),

        createImageColumn<PartSetup>({
            getImage: (partSetup) => partSetup.image,
            getAlt: (partSetup) =>
                `${partSetup.part?.name ?? 'Part'}-${partSetup.setup?.name ?? 'Setup'}`,
        }),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'part setup',
            handleDelete,
        }),
    ]);
