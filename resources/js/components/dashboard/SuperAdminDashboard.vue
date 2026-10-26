<script lang="ts" setup>
import {computed, ref} from 'vue';
import {
    Activity,
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    CalendarDays,
    ChevronDown,
    CircleDollarSign,
    Clock3,
    Cog,
    Download,
    Factory,
    Gauge,
    MoreHorizontal,
    Package,
    PackageCheck,
    Search,
    Settings2,
    ShieldCheck,
    Users,
    Wallet,
    Wrench,
    XCircle,
} from '@lucide/vue';

const selectedPeriod = ref('This month');
const periods = ['Today', 'This week', 'This month'];

const stats = [
    {
        title: "Last month's salary paid",
        value: '₹ 2,84,650',
        description: 'September 2026',
        change: '4.8%',
        direction: 'down',
        icon: Wallet,
        iconClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        changeLabel: 'vs. previous month',
    },
    {
        title: "Today's attendance",
        value: '24 / 28',
        description: 'Employees present',
        change: '85.7%',
        direction: 'up',
        icon: Users,
        iconClass: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        changeLabel: 'attendance rate',
    },
    {
        title: 'Effective attendance hours',
        value: '1,248.5 hrs',
        description: 'For selected period',
        change: '6.2%',
        direction: 'up',
        icon: Clock3,
        iconClass: 'bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
        changeLabel: 'vs. previous period',
    },
    {
        title: 'Operator time utilization',
        value: '72.4%',
        description: 'Production time / effective hours',
        change: '3.1%',
        direction: 'up',
        icon: Gauge,
        iconClass: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
        changeLabel: 'vs. previous period',
    },
    {
        title: 'Total production',
        value: '8,462 pcs',
        description: 'Good + rejected parts',
        change: '8.4%',
        direction: 'up',
        icon: Factory,
        iconClass: 'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
        changeLabel: 'vs. previous period',
    },
    {
        title: 'Good production',
        value: '8,271 pcs',
        description: 'Accepted parts produced',
        change: '9.2%',
        direction: 'up',
        icon: PackageCheck,
        iconClass: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        changeLabel: 'vs. previous period',
    },
    {
        title: 'Rejected production',
        value: '191 pcs',
        description: '2.26% rejection rate',
        change: '0.6%',
        direction: 'down',
        icon: XCircle,
        iconClass: 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
        changeLabel: 'rejection rate improved',
    },
    {
        title: 'Machines in operation',
        value: '5 / 6',
        description: 'One machine needs attention',
        change: '83.3%',
        direction: 'neutral',
        icon: Cog,
        iconClass: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        changeLabel: 'operational status',
    },
];

const operators = [
    { name: 'Sanjay Patel', code: 'EMP-003', shift: 'Day', hours: '184.5 hrs', production: '1,284 pcs', efficiency: 82, rejected: 12, status: 'On track' },
    { name: 'Chetan Parmar', code: 'EMP-010', shift: 'Night', hours: '176.0 hrs', production: '1,146 pcs', efficiency: 76, rejected: 8, status: 'On track' },
    { name: 'Rakesh Thakor', code: 'EMP-004', shift: 'Day', hours: '192.0 hrs', production: '1,092 pcs', efficiency: 69, rejected: 24, status: 'Needs review' },
    { name: 'Mahesh Solanki', code: 'EMP-008', shift: 'Night', hours: '168.0 hrs', production: '986 pcs', efficiency: 64, rejected: 31, status: 'Needs review' },
    { name: 'Nilesh Chauhan', code: 'EMP-012', shift: 'Day', hours: '180.0 hrs', production: '1,034 pcs', efficiency: 78, rejected: 9, status: 'On track' },
];

const machines = [
    { name: 'VMC-1', model: 'PX-30', status: 'Running', production: '1,482 pcs', efficiency: 84 },
    { name: 'VMC-2', model: 'PX-30', status: 'Running', production: '1,368 pcs', efficiency: 79 },
    { name: 'VMC-3', model: 'PX-30', status: 'Running', production: '1,294 pcs', efficiency: 75 },
    { name: 'VMC-4', model: 'AKIRA-2', status: 'Maintenance', production: '—', efficiency: 0 },
    { name: 'VMC-5', model: 'AKIRA-2', status: 'Running', production: '1,612 pcs', efficiency: 88 },
    { name: 'VMC-6', model: 'AKIRA-2', status: 'Running', production: '1,305 pcs', efficiency: 77 },
];

const alerts = [
    { title: 'Attendance missing', description: '4 employees have no attendance entry today.', type: 'warning', icon: AlertTriangle, time: 'Today, 9:15 AM' },
    { title: 'Raw material running low', description: 'MS section 40 × 40 × 3 mm is below the minimum stock level.', type: 'danger', icon: Package, time: 'Today, 8:42 AM' },
    { title: 'Machine maintenance', description: 'VMC-4 is marked for maintenance.', type: 'info', icon: Wrench, time: 'Today, 8:10 AM' },
];

