<script lang="ts" setup>
import {computed, ref} from 'vue';
import {CalendarCheck, CalendarDays, CalendarX, CircleAlert, Clock3, Coffee, LogIn, LogOut, Timer,} from '@lucide/vue';

import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

type Period = 'current' | 'previous';
type AttendanceStatus = 'present' | 'absent' | 'incomplete';

interface AttendanceRecord {
    date: string;
    day: string;
    clockIn: string;
    clockOut: string;
    effectiveWorkingSeconds: number;
    breakSeconds: number;
    undertimeSeconds: number;
    status: AttendanceStatus;
}

interface AttendancePeriod {
    label: string;
    workingDays: number;
    presentDays: number;
    absentDays: number;
    incompleteDays: number;
    totalUndertimeSeconds: number;
    records: AttendanceRecord[];
}

const selectedPeriod = ref<Period>('current');

const periodTabs = [
    { value: 'current', label: 'Current' },
    { value: 'previous', label: 'Previous' },
] as const;

const attendancePeriods: Record<Period, AttendancePeriod> = {
    current: {
        label: 'November 2026',
        workingDays: 24,
        presentDays: 21,
        absentDays: 2,
        incompleteDays: 1,
        totalUndertimeSeconds: 12000,
        records: [
            {
                date: '04 Nov 2026',
                day: 'Wednesday',
                clockIn: '08:02 AM',
                clockOut: '--:--',
                effectiveWorkingSeconds: 30000,
                breakSeconds: 3600,
                undertimeSeconds: 0,
                status: 'incomplete',
            },
            {
                date: '03 Nov 2026',
                day: 'Tuesday',
                clockIn: '08:10 AM',
                clockOut: '08:15 PM',
                effectiveWorkingSeconds: 39800,
                breakSeconds: 3600,
                undertimeSeconds: 2800,
                status: 'present',
            },
            {
                date: '02 Nov 2026',
                day: 'Monday',
                clockIn: '--:--',
                clockOut: '--:--',
                effectiveWorkingSeconds: 0,
                breakSeconds: 0,
                undertimeSeconds: 0,
                status: 'absent',
            },
            {
                date: '01 Nov 2026',
                day: 'Sunday',
                clockIn: '08:05 AM',
                clockOut: '08:10 PM',
                effectiveWorkingSeconds: 39900,
                breakSeconds: 3600,
                undertimeSeconds: 2100,
                status: 'present',
            },
        ],
    },

    previous: {
        label: 'October 2026',
        workingDays: 26,
        presentDays: 24,
        absentDays: 2,
        incompleteDays: 0,
        totalUndertimeSeconds: 14400,
        records: [
            {
                date: '31 Oct 2026',
                day: 'Saturday',
                clockIn: '08:00 AM',
                clockOut: '08:05 PM',
                effectiveWorkingSeconds: 39900,
                breakSeconds: 3600,
                undertimeSeconds: 2100,
                status: 'present',
            },
            {
                date: '30 Oct 2026',
                day: 'Friday',
                clockIn: '08:10 AM',
                clockOut: '08:00 PM',
                effectiveWorkingSeconds: 39000,
                breakSeconds: 3600,
                undertimeSeconds: 3000,
                status: 'present',
            },
            {
                date: '29 Oct 2026',
                day: 'Thursday',
                clockIn: '--:--',
                clockOut: '--:--',
                effectiveWorkingSeconds: 0,
                breakSeconds: 0,
                undertimeSeconds: 0,
                status: 'absent',
            },
        ],
    },
};

const currentPeriod = computed(
    () => attendancePeriods[selectedPeriod.value],
);

const attendanceRate = computed(() => {
    const period = currentPeriod.value;

    if (!period.workingDays) return 0;

    return Math.round(
        ((period.presentDays + period.incompleteDays) /
            period.workingDays) *
        100,
    );
});

const today = {
    clockIn: '08:02 AM',
    clockOut: '--:--',
    effectiveWorkingSeconds: 30000,
    breakSeconds: 3600,
    isWorking: true,
};

const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};

const statusConfig: Record<
    AttendanceStatus,
    { label: string; class: string }
> = {
    present: {
        label: 'Present',
        class: 'bg-green-500/10 text-green-700 dark:text-green-400',
    },
    absent: {
        label: 'Absent',
        class: 'bg-red-500/10 text-red-700 dark:text-red-400',
    },
    incomplete: {
        label: 'Incomplete',
        class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
    },
};

