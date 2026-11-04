<script lang="ts" setup>
import {computed, onMounted, ref} from 'vue';
import {router} from '@inertiajs/vue3';
import {CalendarCheck, CalendarDays, CalendarX, CircleAlert, Clock3, Coffee, LogIn, LogOut, Timer,} from '@lucide/vue';

import type {ListingStatItem} from '@/components/common/ListingStats.vue';
import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';
import StatusBadge, {type StatusVariant} from '@/components/common/StatusBadge.vue';
import type {AttendanceStatus, EmployeeAttendanceDashboard, EmployeeTodayStatus,} from '@/types/dashboard';

type Period = 'current' | 'previous';

const props = defineProps<{
    attendanceDashboard?: EmployeeAttendanceDashboard | null;
}>();

const selectedPeriod = ref<Period>('current');
const loading = ref(props.attendanceDashboard === undefined);

const periodTabs = [
    {value: 'current', label: 'Current'},
    {value: 'previous', label: 'Previous'},
] as const;

const currentPeriod = computed(() => {
    if (!props.attendanceDashboard) return null;

    return props.attendanceDashboard[
        selectedPeriod.value === 'current'
            ? 'currentPeriod'
            : 'previousPeriod'
        ];
});

const today = computed(() => props.attendanceDashboard?.today ?? null);

const monthlyStats = computed<ListingStatItem[]>(() => {
    const period = currentPeriod.value;

    if (!period) return [];

    return [
        {
            label: 'Present',
            value: period.presentDays,
            icon: CalendarCheck,
            description: 'Completed attendance',
            variant: 'success',
        },
        {
            label: 'Leave',
            value: period.absentDays,
            icon: CalendarX,
            description: 'No attendance record',
            variant: 'destructive',
        },
        {
            label: 'Incomplete',
            value: period.incompleteDays,
            icon: CircleAlert,
            description: 'Open attendance',
            variant: 'warning',
        },
        {
            label: 'Total undertime',
            value: period.totalUndertimeFormatted,
            icon: Timer,
            description: 'For selected month',
            variant: 'info',
        },
    ];
});

const todayStats = computed<ListingStatItem[]>(() => {
    const attendance = today.value;

    if (!attendance) return [];

    return [
        {
            label: 'Clock-in',
            value: attendance.startAt,
            icon: LogIn,
            variant: 'default',
        },
        {
            label: 'Clock-out',
            value: attendance.endAt,
            icon: LogOut,
            description: attendance.isWorking ? 'Not clocked out' : undefined,
            variant: 'default',
        },
        {
            label: 'Effective working time',
            value: attendance.effectiveWorkingFormatted,
            icon: Clock3,
            variant: 'default',
        },
        {
            label: 'Break time',
            value: attendance.breakFormatted,
            icon: Coffee,
            variant: 'default',
        },
    ];
});

const statusVariants: Record<EmployeeTodayStatus, StatusVariant> = {
    present: 'success',
    leave: 'destructive',
    incomplete: 'warning',
    weekly_off: 'default',
};

const historyStatusVariants: Record<AttendanceStatus, StatusVariant> = {
    present: 'success',
    leave: 'destructive',
    incomplete: 'warning',
};

const isLoading = computed(() => loading.value && !props.attendanceDashboard);

