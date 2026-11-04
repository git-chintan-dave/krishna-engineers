<script lang="ts" setup>
import {computed, ref} from 'vue';
import {CalendarX, Clock3, Coffee, TrendingUp, UserCheck, Users,} from '@lucide/vue';

import type {ListingStatItem} from '@/components/common/ListingStats.vue';
import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import type {SegmentedTabItem} from '@/components/common/SegmentedTabs.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';
import type {ProgressSummaryItem} from '@/components/common/ProgressSummary.vue';
import ProgressSummary from '@/components/common/ProgressSummary.vue';
import StatusBadge, {type StatusVariant} from '@/components/common/StatusBadge.vue';
import type {AttendanceStatus, ManagementAttendanceDashboard, ManagementAttendancePeriod,} from '@/types/dashboard';

type Period = 'today' | 'yesterday';
type StatusFilter = 'all' | AttendanceStatus;

const props = defineProps<{
    attendanceDashboard?: ManagementAttendanceDashboard | null;
}>();

const selectedPeriod = ref<Period>('today');
const selectedStatus = ref<StatusFilter>('all');

const periodTabs: readonly SegmentedTabItem[] = [
    {value: 'today', label: 'Today'},
    {value: 'yesterday', label: 'Yesterday'},
];

const statusTabs: readonly SegmentedTabItem[] = [
    {value: 'all', label: 'All'},
    {value: 'present', label: 'Present'},
    {value: 'leave', label: 'Leave'},
    {value: 'incomplete', label: 'Incomplete'},
];

const activePeriod = computed<ManagementAttendancePeriod | null>(() => {
    return props.attendanceDashboard?.[selectedPeriod.value] ?? null;
});

const filteredEmployees = computed(() => {
    const employees = activePeriod.value?.employees ?? [];

    if (selectedStatus.value === 'all') {
        return employees;
    }

    return employees.filter(
        employee => employee.status === selectedStatus.value,
    );
});

const attendanceSummaryCards = computed<ListingStatItem[]>(() => {
    const period = activePeriod.value;

    if (!period) return [];

    return [
        {
            label: 'Total employees',
            value: period.totalEmployees,
            description: 'Expected employees',
            icon: Users,
            variant: 'default',
        },
        {
            label: 'Present',
            value: period.present,
            description: `${period.attendancePercentageFormatted} attendance`,
            icon: UserCheck,
            variant: 'success',
        },
        {
            label: 'Leave',
            value: period.leave,
            description: 'No attendance record',
            icon: CalendarX,
            variant: 'destructive',
        },
        {
            label: 'Incomplete',
            value: period.incomplete,
            description: 'Attendance not completed',
            icon: Clock3,
            variant: 'warning',
        },
    ];
});

const workingHoursCards = computed<ListingStatItem[]>(() => {
    const period = activePeriod.value;

    if (!period) return [];

    return [
        {
            label: 'Avg. effective time',
            value: period.averageWorkingFormatted,
            description: 'Per attended employee',
            icon: Clock3,
            variant: 'primary',
        },
        {
            label: 'Avg. break time',
            value: period.averageBreakFormatted,
            description: 'Per attended employee',
            icon: Coffee,
            variant: 'default',
        },
        {
            label: 'Total undertime',
            value: period.totalUndertimeFormatted,
            description: 'Combined employee undertime',
            icon: TrendingUp,
            variant: 'warning',
        },
    ];
});

const attendanceProgressItems = computed<ProgressSummaryItem[]>(() => {
    const period = activePeriod.value;

    if (!period) return [];

    return [
        {
            key: 'present',
            label: 'Present',
            value: period.present,
            color: 'bg-green-500',
        },
        {
            key: 'leave',
            label: 'Leave',
            value: period.leave,
            color: 'bg-red-500',
        },
        {
            key: 'incomplete',
            label: 'Incomplete',
            value: period.incomplete,
            color: 'bg-amber-500',
        },
    ];
});

const statusVariants: Record<AttendanceStatus, StatusVariant> = {
    present: 'success',
    leave: 'destructive',
    incomplete: 'warning',
};

