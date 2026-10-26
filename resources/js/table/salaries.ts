import { router } from '@inertiajs/vue3';
import { createColumnHelper } from '@tanstack/vue-table';

import { edit, markAsPaid, show, regenerate } from '@/routes/salaries';
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
            ...COLUMN_DEFAULTS.mediumColumn,
            sortable: true,
            primary: true,
        }),

        createColumn<Salary, string>({
            accessorKey: 'salary_period_formatted',
            sortKey: 'salary_period_start',
            header: 'Salary Period',
            ...COLUMN_DEFAULTS.ultraWideColumn,
            sortable: true,
            primary: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'working_days',
            sortKey: 'working_days',
            header: 'Payable Days',
            ...COLUMN_DEFAULTS.mediumColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'base_salary_formatted',
            sortKey: 'base_salary',
            header: 'Base Salary',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'incentive_formatted',
            sortKey: 'incentive',
            header: 'Incentive',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'deduction_formatted',
            sortKey: 'deduction',
            header: 'Deduction',
            ...COLUMN_DEFAULTS.secondaryColumn,
            sortable: true,
            align: 'center',
        }),

        createColumn({
            accessorKey: 'final_payable_formatted',
            sortKey: 'final_payable',
            header: 'Final Payable',
            ...COLUMN_DEFAULTS.mediumColumn,
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
            regenerateUrl: (uuid) => regenerate(uuid).url,
            resourceName: 'salary',
            handleDelete,
            handleMarkAsPaid: (uuid) => {
                router.post(markAsPaid(uuid).url);
            },
            handleRegenerate: (uuid) => {
                router.post(regenerate(uuid).url);
            },
            showEdit: (row) => row.status === 'draft',
            showMarkAsPaid: (row) => row.status === 'draft',
            showDelete: (row) => row.status === 'draft',
            showRegenerate: (row) => row.status !== 'paid',
            ...COLUMN_DEFAULTS.wideColumn,
        }),
    ]);
