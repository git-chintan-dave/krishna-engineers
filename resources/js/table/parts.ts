import {createColumnHelper} from '@tanstack/vue-table';
import {h} from 'vue';

import AppImageThumbnail from '@/components/common/AppImageThumbnail.vue';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import {edit} from '@/routes/parts';
import type {Part} from '@/types/part';

const columnHelper = createColumnHelper<any, Part>();

export const createPartColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'parts',
        }),

        createPrimaryColumn({
            accessorKey: 'name',
            header: 'Part Name',
            size: 260,
            sortable: true,
        }),

        createSortableColumn({
            accessorKey: 'new_part_name',
            header: 'New Part Name',
            size: 220,
        }),

        createSortableColumn({
            accessorKey: 'section.name',
            header: 'Section',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'cutting_size',
            header: 'Cutting Size',
            size: 180,
        }),

        createSortableColumn({
            accessorKey: 'part_weight',
            header: 'Part Weight',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'raw_material_weight',
            header: 'Raw Material Weight',
            size: 190,
        }),

        {
            id: 'image',
            header: 'Image',
            size: 100,
            minSize: 100,
            enableSorting: false,
            enableColumnFilter: false,
            cell: ({ row }) =>
                h(AppImageThumbnail, {
                    src: row.original.image,
                    alt: row.original.name,
                    size: 'sm',
                }),
        },

        createStatusColumn({
            header: 'Status',
            size: 120,
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
            size: 150,
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'part',
            handleDelete,
        }),
    ]);
