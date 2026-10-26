<script lang="ts" setup>
import { computed, ref } from 'vue';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Gauge,
    History,
    PackageCheck,
    Plus,
    Timer,
    TrendingUp,
    XCircle,
} from '@lucide/vue';

const selectedPeriod = ref('Today');
const periods = ['Today', 'This week', 'This month'];

const stats = [
    {
        title: 'Effective working hours',
        value: '08:15 hrs',
        description: 'After recorded breaks',
        icon: Clock3,
        iconClass:
            'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    },
    {
        title: 'Production time ratio',
        value: '72.4%',
        description: 'Production time / effective time',
        icon: Gauge,
        iconClass:
            'bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    },
    {
        title: 'Good parts',
        value: '186 pcs',
        description: 'Accepted quantity today',
        icon: PackageCheck,
        iconClass:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    {
        title: 'Rejected parts',
        value: '4 pcs',
        description: '2.1% of recorded production',
        icon: XCircle,
        iconClass:
            'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    },
];

const workLogs = [
    {
        time: '10:42 AM',
        machine: 'VMC-1',
        part: 'Housing Bracket',
        setup: 'HS-104',
        cycleTime: '00:02:40',
        quantity: 60,
        rejected: 1,
        status: 'Completed',
    },
    {
        time: '09:15 AM',
        machine: 'VMC-3',
        part: 'Mounting Plate',
        setup: 'MP-208',
        cycleTime: '00:03:10',
        quantity: 48,
        rejected: 2,
        status: 'Completed',
    },
    {
        time: '08:05 AM',
        machine: 'VMC-1',
        part: 'Housing Bracket',
        setup: 'HS-104',
        cycleTime: '00:02:35',
        quantity: 82,
        rejected: 1,
        status: 'Completed',
    },
];

const dailyTrend = [
    { day: 'Mon', good: 72, rejected: 5 },
    { day: 'Tue', good: 84, rejected: 7 },
    { day: 'Wed', good: 66, rejected: 3 },
    { day: 'Thu', good: 92, rejected: 6 },
    { day: 'Fri', good: 80, rejected: 4 },
    { day: 'Sat', good: 58, rejected: 3 },
    { day: 'Sun', good: 38, rejected: 2 },
];

const maxProduction = computed(() =>
    Math.max(...dailyTrend.map((day) => day.good)),
);
</script>

<template>
    <div
        class="min-h-full space-y-6 bg-background p-4 text-foreground sm:p-6 lg:p-8"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <span>Home</span><span>/</span
                    ><span class="text-foreground">Dashboard</span>
                </div>
                <h1
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    My dashboard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Your attendance and production at a glance.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border bg-card p-1">
                    <button
                        v-for="period in periods"
                        :key="period"
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm transition-colors"
                        :class="
                            selectedPeriod === period
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="selectedPeriod = period"
                    >
                        {{ period }}
                    </button>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 items-center gap-2 rounded-lg border bg-card px-3 text-sm font-medium shadow-sm hover:bg-muted"
                >
                    <CalendarDays class="size-4" />
                    Oct 26, 2026
                </button>
            </div>
        </div>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="My dashboard overview"
        >
            <article
                v-for="stat in stats"
                :key="stat.title"
                class="rounded-xl border bg-card p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight">
                            {{ stat.value }}
                        </p>
                    </div>
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                        :class="stat.iconClass"
                    >
                        <component :is="stat.icon" class="size-5" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ stat.description }}
                </p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article
                class="rounded-xl border bg-card p-5 shadow-sm xl:col-span-2"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h2 class="font-semibold">My production trend</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Good quantity recorded over the last 7 days
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600"
                    >
                        <TrendingUp class="size-4" /> Weekly overview
                    </span>
                </div>
                <div
                    class="mt-6 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-2"
                        ><span class="size-2.5 rounded-sm bg-primary"></span
                        >Good parts</span
                    >
                    <span class="inline-flex items-center gap-2"
                        ><span class="size-2.5 rounded-sm bg-rose-400"></span
                        >Rejected parts</span
                    >
                </div>
                <div
                    class="mt-5 grid h-52 grid-cols-7 items-end gap-3 border-b pb-2 sm:gap-5"
                >
                    <div
                        v-for="day in dailyTrend"
                        :key="day.day"
                        class="flex h-full flex-col items-center justify-end gap-2"
                    >
                        <div
                            class="flex h-full w-full items-end justify-center gap-1"
                        >
                            <div
                                class="w-3 rounded-t bg-primary sm:w-5"
                                :style="{
                                    height: `${(day.good / maxProduction) * 100}%`,
                                }"
                                :title="`${day.good} good parts`"
                            ></div>
                            <div
                                class="w-2 rounded-t bg-rose-400 sm:w-3"
                                :style="{
                                    height: `${Math.max((day.rejected / maxProduction) * 100, 3)}%`,
                                }"
                                :title="`${day.rejected} rejected parts`"
                            ></div>
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            day.day
                        }}</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Sample figures for presentation. Production values will be
                    connected to your work logs later.
                </p>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Today's shift</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Attendance summary
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                        >Present</span
                    >
                </div>
                <div class="mt-6 space-y-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Clock3 class="size-4" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">Shift</p>
                            <p class="mt-0.5 font-medium">Day shift</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <CalendarDays class="size-4" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">
                                Check-in
                            </p>
                            <p class="mt-0.5 font-medium">08:43 AM</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Timer class="size-4" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">
                                Break time recorded
                            </p>
                            <p class="mt-0.5 font-medium">00:45 hrs</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-muted/60 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Effective hours</span
                            >
                            <span class="font-semibold">08:15 hrs</span>
                        </div>
                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full w-[69%] rounded-full bg-primary"
                            ></div>
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            Illustrative progress toward a 12-hour shift.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium hover:bg-muted"
                >
                    View my attendance <ArrowRight class="size-4" />
                </button>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article
                class="rounded-xl border bg-card p-5 shadow-sm xl:col-span-2"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h2 class="font-semibold">Recent work logs</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Your latest production entries
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                    >
                        <Plus class="size-4" /> Add work log
                    </button>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="pr-4 pb-3 font-medium">Time</th>
                                <th class="pr-4 pb-3 font-medium">Machine</th>
                                <th class="pr-4 pb-3 font-medium">
                                    Part / Setup
                                </th>
                                <th class="pr-4 pb-3 font-medium">
                                    Cycle time
                                </th>
                                <th class="pr-4 pb-3 text-right font-medium">
                                    Good qty
                                </th>
                                <th class="pr-4 pb-3 text-right font-medium">
                                    Rejected
                                </th>
                                <th class="pb-3 text-right font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="log in workLogs"
                                :key="`${log.time}-${log.setup}`"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ log.time }}
                                </td>
                                <td class="py-3 pr-4 font-medium">
                                    {{ log.machine }}
                                </td>
                                <td class="py-3 pr-4">
                                    <p class="font-medium">{{ log.part }}</p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ log.setup }}
                                    </p>
                                </td>
                                <td class="py-3 pr-4 tabular-nums">
                                    {{ log.cycleTime }}
                                </td>
                                <td class="py-3 pr-4 text-right tabular-nums">
                                    {{ log.quantity }}
                                </td>
                                <td class="py-3 pr-4 text-right tabular-nums">
                                    {{ log.rejected }}
                                </td>
                                <td class="py-3 text-right">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        {{ log.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                >
                    View all my work logs <ArrowRight class="size-4" />
                </button>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <History class="size-5 text-muted-foreground" />
                    <h2 class="font-semibold">Performance snapshot</h2>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Selected period: {{ selectedPeriod }}
                </p>
                <div class="mt-6 space-y-5">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Production time ratio</span
                            >
                            <span class="font-semibold">72.4%</span>
                        </div>
                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full w-[72.4%] rounded-full bg-primary"
                            ></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Rejection rate</span
                            >
                            <span class="font-semibold">2.1%</span>
                        </div>
                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full w-[2.1%] min-w-1 rounded-full bg-rose-400"
                            ></div>
                        </div>
                    </div>
                    <div class="rounded-lg border p-3">
                        <div
                            class="flex items-center gap-2 text-sm font-medium"
                        >
                            <ArrowUpRight class="size-4 text-emerald-600" />
                            Output summary
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            186 good parts and 4 rejected parts recorded today.
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <p class="text-xs text-muted-foreground">
            Static client-preview dashboard. All names, times, quantities, and
            performance values are sample data and are not connected to live
            records.
        </p>
    </div>
</template>
