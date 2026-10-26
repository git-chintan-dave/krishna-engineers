import { createColumnHelper } from '@tanstack/vue-table';

import { edit } from '@/routes/attendances';
import { createActionsColumn } from '@/table/columns/actionsColumn';
import { createSelectColumn } from '@/table/columns/selectColumn';
import { createColumn } from '@/table/helpers/createColumn';
import { COLUMN_DEFAULTS } from '@/table/constants/columns';
import type { Attendance } from '@/types/attendance';

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
                      ...COLUMN_DEFAULTS.primaryColumn,
                      sortable: true,
                      primary: true,
                  }),
              ]
            : []),

        createColumn<Attendance, string | null>({
            accessorKey: 'attendance_date_formatted',
            sortKey: 'attendance_date',
            header: 'Date',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
            primary: true,
        }),

        createColumn<Attendance, string | null>({
            accessorKey: 'start_at_formatted',
            sortKey: 'start_at',
            header: 'Clock In',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<Attendance, string | null>({
            accessorKey: 'end_at_formatted',
            sortKey: 'end_at',
            header: 'Clock Out',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'total_working_seconds_formatted',
            sortKey: 'total_working_seconds',
            header: 'Total hours',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'break_seconds_formatted',
            header: 'Break Time',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'effective_working_seconds_formatted',
            sortKey: 'effective_working_seconds',
            header: 'Effective Hours',
            ...COLUMN_DEFAULTS.mediumColumn,
            align: 'center',
            sortable: true,
        }),

        createColumn<Attendance, number | null>({
            accessorKey: 'undertime_seconds_formatted',
            sortKey: 'undertime_seconds',
            header: 'Late By',
            ...COLUMN_DEFAULTS.secondaryColumn,
            align: 'center',
            sortable: true,
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
