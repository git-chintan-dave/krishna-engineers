import { router } from '@inertiajs/vue3';
import { createColumnHelper } from '@tanstack/vue-table';

import { edit, show, markAsPaid } from '@/routes/salaries';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createPrimaryColumn } from '@/table/helpers/primaryColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';
import type { Salary } from '@/types/salary';

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
            size: 220,
            sortable: false,
        }),

        createSortableColumn({
            accessorKey: 'salary_period_start',
            header: 'Salary Period',
            size: 205,
            cell: ({ row }) => {
                const start =
                    row.original.salary_period_start_formatted ?? '—';

                const end =
                    row.original.salary_period_end_formatted ?? '—';

                return `${start} - ${end}`;
            },
        }),

        createSortableColumn({
            accessorKey: 'working_days',
            header: 'Days',
            size: 80,
        }),

        createSortableColumn({
            accessorKey: 'base_salary_formatted',
            header: 'Base Salary',
            size: 125,
            cell: (info) => `${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'incentive_formatted',
            header: 'Incentive',
            size: 110,
            cell: (info) => `${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'deduction_formatted',
            header: 'Deduction',
            size: 110,
            cell: (info) => `${info.getValue()}`,
        }),

        createSortableColumn({
            accessorKey: 'final_payable_formatted',
            header: 'Final Payable',
            size: 135,
            cell: (info) => `${info.getValue()}`,
        }),

        createStatusColumn({
            header: 'Status',
            getVariant: (status) =>
                status === 'paid'
                    ? 'default'
                    : 'secondary',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            detailsUrl: (uuid) => show(uuid).url,
            markAsPaidUrl: (uuid) => markAsPaid(uuid).url,
            resourceName: 'salary',
            handleDelete,

            handleMarkAsPaid: (uuid) => {
                router.post(markAsPaid(uuid).url);
            },

            showEdit: (row) =>
                row.status === 'draft',

            showMarkAsPaid: (row) =>
                row.status === 'draft',

            showDelete: (row) =>
                row.status === 'draft',
        }),
    ]);
