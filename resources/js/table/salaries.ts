import {router} from '@inertiajs/vue3';
import {createColumnHelper} from '@tanstack/vue-table';

import {edit, markAsPaid, show} from '@/routes/salaries';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createStatusColumn} from '@/table/columns/statusColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {Salary} from '@/types/salary';

const columnHelper = createColumnHelper<any, Salary>();

export const createSalaryColumns = (handleDelete: (uuid: string) => void) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'salaries',
        }),

        createColumn({
            accessorKey: 'user.name',
            header: 'Employee',
            size: 240,
            primary: true,
        }),

        createColumn<Salary, string>({
            accessorKey: 'salary_period_start',
            header: 'Salary Period',
            size: 205,
            sortable: true,
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
            header: 'Days',
            size: 80,
            sortable: true,
        }),

        createColumn({
            accessorKey: 'base_salary_formatted',
            header: 'Base Salary',
            size: 125,
            sortable: true,
            cell: (info) => `${info.getValue()}`,
        }),

        createColumn({
            accessorKey: 'incentive_formatted',
            header: 'Incentive',
            size: 110,
            sortable: true,
            cell: (info) => `${info.getValue()}`,
        }),

        createColumn({
            accessorKey: 'deduction_formatted',
            header: 'Deduction',
            size: 110,
            sortable: true,
            cell: (info) => `${info.getValue()}`,
        }),

        createColumn({
            accessorKey: 'final_payable_formatted',
            header: 'Final Payable',
            size: 135,
            sortable: true,
            cell: (info) => `${info.getValue()}`,
        }),

        createStatusColumn({
            header: 'Status',
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
