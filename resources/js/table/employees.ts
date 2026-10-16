import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/employees';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {Employee} from '@/types/employee';

const columnHelper = createColumnHelper<any, Employee>();

export const createEmployeeColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'employees',
            isSelectable: (row: Employee) => row.role !== 'super_admin',
        }),

        createPrimaryColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 260,
            sortable: true,
        }),

        createSortableColumn({
            accessorKey: 'employee_code',
            header: 'Employee Code',
            size: 160,
        }),

        createSortableColumn({
            accessorKey: 'phone_number',
            header: 'Phone',
            size: 150,
        }),

        createSortableColumn({
            accessorKey: 'role',
            header: 'Role',
            size: 170,
            cell: ({row}: any) => row.original.role_label ?? '—',
        }),

        createSortableColumn({
            accessorKey: 'shift',
            header: 'Shift',
            size: 120,
            cell: ({row}: any) => row.original.shift_label ?? '—',
        }),

        createStatusColumn({
            header: 'Status',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'employee',
            handleDelete,
            showDelete: (row: Employee) => row.role !== 'super_admin',
        }),
    ]);