onMounted(() => {
    if (props.attendanceDashboard !== undefined) {
        loading.value = false;
        return;
    }

    router.reload({
        only: ['attendanceDashboard'],
        onSuccess: () => {
            loading.value = false;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
});
</script>

<template>
    <main class="w-full min-w-0 space-y-5 overflow-x-hidden p-4 md:px-6 md:py-5">
        <div class="space-y-6">
            <SectionHeader
                title="My Attendance"
                description="Track your daily attendance and working hours."
                :icon="CalendarDays"
            >
                <template #actions>
                    <div
                        v-if="today"
                        class="inline-flex items-center gap-2 self-start rounded-lg border bg-card px-3 py-2 text-sm"
                    >
                        <CalendarDays class="size-4 text-muted-foreground"/>
                        <span>{{ today.date }}</span>
                    </div>
                </template>
            </SectionHeader>

            <!-- Loading state -->
            <div
                v-if="isLoading"
                class="space-y-5"
            >
                <div class="h-44 animate-pulse rounded-xl border bg-muted/40"/>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="item in 4"
                        :key="item"
                        class="h-28 animate-pulse rounded-xl border bg-muted/40"
                    />
                </div>
                <div class="h-48 animate-pulse rounded-xl border bg-muted/40"/>
            </div>

            <template v-else-if="attendanceDashboard && today && currentPeriod">
                <!-- Today's attendance -->
                <section class="space-y-4 rounded-xl border bg-card p-4 sm:p-5">
                    <SectionHeader
                        title="Today's Attendance"
                        description="Your current attendance details"
                    >
                        <template #actions>
                            <StatusBadge :variant="statusVariants[today.status]">
                                {{ today.statusLabel }}
                            </StatusBadge>
                        </template>
                    </SectionHeader>

                    <ListingStats
                        :items="todayStats"
                        :columns="4"
                    />
                </section>

                <!-- Monthly overview -->
                <section class="space-y-4">
                    <SectionHeader
                        title="Monthly Overview"
                        description="Your attendance summary for the selected month"
                    >
                        <template #actions>
                            <SegmentedTabs
                                v-model="selectedPeriod"
                                :items="periodTabs"
                                aria-label="Attendance period"
                                variant="primary"
                                size="sm"
                            />
                        </template>
                    </SectionHeader>

                    <ListingStats
                        :items="monthlyStats"
                        :columns="4"
                    />

                    <!-- Attendance rate -->
                    <div class="rounded-xl border bg-card p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium">
                                    Attendance Rate
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ currentPeriod.label }}
                                </p>
                            </div>

                            <p class="text-2xl font-semibold tabular-nums">
                                {{ currentPeriod.attendanceRateFormatted }}
                            </p>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{width: `${currentPeriod.attendanceRate}%`}"
                            />
                        </div>

                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ currentPeriod.attendedDays }} attended days out of
                            {{ currentPeriod.workingDays }} expected working days.
                        </p>
                    </div>
                </section>

                <!-- Attendance history -->
                <section class="overflow-hidden rounded-xl border bg-card">
                    <div class="border-b p-4 sm:p-5">
                        <SectionHeader
                            title="Attendance History"
                            :description="currentPeriod.label"
                        >
                            <template #actions>
                                <span class="text-sm text-muted-foreground">
                                    {{ currentPeriod.records.length }} working days
                                </span>
                            </template>
                        </SectionHeader>
                    </div>

                    <div
                        v-if="currentPeriod.records.length"
                        class="divide-y"
                    >
                        <div
                            v-for="record in currentPeriod.records"
                            :key="record.date"
                            class="flex flex-col gap-3 p-4 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                    <CalendarDays class="size-5 text-muted-foreground"/>
                                </div>

                                <div>
                                    <p class="text-sm font-medium">
                                        {{ record.date }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ record.day }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4 sm:min-w-[390px]">
                                <div>
                                    <p class="text-xs text-muted-foreground">
                                        Clock-in
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ record.clockIn }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-muted-foreground">
                                        Clock-out
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ record.clockOut }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-muted-foreground">
                                        Effective time
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ record.effectiveWorkingFormatted }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">
                                        Undertime
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ record.undertimeFormatted }}
                                    </p>
                                </div>

                                <StatusBadge :variant="historyStatusVariants[record.status]">
                                    {{ record.statusLabel }}
                                </StatusBadge>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center px-4 py-12 text-center"
                    >
                        <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-muted">
                            <CalendarDays class="size-6 text-muted-foreground"/>
                        </div>

                        <p class="font-medium">
                            No attendance records
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            No working days found for {{ currentPeriod.label }}.
                        </p>
                    </div>
                </section>
            </template>

            <div
                v-else
                class="rounded-xl border p-8 text-center"
            >
                <p class="font-medium">
                    Attendance information is unavailable.
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Please refresh the page and try again.
                </p>
            </div>
        </div>
    </main>
</template>
