import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/attendances';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createPrimaryColumn} from '@/table/helpers/primaryColumn';
import {createSortableColumn} from '@/table/helpers/sortableColumn';
import type {Attendance} from '@/types/attendance';

const columnHelper = createColumnHelper<any, Attendance>();

export const createAttendanceColumns = (
    handleDelete: (uuid: string) => void,
    canShowUserColumn: boolean,
    canEdit: boolean,
    canDelete: boolean,
) =>
    columnHelper.columns([
        ...(canDelete ? [
            createSelectColumn({
                resourceName: 'attendances',
            })
        ] : []),

        ...(canShowUserColumn ? [
            createPrimaryColumn({
                accessorKey: 'user.name',
                header: 'Employee',
                size: 240,
                sortable: false,
            }),
        ] : []),

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
            accessorKey: 'effective_working_seconds_formatted',
            header: 'Effective Hours',
            size: 160,
            cell: ({ row }) =>
                row.original.effective_working_seconds_formatted || '—',
        }),

        ...(canEdit || canDelete ? [
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

                showEdit: (row) => canEdit && !row.is_locked,

                showDelete: (row) => canDelete && !row.is_locked,
            }),
        ] : []),
    ]);
