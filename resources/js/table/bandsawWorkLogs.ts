import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/bandsaw';

import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';

import { createPrimaryColumn } from '@/table/helpers/primaryColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';

import type { BandsawWorkLog } from '@/types/bandsawWorkLog';

const columnHelper =
    createColumnHelper<any, BandsawWorkLog>();

export const createBandsawWorkLogColumns = (
    handleDelete: (uuid: string) => void,
    canManage: boolean,
) =>
    columnHelper.columns([
        ...(canManage
            ? [
                createSelectColumn({
                    resourceName:
                        'bandsaw-work-logs',
                }),
            ]
            : []),

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        ...(canManage ? [
            createPrimaryColumn({
                accessorKey: 'user.name',
                header: 'Bandsaw',
                size: 180,
                sortable: false,
            }),
        ] : []),

        createPrimaryColumn({
            accessorKey: 'part.name',
            header: 'Part',
            size: 160,
            sortable: false,
        }),

        createPrimaryColumn({
            accessorKey: 'section.name',
            header: 'Section',
            size: 140,
            sortable: false,
        }),

        createPrimaryColumn({
            accessorKey: 'section_size.size',
            header: 'Section Size',
            size: 140,
            sortable: false,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,
        }),

        ...(canManage
            ? [
                createActionsColumn({
                    editUrl: (uuid) =>
                        edit(uuid).url,
                    resourceName:
                        'bandsaw work log',
                    handleDelete,
                }),
            ]
            : []),
    ]);
