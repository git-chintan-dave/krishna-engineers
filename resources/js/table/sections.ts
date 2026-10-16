import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/sections';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import type {Section} from '@/types/section';

const columnHelper = createColumnHelper<any, Section>();

export const createSectionColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'sections',
        }),

        createPrimaryColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 300,
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
            resourceName: 'section',
            handleDelete,
        }),
    ]);
