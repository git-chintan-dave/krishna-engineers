import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/tools';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createColumn } from '@/table/helpers/createColumn';
import type { Tool } from '@/types/tool';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';

const columnHelper = createColumnHelper<any, Tool>();

export const createToolColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'tools',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'type_label',
            sortKey: 'type',
            header: 'Type',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createStatusColumn(),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'tool',
            handleDelete,
        }),
    ]);
