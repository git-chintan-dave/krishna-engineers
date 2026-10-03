import {createColumnHelper} from "@tanstack/vue-table";
import {BandsawWorkLog} from "@/types/bandsawWorkLog";
import {createPrimaryColumn} from "@/table/helpers/primaryColumn";
import {createSortableColumn} from "@/table/helpers/sortableColumn";
import {createActionsColumn} from "@/table/columns/actionsColumn";
import {edit} from "@/routes/bandsaw";
import {createSelectColumn} from "@/table/columns/selectColumn";

const columnHelper = createColumnHelper<any, BandsawWorkLog>();

export const createBandsawWorkLogColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'bandsaw',
        }),

        createPrimaryColumn({
            accessorKey: 'user.name',
            header: 'Bandsaw',
            size: 180,
            sortable: false,
        }),

        createSortableColumn({
            accessorKey: 'work_date',
            header: 'Work Date',
            size: 130,
        }),

        createSortableColumn({
            accessorKey: 'part.reference_name',
            header: 'Part',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'section_size.size',
            header: 'Section Size',
            size: 140,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 100,
        }),

        createPrimaryColumn({
            accessorKey: 'remarks',
            header: 'Remarks',
            size: 220,
            sortable: false,
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'bandsaw log',
            handleDelete,
        }),
    ])
