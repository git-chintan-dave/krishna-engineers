import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/part-setups';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {PartSetup} from '@/types/partSetup';
import {createDateColumn} from '@/table/columns/dateColumn';
import {h} from 'vue';
import AppImageThumbnail from '@/components/common/AppImageThumbnail.vue';

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

        createPrimaryColumn({
            id: 'part',
            accessorFn: (row: PartSetup) => row.part?.name ?? '—',
            header: 'Part',
            size: 260,
            sortable: true,
        }),

        createPrimaryColumn({
            id: 'setup',
            accessorFn: (row: PartSetup) => row.setup?.name ?? '—',
            header: 'Setup',
            size: 180,
            sortable: true,
        }),

        createSortableColumn({
            accessorKey: 'machine_cycle_time_seconds',
            header: 'Machine Cycle Time',
            size: 180,
            cell: ({row}: any) =>
                row.original.machine_cycle_time_formatted ?? '—',
        }),

        createSortableColumn({
            accessorKey: 'actual_cycle_time_seconds',
            header: 'Actual Cycle Time',
            size: 180,
            cell: ({row}: any) =>
                row.original.actual_cycle_time_formatted ?? '—',
        }),

        {
            id: 'image',
            header: 'Image',
            size: 80,
            enableSorting: false,
            cell: ({row}) =>
                h(AppImageThumbnail, {
                    src: row.original.image,
                    alt: `${row.original.part.name}-${row.original.setup.name}`,
                    size: 'sm',
                }),
        },

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