const statusLabels: Record<AttendanceStatus, string> = {
    present: 'Present',
    leave: 'Leave',
    incomplete: 'Incomplete',
};
</script>

<template>
    <div class="space-y-6">
        <SectionHeader
            title="Attendance overview"
            description="Monitor employee attendance and working hours"
            :icon="Users"
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

        <!-- Loading -->
        <div
            v-if="!props.attendanceDashboard"
            class="space-y-4"
        >
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="item in 4"
                    :key="item"
                    class="h-28 animate-pulse rounded-xl border bg-muted/40"
                />
            </div>
            <div class="h-36 animate-pulse rounded-xl border bg-muted/40" />
            <div class="h-72 animate-pulse rounded-xl border bg-muted/40" />
        </div>

        <template v-else-if="activePeriod">
            <div
                v-if="!activePeriod.isWorkingDay"
                class="rounded-xl border border-border bg-muted/30 p-4 text-sm text-muted-foreground"
            >
                Sunday is the weekly off. Attendance is not expected on this date.
            </div>

            <template v-else>
                <ListingStats
                    :items="attendanceSummaryCards"
                    :columns="4"
                />

                <ListingStats
                    :items="workingHoursCards"
                    :columns="3"
                />

                <ProgressSummary
                    title="Attendance rate"
                    :description="`Employee attendance for ${activePeriod.label}`"
                    :percentage="activePeriod.attendancePercentage"
                    :items="attendanceProgressItems"
                    percentage-color="text-green-600 dark:text-green-400"
                    bar-color="bg-green-500"
                />

                <section class="overflow-hidden rounded-xl border border-border bg-card">
                    <div class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold">
                                Employee attendance
                            </h3>

                            <p class="mt-1 text-sm text-muted-foreground">
                                Attendance details for {{ activePeriod.label }}
                            </p>
                        </div>

                        <SegmentedTabs
                            v-model="selectedStatus"
                            :items="statusTabs"
                            aria-label="Filter employees by attendance status"
                            variant="primary"
                            size="sm"
                        />
                    </div>

                    <div
                        v-if="filteredEmployees.length"
                        class="max-h-[440px] divide-y divide-border overflow-y-auto"
                    >
                        <div
                            v-for="employee in filteredEmployees"
                            :key="employee.id"
                            class="flex flex-col gap-3 p-4 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                                    {{ employee.name.charAt(0) }}
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{ employee.name }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ employee.employeeCode }}
                                        <span class="px-0.5">·</span>
                                        {{ employee.role }}
                                        <span class="px-0.5">·</span>
                                        {{ employee.shift }} shift
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-3 sm:justify-end">
                                <div class="min-w-20">
                                    <p class="text-xs text-muted-foreground">
                                        In / Out
                                    </p>

                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        <template v-if="employee.startAt">
                                            {{ employee.startAt }}
                                            <span class="text-muted-foreground">–</span>
                                            {{ employee.endAt || 'Active' }}
                                        </template>
                                        <template v-else>
                                            —
                                        </template>
                                    </p>
                                </div>

                                <div class="min-w-20">
                                    <p class="text-xs text-muted-foreground">
                                        Effective
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ employee.effectiveWorkingFormatted }}
                                    </p>
                                </div>

                                <div class="min-w-20">
                                    <p class="text-xs text-muted-foreground">
                                        Break
                                    </p>
                                    <p class="mt-1 text-sm font-medium tabular-nums">
                                        {{ employee.breakFormatted }}
                                    </p>
                                </div>

                                <div class="min-w-20">
                                    <p class="text-xs text-muted-foreground">
                                        Undertime
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-medium tabular-nums"
                                        :class="employee.undertimeSeconds > 0
                                            ? 'text-amber-600 dark:text-amber-400'
                                            : ''"
                                    >
                                        {{ employee.undertimeFormatted }}
                                    </p>
                                </div>

                                <StatusBadge :variant="statusVariants[employee.status]">
                                    {{ statusLabels[employee.status] }}
                                </StatusBadge>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex min-h-40 items-center justify-center p-6 text-center"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                No employees found
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                No employees match the selected attendance filter.
                            </p>
                        </div>
                    </div>
                </section>
            </template>
        </template>
    </div>
</template>