const todayStats = computed(() => [
    {
        label: 'Clock-in',
        value: today.clockIn,
        icon: LogIn,
        variant: 'default' as const,
    },
    {
        label: 'Clock-out',
        value: today.clockOut,
        icon: LogOut,
        description: today.isWorking ? 'Not clocked out' : undefined,
        variant: 'default' as const,
    },
    {
        label: 'Effective working time',
        value: formatDuration(today.effectiveWorkingSeconds),
        icon: Clock3,
        variant: 'default' as const,
    },
    {
        label: 'Break time',
        value: formatDuration(today.breakSeconds),
        icon: Coffee,
        variant: 'default' as const,
    },
]);

const monthlyStats = computed(() => [
    {
        label: 'Present',
        value: currentPeriod.value.presentDays,
        icon: CalendarCheck,
        description: 'Days attended',
        variant: 'success' as const,
    },
    {
        label: 'Absent',
        value: currentPeriod.value.absentDays,
        icon: CalendarX,
        description: 'Days absent',
        variant: 'destructive' as const,
    },
    {
        label: 'Incomplete',
        value: currentPeriod.value.incompleteDays,
        icon: CircleAlert,
        description: 'Open attendance',
        variant: 'warning' as const,
    },
    {
        label: 'Total undertime',
        value: formatDuration(currentPeriod.value.totalUndertimeSeconds),
        icon: Timer,
        description: 'For selected month',
        variant: 'info' as const,
    },
]);
</script>

<template>
    <main class="w-full min-w-0 space-y-5 overflow-x-hidden p-4 md:px-6 md:py-5">
        <div class="space-y-6">
            <!-- Page heading -->
            <SectionHeader
                title="My Attendance"
                description="Track your daily attendance and working hours."
                :icon="CalendarDays"
            >
                <template #actions>
                    <div class="inline-flex items-center gap-2 self-start rounded-lg border bg-card px-3 py-2 text-sm">
                        <CalendarDays class="size-4 text-muted-foreground" />
                        <span>Wednesday, 04 November 2026</span>
                    </div>
                </template>
            </SectionHeader>

            <!-- Today's attendance -->
            <section class="space-y-4 rounded-xl border bg-card p-4 sm:p-5">
                <SectionHeader
                    title="Today's Attendance"
                    description="Your current attendance details"
                >
                    <template #actions>
                    <span
                        v-if="today.isWorking"
                        class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 px-3 py-1 text-xs font-medium text-green-700 dark:text-green-400"
                    >
                        <span class="size-1.5 rounded-full bg-green-500" />
                        Working
                    </span>
                    </template>
                </SectionHeader>

                <ListingStats
                    :items="todayStats"
                    :columns="4"
                />
            </section>

            <!-- Monthly attendance -->
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
                            <p class="font-medium">Attendance Rate</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ currentPeriod.label }}
                            </p>
                        </div>

                        <p class="text-2xl font-semibold tabular-nums">
                            {{ attendanceRate }}%
                        </p>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{ width: `${attendanceRate}%` }"
                        />
                    </div>

                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ currentPeriod.presentDays + currentPeriod.incompleteDays }}
                        attended days out of {{ currentPeriod.workingDays }}
                        expected working days.
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
                            {{ currentPeriod.records.length }} recent records
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
                                <CalendarDays class="size-5 text-muted-foreground" />
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
                                <p class="text-xs text-muted-foreground">Clock-in</p>
                                <p class="mt-1 text-sm font-medium tabular-nums">
                                    {{ record.clockIn }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground">Clock-out</p>
                                <p class="mt-1 text-sm font-medium tabular-nums">
                                    {{ record.clockOut }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-muted-foreground">Effective time</p>
                                <p class="mt-1 text-sm font-medium tabular-nums">
                                    {{ formatDuration(record.effectiveWorkingSeconds) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <div class="text-right">
                                <p class="text-xs text-muted-foreground">Undertime</p>
                                <p class="mt-1 text-sm font-medium tabular-nums">
                                    {{ formatDuration(record.undertimeSeconds) }}
                                </p>
                            </div>

                            <span
                                class="min-w-[90px] rounded-full px-3 py-1 text-center text-xs font-medium"
                                :class="statusConfig[record.status].class"
                            >
                            {{ statusConfig[record.status].label }}
                        </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center px-4 py-12 text-center"
                >
                    <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-muted">
                        <CalendarDays class="size-6 text-muted-foreground" />
                    </div>

                    <p class="font-medium">No attendance records</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        No attendance records found for {{ currentPeriod.label }}.
                    </p>
                </div>
            </section>

            <p class="text-center text-xs text-muted-foreground">
                Attendance information is based on your recorded check-in and check-out times.
            </p>
        </div>
    </main>
</template>
