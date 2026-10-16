import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/section-sizes';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {SectionSize} from '@/types/sectionSize';

const columnHelper = createColumnHelper<any, SectionSize>();

export const createSectionSizeColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'section-sizes',
        }),

        createPrimaryColumn({
            id: 'section',
            accessorFn: (row: SectionSize) => row.section?.name ?? '-',
            header: 'Section',
            size: 260,
        }),

        createSortableColumn({
            accessorKey: 'size',
            header: 'Size',
            size: 180,
        }),

        createSortableColumn({
            accessorKey: 'weight',
            header: 'Weight',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 160,
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
