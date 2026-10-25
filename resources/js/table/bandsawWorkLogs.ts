import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/bandsaw';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import type {BandsawWorkLog} from '@/types/bandsawWorkLog';

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

        createDateColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
        }),

        ...(canManage
            ? [
                createColumn({
                    accessorKey: 'user.name',
                    sortKey: 'user.name',
                    header: 'Bandsaw',
                    size: 180,
                    minSize: 180,
                    maxSize: 180,
                    sortable: true,
                }),
            ]
            : []),

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
            size: 140,
            minSize: 140,
            maxSize: 140,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'section_size.size',
            sortKey: 'section_size.size',
            header: 'Section Size',
            size: 140,
            minSize: 140,
            maxSize: 140,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,
            minSize: 100,
            maxSize: 100,
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
