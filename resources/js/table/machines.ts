import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/machines';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createColumn } from '@/table/helpers/createColumn';
import type { Machine } from '@/types/machine';

const columnHelper = createColumnHelper<any, Machine>();

export const createMachineColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'machines',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 300,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'machine_number',
            header: 'Machine number',
            size: 140,
            sortable: true,
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
            resourceName: 'machine',
            handleDelete,
        }),
    ]);
