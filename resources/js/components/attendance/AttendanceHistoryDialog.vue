<script lang="ts" setup>
import axios from 'axios';
import {CalendarDays, LoaderCircle} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import {Button} from '@/components/ui/button';

type AttendanceRecord = {
    id: number | string;
    attendance_date?: string | null;
    attendance_date_formatted?: string | null;
    shift?: string | null;
    start_at?: string | null;
    end_at?: string | null;
    start_at_formatted?: string | null;
    end_at_formatted?: string | null;
    break_seconds_formatted?: string | null;
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

const dateRangeLabel = computed(
    () => `${props.startDate ?? ''} – ${props.endDate ?? ''}`,
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

const loadAttendance = async (): Promise<void> => {
    if (!hasFilters.value) {
        records.value = [];
        errorMessage.value =
            'Select an employee and salary period to view attendance.';
        return;
    }

    const currentVersion = ++requestVersion.value;

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

watch(open, (isOpen) => {
    if (isOpen) {
        void loadAttendance();
    } else {
        requestVersion.value++;
        loading.value = false;
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="open = false"
            @keydown.esc="open = false"
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
                        @click="open = false"
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
                        class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm"
                    >
                        {{ errorMessage }}
                    </div>

                    <div
                        v-else-if="records.length === 0"
                        class="py-12 text-center text-sm text-muted-foreground"
                    >
                        No attendance records found for this period.
                    </div>

                    <div v-else class="overflow-x-auto rounded-lg border">
                        <table class="w-full min-w-[700px] text-sm">
                            <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">
                                    Date
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Start
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    End
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Break
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    Undertime
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y">
                            <tr
                                v-for="record in records"
                                :key="record.id"
                            >
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{
                                        record.attendance_date_formatted ??
                                        formatDate(record.attendance_date)
                                    }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{
                                        record.start_at_formatted ??
                                        record.start_at ??
                                        '—'
                                    }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{
                                        record.end_at_formatted ??
                                        record.end_at ??
                                        '—'
                                    }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{
                                        record.break_seconds_formatted ??
                                        '—'
                                    }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{
                                        record.undertime_seconds_formatted ??
                                        '—'
                                    }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <footer
                    class="border-t px-5 py-3 text-xs text-muted-foreground"
                >
                    Showing up to 50 records, newest first.
                </footer>
            </section>
        </div>
    </Teleport>
</template>
