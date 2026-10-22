import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/employees';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createColumn } from '@/table/helpers/createColumn';
import type { Employee } from '@/types/employee';

const columnHelper = createColumnHelper<any, Employee>();

export const createEmployeeColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'employees',
            isSelectable: (row: Employee) => row.role !== 'super_admin',
        }),

        createColumn({
            accessorKey: 'name',
            header: 'Name',
            size: 260,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'employee_code',
            header: 'Employee Code',
            size: 160,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'phone_number',
            header: 'Phone',
            size: 150,
            sortable: true,
        }),

        createColumn<Employee, string>({
            accessorKey: 'role',
            header: 'Role',
            size: 170,
            sortable: true,
            cell: ({ row }) => row.original.role_label ?? '—',
        }),

        createColumn<Employee, string>({
            accessorKey: 'shift',
            header: 'Shift',
            size: 120,
            sortable: true,
            cell: ({ row }) => row.original.shift_label ?? '—',
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
