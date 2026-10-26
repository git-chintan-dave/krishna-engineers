<script lang="ts" setup>
import { computed, ref } from 'vue';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    History,
    Layers,
    Package,
    PackageCheck,
    Scissors,
    Search,
    Timer,
    TriangleAlert,
} from '@lucide/vue';

const selectedPeriod = ref('Today');
const periods = ['Today', 'This week', 'This month'];

const stats = [
    {
        title: 'Effective working hours',
        value: '08:10 hrs',
        description: 'After recorded breaks',
        icon: Clock3,
        iconClass:
            'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    },
    {
        title: 'Pieces cut',
        value: '245 pcs',
        description: 'Sample cutting output today',
        icon: Scissors,
        iconClass:
            'bg-cyan-50 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
    },
    {
        title: 'Material processed',
        value: '— kg',
        description: 'Shown when weight tracking is available',
        icon: PackageCheck,
        iconClass:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    {
        title: 'Stock alerts',
        value: '2 items',
        description: 'Read-only low-stock notices',
        icon: TriangleAlert,
        iconClass:
            'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    },
];

const cuttingActivity = [
    {
        time: '10:35 AM',
        section: '40 × 40 × 3 mm',
        material: 'MS Square Tube',
        quantity: 85,
        remarks: 'Batch KE-1026',
        status: 'Recorded',
    },
    {
        time: '09:20 AM',
        section: '50 × 25 × 2 mm',
        material: 'MS Rectangular Tube',
        quantity: 64,
        remarks: 'Batch KE-1024',
        status: 'Recorded',
    },
    {
        time: '08:15 AM',
        section: '25 × 25 × 2 mm',
        material: 'MS Square Tube',
        quantity: 96,
        remarks: 'Batch KE-1021',
        status: 'Recorded',
    },
];

const stockItems = [
    {
        name: 'MS Square Tube',
        section: '40 × 40 × 3 mm',
        available: '18 lengths',
        level: 'Low stock',
        tone: 'warning',
    },
    {
        name: 'MS Rectangular Tube',
        section: '50 × 25 × 2 mm',
        available: '42 lengths',
        level: 'In stock',
        tone: 'success',
    },
    {
        name: 'MS Square Tube',
        section: '25 × 25 × 2 mm',
        available: '12 lengths',
        level: 'Low stock',
        tone: 'warning',
    },
    {
        name: 'MS Round Bar',
        section: 'Ø 20 mm',
        available: '64 lengths',
        level: 'In stock',
        tone: 'success',
    },
];

const weeklyOutput = [
    { day: 'Mon', quantity: 198 },
    { day: 'Tue', quantity: 225 },
    { day: 'Wed', quantity: 184 },
    { day: 'Thu', quantity: 245 },
    { day: 'Fri', quantity: 216 },
    { day: 'Sat', quantity: 172 },
    { day: 'Sun', quantity: 0 },
];

const maxOutput = computed(() =>
    Math.max(...weeklyOutput.map((day) => day.quantity)),
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
                    Bandsaw dashboard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Your cutting activity, attendance, and raw material
                    overview.
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
            aria-label="Bandsaw dashboard overview"
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
                        <h2 class="font-semibold">Cutting output</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Pieces cut over the last 7 days
                        </p>
                    </div>
                    <span class="text-sm text-muted-foreground"
                        >{{ selectedPeriod }} overview</span
                    >
                </div>
                <div
                    class="mt-6 flex h-52 items-end gap-3 border-b pb-2 sm:gap-5"
                >
                    <div
                        v-for="day in weeklyOutput"
                        :key="day.day"
                        class="flex h-full flex-1 flex-col items-center justify-end gap-2"
                    >
                        <span class="text-xs text-muted-foreground">{{
                            day.quantity || '—'
                        }}</span>
                        <div
                            class="w-full max-w-10 rounded-t-md bg-primary/85 transition-all"
                            :style="{
                                height: `${day.quantity ? (day.quantity / maxOutput) * 82 : 0}%`,
                            }"
                        ></div>
                        <span class="text-xs text-muted-foreground">{{
                            day.day
                        }}</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-muted-foreground">
                    Sample values for the client preview. Connect this chart
                    only if cutting output is recorded.
                </p>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">My attendance</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Today's shift summary
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
                            <span class="font-semibold">08:10 hrs</span>
                        </div>
                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full w-[68%] rounded-full bg-primary"
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
                        <h2 class="font-semibold">Recent cutting activity</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Recent material preparation entries
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm text-muted-foreground"
                    >
                        <History class="size-4" /> Latest entries
                    </span>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="pr-4 pb-3 font-medium">Time</th>
                                <th class="pr-4 pb-3 font-medium">Material</th>
                                <th class="pr-4 pb-3 font-medium">
                                    Section size
                                </th>
                                <th class="pr-4 pb-3 text-right font-medium">
                                    Pieces
                                </th>
                                <th class="pr-4 pb-3 font-medium">Reference</th>
                                <th class="pb-3 text-right font-medium">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="entry in cuttingActivity"
                                :key="`${entry.time}-${entry.remarks}`"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ entry.time }}
                                </td>
                                <td class="py-3 pr-4 font-medium">
                                    {{ entry.material }}
                                </td>
                                <td class="py-3 pr-4">{{ entry.section }}</td>
                                <td class="py-3 pr-4 text-right tabular-nums">
                                    {{ entry.quantity }}
                                </td>
                                <td class="py-3 pr-4 text-muted-foreground">
                                    {{ entry.remarks }}
                                </td>
                                <td class="py-3 text-right">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        {{ entry.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-xs text-muted-foreground">
                    Cutting rows are illustrative placeholders; retain this
                    section only if your workflow records these entries.
                </p>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <Layers class="size-5 text-muted-foreground" />
                    <h2 class="font-semibold">Raw material stock</h2>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                    Read-only stock overview
                </p>
                <div class="mt-5 space-y-4">
                    <div
                        v-for="item in stockItems"
                        :key="`${item.name}-${item.section}`"
                        class="border-b pb-4 last:border-0 last:pb-0"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium">
                                    {{ item.name }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ item.section }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2 py-1 text-xs font-medium"
                                :class="
                                    item.tone === 'warning'
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                        : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                "
                            >
                                {{ item.level }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Available:
                            <span class="font-medium text-foreground">{{
                                item.available
                            }}</span>
                        </p>
                    </div>
                </div>
                <div
                    class="mt-3 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground"
                >
                    <Package class="mb-1 size-4" />
                    Stock transactions are view-only for this role.
                </div>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <Search class="size-5 text-muted-foreground" />
                    <h2 class="font-semibold">Stock reminders</h2>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-start gap-3 rounded-lg border p-3">
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0 text-amber-600"
                        />
                        <div>
                            <p class="text-sm font-medium">
                                MS Square Tube · 40 × 40 × 3 mm
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Sample alert: stock is below the configured
                                minimum.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg border p-3">
                        <TriangleAlert
                            class="mt-0.5 size-4 shrink-0 text-amber-600"
                        />
                        <div>
                            <p class="text-sm font-medium">
                                MS Square Tube · 25 × 25 × 2 mm
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Sample alert: check availability before
                                preparing material.
                            </p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <History class="size-5 text-muted-foreground" />
                    <h2 class="font-semibold">Quick information</h2>
                </div>
                <div class="mt-4 space-y-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Clock3 class="size-4" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">
                                Attendance and breaks
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Review your own shift, breaks, and effective
                                hours.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Scissors class="size-4" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">Cutting history</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                See your latest entries if cutting activity is
                                recorded in the system.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Package class="size-4" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">
                                Material transactions
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                You can view relevant stock information but
                                cannot create stock transactions.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <p class="text-xs text-muted-foreground">
            Static client-preview dashboard. All quantities, stock levels,
            times, and cutting entries are sample data and are not connected to
            live records.
        </p>
    </div>
</template>
