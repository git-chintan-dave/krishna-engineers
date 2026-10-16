import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/bandsaw';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
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
                    header: 'Bandsaw',
                    size: 180,
                    primary: true,
                }),
            ]
            : []),

        createColumn({
            accessorKey: 'part.name',
            header: 'Part',
            size: 160,
            primary: true,
        }),

        createColumn({
            accessorKey: 'section.name',
            header: 'Section',
            size: 140,
            primary: true,
        }),

        createColumn({
            accessorKey: 'section_size.size',
            header: 'Section Size',
            size: 140,
            primary: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,
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
