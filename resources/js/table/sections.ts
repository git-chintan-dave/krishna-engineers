import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/sections';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createColumn } from '@/table/helpers/createColumn';
import type { Section } from '@/types/section';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';

const columnHelper = createColumnHelper<any, Section>();

export const createSectionColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'sections',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
        }),

        createStatusColumn(),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'section',
            handleDelete,
        }),
    ]);
