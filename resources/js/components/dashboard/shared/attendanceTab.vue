<script lang="ts" setup>
import {computed, ref} from 'vue';
import {Clock3, Coffee, TrendingUp, UserCheck, Users, UserX,} from '@lucide/vue';

import type {ListingStatItem} from '@/components/common/ListingStats.vue';
import ListingStats from '@/components/common/ListingStats.vue';

import type {SegmentedTabItem} from '@/components/common/SegmentedTabs.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

import type {ProgressSummaryItem} from '@/components/common/ProgressSummary.vue';
import ProgressSummary from '@/components/common/ProgressSummary.vue';
import SectionHeader from "@/components/common/SectionHeader.vue";
import StatusBadge, {type StatusVariant,} from '@/components/common/StatusBadge.vue';

type AttendanceStatus = 'present' | 'absent' | 'incomplete';
type AttendancePeriod = 'today' | 'yesterday';
type AttendanceFilter = 'all' | AttendanceStatus;

interface EmployeeAttendance {
    id: number;
    name: string;
    employeeCode: string;
    role: string;
    shift: 'Day' | 'Night';
    status: AttendanceStatus;
    startAt?: string;
    endAt?: string;
    workingSeconds: number;
    breakSeconds: number;
    effectiveWorkingSeconds: number;
    undertimeSeconds: number;
}

interface AttendancePeriodData {
    label: string;
    totalEmployees: number;
    present: number;
    absent: number;
    incomplete: number;
    averageWorkingSeconds: number;
    averageBreakSeconds: number;
    totalUndertimeSeconds: number;
    employees: EmployeeAttendance[];
}

const selectedPeriod = ref<AttendancePeriod>('today');
const selectedStatus = ref<AttendanceFilter>('all');

const periodTabs: readonly SegmentedTabItem[] = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
];

const statusTabs: readonly SegmentedTabItem[] = [
    { value: 'all', label: 'All' },
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'incomplete', label: 'Incomplete' },
];

const attendancePeriods: Record<AttendancePeriod, AttendancePeriodData> = {
    today: {
        label: '04 November 2026',
        totalEmployees: 24,
        present: 21,
        absent: 2,
        incomplete: 1,
        averageWorkingSeconds: 43200,
        averageBreakSeconds: 3600,
        totalUndertimeSeconds: 5400,
        employees: [
            {
                id: 1,
                name: 'Sanjay',
                employeeCode: 'EMP-003',
                role: 'Operator',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 2,
                name: 'Chetan',
                employeeCode: 'EMP-010',
                role: 'Operator',
                shift: 'Night',
                status: 'present',
                startAt: '08:00 PM',
                endAt: '08:00 AM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 3,
                name: 'Mahesh',
                employeeCode: 'EMP-004',
                role: 'Bandsaw',
                shift: 'Day',
                status: 'present',
                startAt: '08:10 AM',
                endAt: '08:00 PM',
                workingSeconds: 42600,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39000,
                undertimeSeconds: 4200,
            },
            {
                id: 4,
                name: 'Ramesh',
                employeeCode: 'EMP-005',
                role: 'Deburring & Packaging',
                shift: 'Day',
                status: 'incomplete',
                startAt: '08:15 AM',
                workingSeconds: 0,
                breakSeconds: 0,
                effectiveWorkingSeconds: 0,
                undertimeSeconds: 0,
            },
            {
                id: 5,
                name: 'Paresh',
                employeeCode: 'EMP-006',
                role: 'Operator',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 6,
                name: 'Nilesh',
                employeeCode: 'EMP-007',
                role: 'Operator',
                shift: 'Night',
                status: 'absent',
                workingSeconds: 0,
                breakSeconds: 0,
                effectiveWorkingSeconds: 0,
                undertimeSeconds: 0,
            },
            {
                id: 7,
                name: 'Vijay',
                employeeCode: 'EMP-008',
                role: 'Bandsaw',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 8,
                name: 'Kiran',
                employeeCode: 'EMP-009',
                role: 'Operator',
                shift: 'Day',
                status: 'absent',
                workingSeconds: 0,
                breakSeconds: 0,
                effectiveWorkingSeconds: 0,
                undertimeSeconds: 0,
            },
        ],
    },

    yesterday: {
        label: '03 November 2026',
        totalEmployees: 24,
        present: 22,
        absent: 2,
        incomplete: 0,
        averageWorkingSeconds: 43800,
        averageBreakSeconds: 3600,
        totalUndertimeSeconds: 3600,
        employees: [
            {
                id: 1,
                name: 'Sanjay',
                employeeCode: 'EMP-003',
                role: 'Operator',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 2,
                name: 'Chetan',
                employeeCode: 'EMP-010',
                role: 'Operator',
                shift: 'Night',
                status: 'present',
                startAt: '08:00 PM',
                endAt: '08:00 AM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 3600,
            },
            {
                id: 3,
                name: 'Mahesh',
                employeeCode: 'EMP-004',
                role: 'Bandsaw',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 0,
            },
            {
                id: 4,
                name: 'Ramesh',
                employeeCode: 'EMP-005',
                role: 'Deburring & Packaging',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 0,
            },
            {
                id: 5,
                name: 'Paresh',
                employeeCode: 'EMP-006',
                role: 'Operator',
                shift: 'Day',
                status: 'present',
                startAt: '08:00 AM',
                endAt: '08:00 PM',
                workingSeconds: 43200,
                breakSeconds: 3600,
                effectiveWorkingSeconds: 39600,
                undertimeSeconds: 0,
            },
            {
                id: 6,
                name: 'Nilesh',
                employeeCode: 'EMP-007',
                role: 'Operator',
                shift: 'Night',
                status: 'absent',
                workingSeconds: 0,
                breakSeconds: 0,
                effectiveWorkingSeconds: 0,
                undertimeSeconds: 0,
            },
        ],
    },
};

