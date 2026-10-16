import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/raw-material-transactions';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {RawMaterialTransaction} from '@/types/rawMaterialTransaction';

const columnHelper = createColumnHelper<any, RawMaterialTransaction>();

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
            id: 'section_size',
            accessorFn: (row) =>
                row.section_size.section
                    ? `${row.section_size.section} - ${row.section_size.size ?? '—'}`
                    : row.section_size.size ?? '—',
            header: 'Section Size',
            size: 240,
            primary: true,
        }),

        createColumn<RawMaterialTransaction, RawMaterialTransaction['type']>({
            accessorKey: 'type',
            header: 'Type',
            size: 140,
            sortable: true,
            cell: ({ row }) => row.original.type.label,
        }),

        createColumn<RawMaterialTransaction, number>({
            accessorKey: 'quantity',
            header: 'Quantity',
            size: 120,
            align: 'center',
            sortable: true,
        }),

        createColumn<RawMaterialTransaction, RawMaterialTransaction['user']>({
            accessorKey: 'user',
            header: 'User',
            size: 180,
            sortable: true,
            cell: ({ row }) => {
                const user = row.original.user;

                if (!user?.name) {
                    return '—';
                }

                return user.employee_code
                    ? `${user.name} (${user.employee_code})`
                    : user.name;
            },
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
