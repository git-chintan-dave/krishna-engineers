import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/attendances';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createDateColumn } from '@/table/columns/dateColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createPrimaryColumn } from '@/table/helpers/primaryColumn';
import { createSortableColumn } from '@/table/helpers/sortableColumn';
import type { Attendance } from '@/types/attendance';

const columnHelper = createColumnHelper<any, Attendance>();

export const createAttendanceColumns = (
    handleDelete: (uuid: string) => void,
) =>
    columnHelper.columns([
        createSelectColumn({
            resourceName: 'attendances',
        }),

        createPrimaryColumn({
            accessorKey: 'user.name',
            header: 'Employee',
            size: 240,
            sortable: false,
        }),

        createSortableColumn({
            accessorKey: 'attendance_date',
            header: 'Attendance Date',
            size: 160,
            cell: ({ row }) =>
                row.original.attendance_date_formatted || '—',
        }),

        createSortableColumn({
            accessorKey: 'start_at',
            header: 'Clock In',
            size: 150,
            cell: ({ row }) =>
                row.original.start_at_formatted || '—',
        }),

        createSortableColumn({
            accessorKey: 'end_at',
            header: 'Clock Out',
            size: 150,
            cell: ({ row }) =>
                row.original.end_at_formatted || '—',
        }),

        createSortableColumn({
            accessorKey: 'effective_working_seconds',
            header: 'Effective Hours',
            size: 160,
            cell: ({ row }) => {
                const seconds =
                    row.original.effective_working_seconds;

                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor(
                    (seconds % 3600) / 60,
                );

                return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            },
        }),

        createDateColumn({
            accessorKey: 'created_at',
            header: 'Created',
        }),

        createActionsColumn({
            editUrl: (uuid) => edit(uuid).url,
            resourceName: 'attendance',
            handleDelete,

            /*
            |--------------------------------------------------------------------------
            | Salary Lock
            |--------------------------------------------------------------------------
            |
            | Attendance belonging to a paid salary period is read-only.
            | The backend service still enforces this rule.
            |
            */

            showEdit: (row) => !row.is_locked,

            showDelete: (row) => !row.is_locked,
        }),
    ]);
