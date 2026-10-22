import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/raw-material-transactions';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import {RawMaterialTransaction, RawMaterialTransactionType} from '@/types/rawMaterialTransaction';
import type {ToolTransactionType} from "@/types/toolTransaction";
import {h} from "vue";

const columnHelper = createColumnHelper<any, RawMaterialTransaction>();

const getTypeClass = (type: ToolTransactionType) => {
    switch (type) {
        case 'opening':
            return [
                'bg-blue-50 text-blue-700',
                'dark:bg-blue-950/40 dark:text-blue-400',
            ];

        case 'purchase':
            return [
                'bg-green-50 text-green-700',
                'dark:bg-green-950/40 dark:text-green-400',
            ];

        case 'issue':
            return [
                'bg-orange-50 text-orange-700',
                'dark:bg-orange-950/40 dark:text-orange-400',
            ];

        case 'adjustment':
            return [
                'bg-slate-50 text-slate-700',
                'dark:bg-slate-950/40 dark:text-slate-400',
            ];
    }
};

export const createRawMaterialTransactionColumns = (
    handleDelete: (uuid: string) => void,
    canEdit: boolean,
    canDelete: boolean,
) =>
    columnHelper.columns([
        ...(canDelete
            ? [
                createSelectColumn({
                    resourceName: 'raw-material-transactions',
                }),
            ]
            : []),

        createColumn<RawMaterialTransaction, string>({
            accessorKey: 'section_size.section',
            sortKey: 'sectionSize.section.name',
            header: 'Section',
            size: 120,
            sortable: true,
        }),

        createColumn<RawMaterialTransaction, string>({
            accessorKey: 'section_size.size',
            sortKey: 'sectionSize.size',
            header: 'Section Size',
            size: 120,
            sortable: true,
        }),

        createColumn<RawMaterialTransaction, string>({
            accessorKey: 'type',
            header: 'Type',
            size: 140,
            sortable: true,
            cell: ({ row }) => {
                const type = row.original.type?.value as
                    | RawMaterialTransactionType
                    | undefined;

                if (!type) {
                    return '—';
                }

                const label = row.original.type?.label ?? type;

                return h(
                    'span',
                    {
                        class: [
                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                            getTypeClass(type),
                        ],
                    },
                    label,
                );
            },
        }),

        createColumn<RawMaterialTransaction, number>({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
            sortable: true,
            cell: ({ row }) => {
                const quantity = row.original.quantity;

                return h(
                    'span',
                    {
                        class:
                            quantity < 0
                                ? 'font-medium text-destructive'
                                : undefined,
                    },
                    String(quantity),
                );
            },
        }),

        createColumn<RawMaterialTransaction, RawMaterialTransaction['user']>({
            accessorKey: 'user.name',
            sortKey: 'user.name',
            header: 'User',
            size: 180,
            sortable: true,
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        ...(canEdit || canDelete
            ? [
                createActionsColumn({
                    editUrl: (uuid) => edit(uuid).url,
                    resourceName: 'raw material transaction',
                    handleDelete,
                    showEdit: () => canEdit,
                    showDelete: () => canDelete,
                }),
            ]
            : []),
    ]);