const activePeriod = computed(
    () => attendancePeriods[selectedPeriod.value],
);

const filteredEmployees = computed(() => {
    if (selectedStatus.value === 'all') {
        return activePeriod.value.employees;
    }

    return activePeriod.value.employees.filter(
        employee => employee.status === selectedStatus.value,
    );
});

const attendancePercentage = computed(() => {
    if (!activePeriod.value.totalEmployees) {
        return 0;
    }

    return (
        (activePeriod.value.present / activePeriod.value.totalEmployees) *
        100
    );
});

const attendanceSummaryCards = computed<ListingStatItem[]>(() => [
    {
        label: 'Total employees',
        value: activePeriod.value.totalEmployees,
        description: 'Registered employees',
        icon: Users,
        variant: 'default',
    },
    {
        label: 'Present',
        value: activePeriod.value.present,
        description: `${attendancePercentage.value.toFixed(1)}% attendance`,
        icon: UserCheck,
        variant: 'success',
    },
    {
        label: 'Absent',
        value: activePeriod.value.absent,
        description: 'Employees not present',
        icon: UserX,
        variant: 'destructive',
    },
    {
        label: 'Incomplete',
        value: activePeriod.value.incomplete,
        description: 'Attendance not completed',
        icon: Clock3,
        variant: 'warning',
    },
]);

const workingHoursCards = computed<ListingStatItem[]>(() => [
    {
        label: 'Avg. working time',
        value: formatDuration(activePeriod.value.averageWorkingSeconds),
        description: 'Total working duration',
        icon: Clock3,
        variant: 'primary',
    },
    {
        label: 'Avg. break time',
        value: formatDuration(activePeriod.value.averageBreakSeconds),
        description: 'Break duration',
        icon: Coffee,
        variant: 'default',
    },
    {
        label: 'Total undertime',
        value: formatDuration(activePeriod.value.totalUndertimeSeconds),
        description: 'Combined employee undertime',
        icon: TrendingUp,
        variant: 'warning',
    },
]);

const attendanceProgressItems = computed<ProgressSummaryItem[]>(() => [
    {
        key: 'present',
        label: 'Present',
        value: activePeriod.value.present,
        color: 'bg-green-500',
    },
    {
        key: 'absent',
        label: 'Absent',
        value: activePeriod.value.absent,
        color: 'bg-red-500',
    },
    {
        key: 'incomplete',
        label: 'Incomplete',
        value: activePeriod.value.incomplete,
        color: 'bg-amber-500',
    },
]);

const statusConfig: Record<
    AttendanceStatus,
    { label: string; class: string }
> = {
    present: {
        label: 'Present',
        class: 'bg-green-500/10 text-green-600 dark:text-green-400',
    },
    absent: {
        label: 'Absent',
        class: 'bg-red-500/10 text-red-600 dark:text-red-400',
    },
    incomplete: {
        label: 'Incomplete',
        class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
};

const statusVariants: Record<AttendanceStatus, StatusVariant> = {
    present: 'success',
    absent: 'destructive',
    incomplete: 'warning',
};

function formatDuration(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header and date selector -->
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

        <!-- Attendance summary -->
        <ListingStats
            :items="attendanceSummaryCards"
            :columns="4"
        />

        <!-- Working hours overview -->
        <ListingStats
            :items="workingHoursCards"
            :columns="3"
        />

        <!-- Attendance progress -->
        <ProgressSummary
            title="Attendance rate"
            :description="`Employee attendance for ${activePeriod.label}`"
            :percentage="attendancePercentage"
            :items="attendanceProgressItems"
            percentage-color="text-green-600 dark:text-green-400"
            bar-color="bg-green-500"
        />

        <!-- Employee attendance list -->
        <section class="overflow-hidden rounded-xl border border-border bg-card">
            <!-- Header and status filters -->
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

            <!-- Employee list -->
            <div
                v-if="filteredEmployees.length"
                class="max-h-[440px] divide-y divide-border overflow-y-auto"
            >
                <div
                    v-for="employee in filteredEmployees"
                    :key="employee.id"
                    class="flex flex-col gap-3 p-4 transition-colors hover:bg-muted/30 sm:flex-row sm:items-center sm:justify-between"
                >
                    <!-- Employee identity -->
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

                    <!-- Attendance details -->
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
                                {{ formatDuration(employee.effectiveWorkingSeconds) }}
                            </p>
                        </div>

                        <div class="min-w-20">
                            <p class="text-xs text-muted-foreground">
                                Break
                            </p>

                            <p class="mt-1 text-sm font-medium tabular-nums">
                                {{ formatDuration(employee.breakSeconds) }}
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
                                {{ formatDuration(employee.undertimeSeconds) }}
                            </p>
                        </div>

                        <StatusBadge :variant="statusVariants[employee.status]">
                            {{ statusConfig[employee.status].label }}
                        </StatusBadge>
                        <span
                            class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusConfig[employee.status].class"
                        >
                            {{ statusConfig[employee.status].label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-else
                class="flex min-h-40 items-center justify-center p-6 text-center"
            >
                <div>
                    <p class="text-sm font-medium">
                        No attendance records found
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        No employees match the selected filter.
                    </p>
                </div>
            </div>
        </section>

        <p class="text-center text-xs text-muted-foreground">
            Dashboard currently displays static demonstration data.
        </p>
    </div>
</template>
