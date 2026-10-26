import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/bandsaw';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createColumn } from '@/table/helpers/createColumn';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';
import type { BandsawWorkLog } from '@/types/bandsawWorkLog';

const columnHelper = createColumnHelper<any, BandsawWorkLog>();

export const createBandsawWorkLogColumns = (
    handleDelete: (uuid: string) => void,
    canManage: boolean,
) =>
    columnHelper.columns([
        ...(canManage
            ? [
                  createSelectColumn({
                      resourceName: 'bandsaw-work-logs',
                  }),
              ]
            : []),

        ...(canManage
            ? [
                  createColumn({
                      accessorKey: 'user.name',
                      sortKey: 'user.name',
                      header: 'Bandsaw',
                      ...COLUMN_DEFAULTS.smallColumn,
                      sortable: true,
                      primary: true,
                  }),
              ]
            : []),

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        createColumn({
            accessorKey: 'part.name',
            sortKey: 'part.name',
            header: 'Part',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'section.name',
            sortKey: 'section.name',
            header: 'Section',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'section_size.size',
            sortKey: 'section_size.size',
            header: 'Section Size',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            ...COLUMN_DEFAULTS.smallColumn,
            sortable: true,
        }),

        ...(canManage
            ? [
                  createActionsColumn({
                      editUrl: (uuid) => edit(uuid).url,
                      resourceName: 'bandsaw work log',
                      handleDelete,
                  }),
              ]
            : []),
    ]);
