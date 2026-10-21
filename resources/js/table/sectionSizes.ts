import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/section-sizes';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {SectionSize} from '@/types/sectionSize';

const columnHelper = createColumnHelper<any, SectionSize>();

export const createSectionSizeColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'section-sizes',
        }),

        createColumn({
            accessorKey: 'section.name',
            sortKey: 'section.name',
            header: 'Section',
            size: 160,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'size',
            header: 'Size',
            size: 180,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 160,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'weight',
            header: 'Weight',
            size: 160,
            sortable: true,
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'section size',
            handleDelete,
        }),
    ]);
