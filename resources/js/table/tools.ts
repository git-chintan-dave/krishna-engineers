import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/tools';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createImageColumn} from '@/table/columns/imageColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {Tool} from '@/types/tool';

const columnHelper = createColumnHelper<any, Tool>();

export const createToolColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'tools',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 260,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'type_label',
            header: 'Type',
            size: 160,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 160,
            sortable: true,
        }),

        createImageColumn<Tool>({
            getImage: (tool) => tool.image,
            getAlt: (tool) => tool.name,
            size: 80,
        }),

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
