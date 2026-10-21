import {createColumnHelper} from '@tanstack/vue-table';
import {edit} from '@/routes/parts';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createColumn} from '@/table/helpers/createColumn';
import {createImageColumn} from "@/table/columns/imageColumn";
import type {Part} from '@/types/part';

const columnHelper = createColumnHelper<any, Part>();

export const createPartColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'parts',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Part Name',
            size: 260,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'section.name',
            sortKey: 'section.name',
            header: 'Section',
            size: 160,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'new_part_name',
            sortKey: 'new_part_name',
            header: 'New Part Name',
            size: 220,
            sortable: true,
        }),

        // createColumn({
        //     accessorKey: 'cutting_size',
        //     sortKey: 'cutting_size',
        //     header: 'Cutting Size',
        //     size: 180,
        //     sortable: true,
        // }),
        //
        // createColumn({
        //     accessorKey: 'part_weight',
        //     sortKey: 'part_weight',
        //     header: 'Part Weight',
        //     size: 160,
        //     sortable: true,
        // }),
        //
        // createColumn({
        //     accessorKey: 'raw_material_weight',
        //     sortKey: 'raw_material_weight',
        //     header: 'Raw Material Weight',
        //     size: 190,
        //     sortable: true,
        // }),
        //
        // createImageColumn<Part>({
        //     getImage: (part) => part.image,
        //     getAlt: (part) => part.name,
        //     size: 100,
        // }),

        createStatusColumn({
            header: 'Status',
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'part',
            handleDelete,
        }),
    ]);
