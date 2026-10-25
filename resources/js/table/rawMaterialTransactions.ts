import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/raw-material-transactions';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createTransactionQuantityColumn, createTransactionTypeColumn,} from '@/table/columns/transactionColumns';
import {createColumn} from '@/table/helpers/createColumn';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
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
            accessorKey: 'section_size.section',
            sortKey: 'sectionSize.section.name',
            header: 'Section',
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
        }),

        createColumn<RawMaterialTransaction, string>({
            accessorKey: 'section_size.size',
            sortKey: 'sectionSize.size',
            header: 'Section Size',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createTransactionTypeColumn<RawMaterialTransaction>({
            accessorKey: 'type',
            size: 140,
            getType: (row) => row.type,
        }),

        createTransactionQuantityColumn<RawMaterialTransaction>({
            getQuantity: (row) => row.quantity,
            size: 120,
        }),

        createColumn<RawMaterialTransaction, RawMaterialTransaction['user']>({
            accessorKey: 'user.name',
            sortKey: 'user.name',
            header: 'User',
            size: 180,
            minSize: 180,
            maxSize: 180,
            sortable: true,
        }),

        createDateColumn(),

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
