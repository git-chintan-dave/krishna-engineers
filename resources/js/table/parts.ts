import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/parts';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createImageColumn } from '@/table/columns/imageColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';
import { createColumn } from '@/table/helpers/createColumn';
import type { Part } from '@/types/part';

const columnHelper = createColumnHelper<any, Part>();

export const createPartColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'parts',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Part Name',
            ...COLUMN_DEFAULTS.primaryColumn,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'section.name',
            sortKey: 'section.name',
            header: 'Section',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'new_part_name',
            sortKey: 'new_part_name',
            header: 'New Part Name',
            ...COLUMN_DEFAULTS.mediumColumn,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'cutting_size',
            sortKey: 'cutting_size',
            header: 'Cutting Size',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'part_weight',
            sortKey: 'part_weight',
            header: 'Part Weight',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'raw_material_weight',
            sortKey: 'raw_material_weight',
            header: 'Raw Material Weight',
            ...COLUMN_DEFAULTS.extraWideColumn,
            sortable: true,
            align: 'center',
        }),

        createImageColumn<Part>({
            getImage: (part) => part.image,
            getAlt: (part) => part.name,
        }),

        createStatusColumn(),

        createDateColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'part',
            handleDelete,
        }),
    ]);
