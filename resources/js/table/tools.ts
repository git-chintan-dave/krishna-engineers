import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/tools';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createPrimaryColumn } from '@/table/helpers/primaryColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';
import type { Tool } from '@/types/tool';
import { h } from 'vue';
import AppImageThumbnail from '@/components/common/AppImageThumbnail.vue';

const columnHelper = createColumnHelper<any, Tool>();

export const createToolColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'tools',
        }),

        createPrimaryColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 260,
            sortable: true,
        }),

        createSortableColumn({
            accessorKey: 'type_label',
            header: 'Type',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 160,
        }),

        {
            id: 'image',
            header: 'Image',
            size: 80,
            enableSorting: false,
            cell: ({ row }) =>
                h(AppImageThumbnail, {
                    src: row.original.image,
                    alt: row.original.name,
                    size: 'sm',
                }),
        },

        createStatusColumn({
            header: 'Status',
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'tool',
            handleDelete,
        }),
    ]);
