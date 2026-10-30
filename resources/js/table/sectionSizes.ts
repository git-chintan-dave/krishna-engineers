import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/section-sizes';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {SectionSize} from '@/types/sectionSize';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';

const columnHelper = createColumnHelper<any, SectionSize>();

export const createSectionSizeColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'section-sizes',
            pin: 'left',
        }),

        createColumn({
            accessorKey: 'section.name',
            sortKey: 'section.name',
            header: 'Section',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
            primary: true,
            pin: 'left',
        }),

        createColumn({
            accessorKey: 'size',
            header: 'Size',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'weight',
            header: 'Weight',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'section size',
            handleDelete,
        }),
    ]);
