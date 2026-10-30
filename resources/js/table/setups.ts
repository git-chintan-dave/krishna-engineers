import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/setups';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {Setup} from '@/types/setup';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';

const columnHelper = createColumnHelper<any, Setup>();

export const createSetupColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'setups',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
            primary: true,
        }),

        createStatusColumn(),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'setup',
            handleDelete,
        }),
    ]);
