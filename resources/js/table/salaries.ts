import {createColumnHelper} from '@tanstack/vue-table';

import {edit, markAsPaid} from '@/routes/salaries';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createDateColumn} from '@/table/columns/dateColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {Salary} from '@/types/salary';
import {router} from "@inertiajs/vue3";

const columnHelper = createColumnHelper<any, Salary>();

export const createSalaryColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'salaries',
        }),

        createPrimaryColumn<Salary>({
            accessorFn: (salary) =>
                `${salary.user.employee_code} - ${salary.user.name}`,
            id: 'employee',
            header: 'Employee',
            size: 240,
            sortable: true,
        }),

        createSortableColumn({
            accessorKey: 'salary_period_start',
            header: 'Period Start',
            size: 150,
            cell: ({ row }) =>
                row.original.salary_period_start_formatted ?? '—',
        }),

        createSortableColumn({
            accessorKey: 'salary_period_end',
            header: 'Period End',
            size: 150,
            cell: ({ row }) =>
                row.original.salary_period_end_formatted ?? '—',
        }),

        createSortableColumn({
            accessorKey: 'working_days',
            header: 'Working Days',
            size: 130,
        }),

        createSortableColumn({
            accessorKey: 'base_salary',
            header: 'Base Salary',
            size: 130,
            cell: (info) => `₹${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'incentive',
            header: 'Incentive',
            size: 130,
            cell: (info) => `₹${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'deduction',
            header: 'Deduction',
            size: 130,
            cell: (info) => `₹${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'final_payable',
            header: 'Final Payable',
            size: 130,
            cell: (info) => `₹${info.getValue()}`,
        }),

        createStatusColumn({
            header: 'Status',
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            markAsPaidUrl: (uuid) => markAsPaid(uuid).url,
            resourceName: 'salary',
            handleDelete,
            handleMarkAsPaid: (uuid) => {
                router.post(
                    markAsPaid(uuid).url,
                );
            },
            showEdit: (row) =>
                row.status === 'draft',
            showMarkAsPaid: (row) =>
                row.status === 'draft',
            showDelete: (row) =>
                row.status === 'draft',
        }),
    ]);
