import {createColumnHelper} from '@tanstack/vue-table';

import {edit} from '@/routes/attendances';
import {createActionsColumn} from '@/table/columns/actionsColumn';
import {createSelectColumn} from '@/table/columns/selectColumn';
import {createColumn} from '@/table/helpers/createColumn';
import type {Attendance} from '@/types/attendance';

const columnHelper = createColumnHelper<any, Attendance>();

export const createAttendanceColumns = (
    handleDelete: (uuid: string) => void,
    canShowUserColumn: boolean,
    canEdit: boolean,
    canDelete: boolean,
) =>
    columnHelper.columns([
        ...(canDelete
            ? [createSelectColumn({ resourceName: 'attendances' })]
            : []),

        ...(canShowUserColumn
            ? [
                createColumn<Attendance, string>({
                    accessorKey: 'user.name',
                    header: 'Employee',
                    size: 240,
                    sortable: true
                }),
            ]
            : []),

        createColumn<Attendance, string | null>({
            accessorKey: 'attendance_date',
            header: 'Attendance Date',
            size: 160,
            align: 'center',
            sortable: true,
            cell: ({ row }) =>
                row.original.attendance_date_formatted ?? '—',
        }),

        createColumn<Attendance, string | null>({
            accessorKey: 'start_at',
            header: 'Clock In',
            size: 150,
            align: 'center',
            sortable: true,
            cell: ({ row }) => row.original.start_at_formatted ?? '—',
        }),

        createColumn<Attendance, string | null>({
            accessorKey: 'end_at',
            header: 'Clock Out',
            size: 150,
            align: 'center',
            sortable: true,
            cell: ({ row }) => row.original.end_at_formatted ?? '—',
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'break_seconds',
            header: 'Break Time',
            size: 140,
            align: 'center',
            sortable: true,
            cell: ({ row }) =>
                row.original.break_seconds_formatted ?? '—',
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'effective_working_seconds',
            header: 'Effective Hours',
            size: 160,
            align: 'center',
            sortable: true,
            cell: ({ row }) =>
                row.original.effective_working_seconds_formatted ?? '—',
        }),

        ...(canEdit || canDelete
            ? [
                createActionsColumn({
                    editUrl: (uuid) => edit(uuid).url,
                    resourceName: 'attendance',
                    handleDelete,
                    showEdit: (row) => canEdit && !row.is_locked,
                    showDelete: (row) => canDelete && !row.is_locked,
                }),
            ]
            : []),
    ]);
