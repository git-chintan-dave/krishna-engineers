import { router } from '@inertiajs/vue3';
import { createColumnHelper } from '@tanstack/vue-table';

import { edit, markAsPaid, show } from '@/routes/salaries';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createStatusColumn } from '@/table/columns/statusColumn';
import { createColumn } from '@/table/helpers/createColumn';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';
import type { Salary } from '@/types/salary';

const columnHelper = createColumnHelper<any, Salary>();

export const createSalaryColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'salaries',
        }),

        createColumn<Salary, string>({
            accessorKey: 'user.name',
            sortKey: 'user.name',
            header: 'Employee',
            ...COLUMN_DEFAULTS.primaryColumn,
            sortable: true,
        }),

        createColumn<Salary, string>({
            accessorKey: 'salary_period_start',
            sortKey: 'salary_period_start',
            header: 'Salary Period',
            size: 205,
            minSize: 205,
            maxSize: 205,
            sortable: true,
            align: 'center',
            cell: ({ row }) => {
                const start =
                    row.original.salary_period_start_formatted ?? '—';

                const end =
                    row.original.salary_period_end_formatted ?? '—';

                return `${start} - ${end}`;
            },
        }),

        createColumn({
            accessorKey: 'working_days',
            sortKey: 'working_days',
            header: 'Payable Days',
            size: 125,
            minSize: 125,
            maxSize: 125,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'base_salary_formatted',
            sortKey: 'base_salary',
            header: 'Base Salary',
            size: 140,
            minSize: 140,
            maxSize: 140,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'incentive_formatted',
            sortKey: 'incentive',
            header: 'Incentive',
            size: 125,
            minSize: 125,
            maxSize: 125,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'deduction_formatted',
            sortKey: 'deduction',
            header: 'Deduction',
            size: 125,
            minSize: 125,
            maxSize: 125,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'final_payable_formatted',
            sortKey: 'final_payable',
            header: 'Final Payable',
            size: 155,
            minSize: 155,
            maxSize: 155,
            sortable: true,
            align: 'center',
        }),

        createStatusColumn({
            getVariant: (status) =>
                status === 'paid' ? 'default' : 'secondary',
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
            showEdit: (row) => row.status === 'draft',
            showMarkAsPaid: (row) => row.status === 'draft',
            showDelete: (row) => row.status === 'draft',
        }),
    ]);
