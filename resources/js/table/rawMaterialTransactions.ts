import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/raw-material-transactions';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {RawMaterialTransaction} from '@/types/rawMaterialTransaction';

import {edit as editBandsawWorkLog} from '@/routes/bandsaw';
import {router} from "@inertiajs/vue3";
import {h} from "vue";

const columnHelper = createColumnHelper<
    any,
    RawMaterialTransaction
>();

export const createRawMaterialTransactionColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'raw-material-transactions',
        }),

        createPrimaryColumn<RawMaterialTransaction>({
            accessorFn: (row) =>
                row.section_size.section
                    ? `${row.section_size.section} - ${row.section_size.size}`
                    : row.section_size.size,
            id: 'section_size',
            header: 'Section Size',
            size: 240,
        }),

        createSortableColumn({
            accessorKey: 'type',
            header: 'Type',
            size: 140,
            cell: ({ row }) => row.original.type.label,
        }),

        createSortableColumn({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
        }),

        createSortableColumn({
            accessorKey: 'user',
            header: 'User',
            size: 180,
            cell: ({ row }) => {
                const user = row.original.user;

                if (!user.name) {
                    return '—';
                }

                return user.employee_code
                    ? `${user.name} (${user.employee_code})`
                    : user.name;
            },
        }),

        createSortableColumn({
            accessorKey: 'reference',
            header: 'Reference',
            size: 200,
            cell: ({ row }) => {
                const reference = row.original.reference;

                if (!reference) {
                    return '—';
                }

                return h(
                    'a',
                    {
                        href: editBandsawWorkLog(reference.uuid).url,
                        class: 'font-medium text-primary hover:underline',
                    },
                    reference.label,
                );
            },
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'raw material transaction',
            handleDelete,
        }),
    ]);
