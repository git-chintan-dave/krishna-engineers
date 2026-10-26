import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/employees';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createColumn} from '@/table/helpers/createColumn';
import {COLUMN_DEFAULTS} from '@/table/constants/columns';
import type {Employee} from '@/types/employee';

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
            ...COLUMN_DEFAULTS.flexiblePrimaryColumn,
            sortable: true,
            primary: true,
        }),

        createColumn({
            accessorKey: 'employee_code',
            header: 'Employee Code',
            ...COLUMN_DEFAULTS.wideColumn,
            sortable: true,
            align: 'center'
        }),

        createColumn({
            accessorKey: 'phone_number',
            header: 'Phone',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
        }),

        createColumn<Employee, string>({
            accessorKey: 'role_label',
            sortKey: 'role',
            header: 'Role',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center'
        }),

        createColumn<Employee, string>({
            accessorKey: 'shift_label',
            sortKey: 'shift',
            header: 'Shift',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center'
        }),

        createStatusColumn(),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'employee',
            handleDelete,
            showDelete: (row: Employee) => row.role !== 'super_admin',
        }),
    ]);
