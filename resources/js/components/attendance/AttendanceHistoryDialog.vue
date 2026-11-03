<script lang="ts" setup>
import axios from 'axios';
import {CalendarDays, LoaderCircle} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import {Button} from '@/components/ui/button';

type AttendanceType = 'attendance' | 'week_off' | 'on_leave';

type AttendanceRecord = {
    id: number | string | null;
    uuid?: string | null;
    attendance_date?: string | null;
    attendance_date_formatted?: string | null;
    attendance_type?: AttendanceType;
    day_status?: string;
    is_missing?: boolean;
    start_at?: string | null;
    end_at?: string | null;
    start_at_formatted?: string | null;
    end_at_formatted?: string | null;
    total_working_seconds_formatted?: string | null;
    break_seconds_formatted?: string | null;
    effective_working_seconds_formatted?: string | null;
    undertime_seconds_formatted?: string | null;
};

type AttendanceHistoryResponse = {
    data: AttendanceRecord[];
    meta?: {
        current_page?: number;
        last_page?: number;
        total?: number;
    };
};

const props = defineProps<{
    userId: number | string | null | undefined;
    startDate: string | null | undefined;
    endDate: string | null | undefined;
}>();

const open = defineModel<boolean>('open', { default: false });

const loading = ref(false);
const errorMessage = ref('');
const records = ref<AttendanceRecord[]>([]);
const requestVersion = ref(0);

const hasFilters = computed(
    () =>
        props.userId !== null &&
        props.userId !== undefined &&
        props.userId !== '' &&
        Boolean(props.startDate) &&
        Boolean(props.endDate),
);

const formatDate = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return value;
    }

    return new Date(year, month - 1, day).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const getRecordType = (record: AttendanceRecord): AttendanceType => {
    if (record.attendance_type) {
        return record.attendance_type;
    }

    return record.is_missing ? 'on_leave' : 'attendance';
};

const getStatusLabel = (record: AttendanceRecord): string => {
    return (
        record.day_status ??
        {
            attendance: 'Attendance',
            week_off: 'Week Off',
            on_leave: 'On Leave',
        }[getRecordType(record)]
    );
};

const getStatusClass = (record: AttendanceRecord): string => {
    return {
        attendance:
            'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
        week_off:
            'border-border bg-muted text-muted-foreground',
        on_leave:
            'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    }[getRecordType(record)];
};

const getRowClass = (record: AttendanceRecord): string => {
    return {
        attendance: '',
        week_off: 'bg-muted/40 text-muted-foreground',
        on_leave: 'bg-amber-500/5',
    }[getRecordType(record)];
};

const getRecordKey = (record: AttendanceRecord): string => {
    return (
        record.uuid ??
        record.attendance_date ??
        String(record.id)
    );
};

const loadAttendance = async (): Promise<void> => {
    const currentVersion = ++requestVersion.value;

    if (!hasFilters.value) {
        records.value = [];
        loading.value = false;
        errorMessage.value =
            'Select an employee and salary period to view attendance.';
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get<AttendanceHistoryResponse>(
            '/people/attendances/history',
            {
                params: {
                    user_id: props.userId,
                    start_date: props.startDate,
                    end_date: props.endDate,
                    per_page: 50,
                },
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        if (currentVersion !== requestVersion.value) {
            return;
        }

        records.value = response.data.data;
    } catch (error: unknown) {
        if (currentVersion !== requestVersion.value) {
            return;
        }

        records.value = [];

        if (axios.isAxiosError(error) && error.response?.status === 403) {
            errorMessage.value =
                'You do not have permission to view attendance records.';
        } else if (
            axios.isAxiosError(error) &&
            error.response?.status === 422
        ) {
            errorMessage.value =
                'The employee or salary date range is invalid.';
        } else {
            errorMessage.value =
                'Attendance could not be loaded. Please try again.';
        }
    } finally {
        if (currentVersion === requestVersion.value) {
            loading.value = false;
        }
    }
};

const closeDialog = (): void => {
    open.value = false;
};

watch(
    () => [open.value, props.userId, props.startDate, props.endDate],
    ([isOpen]) => {
        if (isOpen) {
            void loadAttendance();
        } else {
            requestVersion.value++;
            loading.value = false;
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeDialog"
            @keydown.esc="closeDialog"
        >
            <section
                aria-labelledby="attendance-history-title"
                aria-modal="true"
                class="flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border bg-background shadow-xl"
                role="dialog"
            >
                <header
                    class="flex items-start justify-between gap-4 border-b p-5"
                >
                    <div>
                        <h2
                            id="attendance-history-title"
                            class="flex items-center gap-2 font-semibold"
                        >
                            <CalendarDays class="size-5" />
                            Attendance History
                        </h2>

                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ formatDate(startDate) }}
                            <span class="px-1">–</span>
                            {{ formatDate(endDate) }}
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        @click="closeDialog"
                    >
                        Close
                    </Button>
                </header>

                <div class="min-h-0 flex-1 overflow-auto p-5">
                    <div
                        v-if="loading"
                        class="flex items-center justify-center gap-2 py-12 text-sm text-muted-foreground"
                    >
                        <LoaderCircle class="size-4 animate-spin" />
                        Loading attendance records...
                    </div>

                    <div
                        v-else-if="errorMessage"
                        class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
                    >
                        {{ errorMessage }}
                    </div>

                    <div
                        v-else-if="records.length === 0"
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        No attendance records found for this period.
                    </div>

                    <template v-else>
                        <div class="mb-4 flex flex-wrap items-center gap-3 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="size-2 rounded-full bg-emerald-500"
                                />
                                <span class="text-muted-foreground">
                                    Attendance
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span
                                    class="size-2 rounded-full bg-muted-foreground"
                                />
                                <span class="text-muted-foreground">
                                    Week Off
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span
                                    class="size-2 rounded-full bg-amber-500"
                                />
                                <span class="text-muted-foreground">
                                    On Leave
                                </span>
                            </div>
                        </div>

                        <div class="max-h-[60vh] overflow-auto rounded-lg border">
                            <table class="w-full min-w-212 text-sm">
                                <thead class="sticky top-0 z-10 bg-muted text-left shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 font-medium">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Clock In
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Clock Out
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Total Hours
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Break Time
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Effective Hours
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Undertime
                                    </th>
                                </tr>
                                </thead>

                                <tbody class="divide-y">
                                <tr
                                    v-for="record in records"
                                    :key="getRecordKey(record)"
                                    :class="getRowClass(record)"
                                >
                                    <td
                                        class="px-4 py-3 whitespace-nowrap font-medium"
                                    >
                                        {{
                                            record.attendance_date_formatted ??
                                            formatDate(record.attendance_date)
                                        }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                                :class="getStatusClass(record)"
                                            >
                                                {{ getStatusLabel(record) }}
                                            </span>
                                    </td>

                                    <template v-if="!record.is_missing">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.start_at_formatted ??
                                                record.start_at ??
                                                '—'
                                            }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.end_at_formatted ??
                                                record.end_at ??
                                                '—'
                                            }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.total_working_seconds_formatted ??
                                                '—'
                                            }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.break_seconds_formatted ??
                                                '—'
                                            }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.effective_working_seconds_formatted ??
                                                '—'
                                            }}
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{
                                                record.undertime_seconds_formatted ??
                                                '—'
                                            }}
                                        </td>
                                    </template>

                                    <td
                                        v-else
                                        colspan="6"
                                        class="px-4 py-3 text-center text-xs italic text-muted-foreground"
                                    >
                                        No attendance recorded
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </section>
        </div>
    </Teleport>
</template>