const activities = [
    { title: 'Production log submitted', description: 'Sanjay Patel · VMC-1 · 120 pcs', time: '10:42 AM', icon: Factory },
    { title: 'Attendance updated', description: 'Chetan Parmar · Night shift', time: '9:18 AM', icon: Users },
    { title: 'Raw material transaction', description: 'MS section 50 × 50 × 5 mm · Issued', time: '8:55 AM', icon: Package },
    { title: 'Work log updated', description: 'Rakesh Thakor · VMC-3', time: '8:34 AM', icon: Activity },
];

const trendBars = [
    { day: 'Mon', good: 72, rejected: 10 },
    { day: 'Tue', good: 84, rejected: 14 },
    { day: 'Wed', good: 66, rejected: 8 },
    { day: 'Thu', good: 92, rejected: 13 },
    { day: 'Fri', good: 80, rejected: 9 },
    { day: 'Sat', good: 58, rejected: 16 },
    { day: 'Sun', good: 38, rejected: 4 },
];

const maxTrend = computed(() => Math.max(...trendBars.map((bar) => bar.good)));
</script>

<template>
    <div class="min-h-full space-y-6 bg-background p-4 text-foreground sm:p-6 lg:p-8">
        <!-- Page heading -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Home</span>
                    <span>/</span>
                    <span class="text-foreground">Dashboard</span>
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Factory dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">Here’s what’s happening at Krishna Engineers.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border bg-card p-1">
                    <button
                        v-for="period in periods"
                        :key="period"
                        type="button"
                        class="rounded-md px-3 py-1.5 text-sm transition-colors"
                        :class="selectedPeriod === period ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                        @click="selectedPeriod = period"
                    >
                        {{ period }}
                    </button>
                </div>
                <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border bg-card px-3 text-sm font-medium shadow-sm hover:bg-muted">
                    <CalendarDays class="size-4" />
                    Oct 1 – Oct 26, 2026
                    <ChevronDown class="size-4 text-muted-foreground" />
                </button>
                <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border bg-card px-3 text-sm font-medium shadow-sm hover:bg-muted">
                    <Download class="size-4" />
                    Export
                </button>
            </div>
        </div>

        <!-- Overview cards -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard overview">
            <article v-for="stat in stats" :key="stat.title" class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-muted-foreground">{{ stat.title }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight">{{ stat.value }}</p>
                    </div>
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="stat.iconClass">
                        <component :is="stat.icon" class="size-5" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">{{ stat.description }}</p>
                <div class="mt-4 flex items-center gap-1.5 text-xs">
                    <template v-if="stat.direction === 'up'">
                        <ArrowUpRight class="size-4 text-emerald-600" />
                        <span class="font-medium text-emerald-600">{{ stat.change }}</span>
                    </template>
                    <template v-else-if="stat.direction === 'down'">
                        <ArrowDownRight class="size-4 text-emerald-600" />
                        <span class="font-medium text-emerald-600">{{ stat.change }}</span>
                    </template>
                    <span v-else class="font-medium text-muted-foreground">{{ stat.change }}</span>
                    <span class="text-muted-foreground">{{ stat.changeLabel }}</span>
                </div>
            </article>
        </section>

        <!-- Trends and quick insights -->
        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-xl border bg-card p-5 shadow-sm xl:col-span-2">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-semibold">Production overview</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Good parts and rejected parts over the last 7 days</p>
                    </div>
                    <button type="button" class="inline-flex h-8 items-center gap-2 self-start rounded-md border px-2.5 text-xs font-medium hover:bg-muted">
                        This week <ChevronDown class="size-3.5" />
                    </button>
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-sm bg-primary"></span>Good production</span>
                    <span class="inline-flex items-center gap-2"><span class="size-2.5 rounded-sm bg-rose-400"></span>Rejected parts</span>
                </div>
                <div class="mt-5 grid h-56 grid-cols-7 gap-3 border-b border-l px-2 pb-0 pt-3 sm:gap-5">
                    <div v-for="bar in trendBars" :key="bar.day" class="flex min-w-0 flex-col items-center justify-end gap-2">
                        <div class="flex h-full w-full items-end justify-center gap-1">
                            <div
                                class="w-3 rounded-t-sm bg-primary/90 sm:w-5"
                                :style="{ height: `${(bar.good / maxTrend) * 100}%` }"
                                :title="`${bar.day}: good production`"
                            ></div>
                            <div
                                class="w-2 rounded-t-sm bg-rose-400 sm:w-3"
                                :style="{ height: `${Math.max(5, (bar.rejected / maxTrend) * 100)}%` }"
                                :title="`${bar.day}: rejected parts`"
                            ></div>
                        </div>
                        <span class="pb-2 text-xs text-muted-foreground">{{ bar.day }}</span>
                    </div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-muted/60 p-3">
                        <p class="text-xs text-muted-foreground">Good parts this week</p>
                        <p class="mt-1 text-lg font-semibold">2,846 <span class="text-xs font-normal text-emerald-600">+8.2%</span></p>
                    </div>
                    <div class="rounded-lg bg-muted/60 p-3">
                        <p class="text-xs text-muted-foreground">Rejected this week</p>
                        <p class="mt-1 text-lg font-semibold">74 <span class="text-xs font-normal text-rose-600">2.5%</span></p>
                    </div>
                    <div class="rounded-lg bg-muted/60 p-3">
                        <p class="text-xs text-muted-foreground">Production time ratio</p>
                        <p class="mt-1 text-lg font-semibold">72.4% <span class="text-xs font-normal text-emerald-600">+3.1%</span></p>
                    </div>
                </div>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Working hours</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Attendance vs. production time</p>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-muted-foreground hover:bg-muted" aria-label="More working hours options"><MoreHorizontal class="size-5" /></button>
                </div>
                <div class="mt-6 space-y-5">
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Effective attendance</span>
                            <span class="font-medium">1,248.5 hrs</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-muted"><div class="h-full w-full rounded-full bg-slate-400"></div></div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Production time</span>
                            <span class="font-medium">904.0 hrs</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-muted"><div class="h-full w-[72.4%] rounded-full bg-primary"></div></div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Remaining time</span>
                            <span class="font-medium">344.5 hrs</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-muted"><div class="h-full w-[27.6%] rounded-full bg-amber-400"></div></div>
                    </div>
                </div>
                <div class="mt-6 rounded-lg border border-dashed p-3">
                    <div class="flex items-start gap-2.5">
                        <Clock3 class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                        <p class="text-xs leading-5 text-muted-foreground">Remaining time can include setup, loading, inspection, waiting, breaks not deducted, or unrecorded work. It should not automatically be treated as idle time.</p>
                    </div>
                </div>
            </article>
        </section>

        <!-- Operator performance -->
        <section class="rounded-xl border bg-card shadow-sm">
            <div class="flex flex-col gap-3 border-b p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold">Operator performance</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Production and efficiency for the selected period</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <input class="h-9 w-full rounded-lg border bg-background pl-9 pr-3 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring sm:w-52" placeholder="Search operator..." />
                    </div>
                    <button type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-sm font-medium hover:bg-muted"><Settings2 class="size-4" /> Filters</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="px-5 py-3 font-medium">Operator</th>
                        <th class="px-4 py-3 font-medium">Shift</th>
                        <th class="px-4 py-3 font-medium">Attendance</th>
                        <th class="px-4 py-3 font-medium">Production</th>
                        <th class="px-4 py-3 font-medium">Efficiency</th>
                        <th class="px-4 py-3 font-medium">Rejected</th>
                        <th class="px-5 py-3 text-right font-medium">Status</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="operator in operators" :key="operator.code" class="hover:bg-muted/30">
                        <td class="px-5 py-3.5">
                            <div class="font-medium">{{ operator.name }}</div>
                            <div class="mt-0.5 text-xs text-muted-foreground">{{ operator.code }}</div>
                        </td>
                        <td class="px-4 py-3.5 text-muted-foreground">{{ operator.shift }}</td>
                        <td class="px-4 py-3.5">{{ operator.hours }}</td>
                        <td class="px-4 py-3.5 font-medium">{{ operator.production }}</td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full" :class="operator.efficiency >= 75 ? 'bg-emerald-500' : 'bg-amber-500'" :style="{ width: `${operator.efficiency}%` }"></div></div>
                                <span class="font-medium tabular-nums">{{ operator.efficiency }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5" :class="operator.rejected >= 20 ? 'font-medium text-rose-600' : ''">{{ operator.rejected }}</td>
                        <td class="px-5 py-3.5 text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="operator.status === 'On track' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'">
                                    <span class="size-1.5 rounded-full" :class="operator.status === 'On track' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                    {{ operator.status }}
                                </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-2 border-t px-5 py-3 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                <span>Showing 5 of 18 operators</span>
                <button type="button" class="inline-flex items-center gap-1 self-start font-medium text-foreground hover:underline">View all operators <ArrowUpRight class="size-3.5" /></button>
            </div>
        </section>

        <!-- Machine status and payroll -->
        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-xl border bg-card shadow-sm xl:col-span-2">
                <div class="flex items-start justify-between gap-3 border-b p-5">
                    <div>
                        <h2 class="font-semibold">Machine status</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Production summary across all six VMC machines</p>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-muted-foreground hover:bg-muted" aria-label="More machine status options"><MoreHorizontal class="size-5" /></button>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="machine in machines" :key="machine.name" class="rounded-lg border p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ machine.name }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ machine.model }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[11px] font-medium" :class="machine.status === 'Running' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'">
                                <span class="size-1.5 rounded-full" :class="machine.status === 'Running' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                {{ machine.status }}
                            </span>
                        </div>
                        <div class="mt-5 flex items-end justify-between gap-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Production</p>
                                <p class="mt-1 text-lg font-semibold">{{ machine.production }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-muted-foreground">Time ratio</p>
                                <p class="mt-1 text-sm font-semibold">{{ machine.efficiency ? `${machine.efficiency}%` : '—' }}</p>
                            </div>
                        </div>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full" :class="machine.status === 'Running' ? 'bg-primary' : 'bg-amber-400'" :style="{ width: `${machine.efficiency}%` }"></div></div>
                    </div>
                </div>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">Payroll summary</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Salary overview</p>
                    </div>
                    <CircleDollarSign class="size-5 text-muted-foreground" />
                </div>
                <div class="mt-5 rounded-lg bg-muted/60 p-4">
                    <p class="text-sm text-muted-foreground">Last month's salary paid</p>
                    <p class="mt-1 text-2xl font-semibold">₹ 2,84,650</p>
                    <p class="mt-1 text-xs text-muted-foreground">September 2026</p>
                </div>
                <div class="mt-4 space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div><p class="text-sm font-medium">Current month calculated</p><p class="mt-0.5 text-xs text-muted-foreground">October 2026 estimate</p></div>
                        <p class="font-semibold">₹ 2,36,400</p>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <div><p class="text-sm font-medium">Payment pending</p><p class="mt-0.5 text-xs text-muted-foreground">Based on sample status</p></div>
                        <p class="font-semibold text-amber-600">₹ 18,500</p>
                    </div>
                </div>
                <button type="button" class="mt-5 inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg border text-sm font-medium hover:bg-muted">View payroll details <ArrowUpRight class="size-4" /></button>
                <p class="mt-3 text-xs leading-5 text-muted-foreground">Sample values only. “Paid” should be linked to actual payment records once payroll tracking is available.</p>
            </article>
        </section>

        <!-- Alerts and activity -->
        <section class="grid gap-4 xl:grid-cols-2">
            <article class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b p-5">
                    <div>
                        <h2 class="font-semibold">Needs attention</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Items that may need admin action</p>
                    </div>
                    <span class="inline-flex size-7 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">3</span>
                </div>
                <div class="divide-y">
                    <div v-for="alert in alerts" :key="alert.title" class="flex gap-3 p-4 sm:p-5">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg" :class="alert.type === 'danger' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : alert.type === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300'">
                            <component :is="alert.icon" class="size-4.5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><p class="text-sm font-medium">{{ alert.title }}</p><span class="text-xs text-muted-foreground">{{ alert.time }}</span></div>
                            <p class="mt-1 text-sm leading-5 text-muted-foreground">{{ alert.description }}</p>
                        </div>
                        <button type="button" class="self-center rounded-md px-2 py-1 text-xs font-medium text-foreground hover:bg-muted">Review</button>
                    </div>
                </div>
                <div class="border-t p-4"><button type="button" class="text-sm font-medium hover:underline">View all alerts <ArrowUpRight class="ml-1 inline size-3.5" /></button></div>
            </article>

            <article class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b p-5">
                    <div>
                        <h2 class="font-semibold">Recent activity</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Latest updates across the factory</p>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-muted-foreground hover:bg-muted" aria-label="More recent activity options"><MoreHorizontal class="size-5" /></button>
                </div>
                <div class="divide-y">
                    <div v-for="activity in activities" :key="activity.title + activity.time" class="flex gap-3 p-4 sm:p-5">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"><component :is="activity.icon" class="size-4" /></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ activity.title }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ activity.description }}</p>
                        </div>
                        <span class="shrink-0 pt-0.5 text-xs text-muted-foreground">{{ activity.time }}</span>
                    </div>
                </div>
                <div class="border-t p-4"><button type="button" class="text-sm font-medium hover:underline">View activity log <ArrowUpRight class="ml-1 inline size-3.5" /></button></div>
            </article>
        </section>

        <footer class="flex flex-col gap-2 border-t pt-4 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
            <p>Krishna Engineers · Super Admin dashboard</p>
            <p class="inline-flex items-center gap-1.5"><ShieldCheck class="size-3.5" /> Static client-preview data · Not connected to live records</p>
        </footer>
    </div>
</template>
