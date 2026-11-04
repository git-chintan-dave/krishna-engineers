<script lang="ts" setup>
import {computed, ref} from 'vue';
import {
    Activity,
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    Boxes,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Factory,
    IndianRupee,
    Package,
    Users,
    Wallet,
    Wrench,
    XCircle,
} from '@lucide/vue';

type Tab = 'production' | 'attendance' | 'salary' | 'stocks';
type Period = 'today' | 'week' | 'month';
type StockCategory = 'raw_material' | 'tool';
type StockAlertFilter = 'all' | 'low' | 'out';

const activeTab = ref<Tab>('production');
const period = ref<Period>('today');
const stockCategory = ref<StockCategory>('raw_material');
const stockAlertFilter = ref<StockAlertFilter>('all');

const tabs = [
    { value: 'production', label: 'Production', icon: Factory },
    { value: 'attendance', label: 'Attendance', icon: Users },
    { value: 'salary', label: 'Salary', icon: Wallet },
    { value: 'stocks', label: 'Stocks', icon: Boxes },
] as const;

const periods = [
    { label: 'Today', value: 'today' },
    { label: 'This week', value: 'week' },
    { label: 'This month', value: 'month' },
] as const;

const stockCategories = [
    { value: 'raw_material', label: 'Raw materials', icon: Boxes },
    { value: 'tool', label: 'Tools', icon: Wrench },
] as const;

const stockAlertFilters = [
    { value: 'all', label: 'All alerts' },
    { value: 'low', label: 'Low stock' },
    { value: 'out', label: 'Out of stock' },
] as const;

const productionData = {
    today: {
        cards: [
            { label: 'Total pieces', value: '1,248', description: 'Pieces produced today', icon: Package, trend: '+12.5%', up: true },
            { label: 'Good pieces', value: '1,210', description: '96.96% production yield', icon: CheckCircle2, trend: '+8.2%', up: true },
            { label: 'Rejected pieces', value: '38', description: '3.04% rejection rate', icon: XCircle, trend: '-2.1%', up: false },
            { label: 'Production time', value: '86h 30m', description: 'Actual cycle time', icon: Clock3 },
        ],
        trend: [
            { label: '08 AM', good: 120, rejected: 5 },
            { label: '10 AM', good: 185, rejected: 8 },
            { label: '12 PM', good: 145, rejected: 4 },
            { label: '02 PM', good: 210, rejected: 7 },
            { label: '04 PM', good: 195, rejected: 6 },
            { label: '06 PM', good: 165, rejected: 5 },
            { label: '08 PM', good: 190, rejected: 3 },
        ],
        machines: [
            { name: 'VMC-1 · PX-30', pieces: 285, percentage: 95 },
            { name: 'VMC-2 · PX-30', pieces: 248, percentage: 83 },
            { name: 'VMC-3 · PX-30', pieces: 220, percentage: 73 },
            { name: 'VMC-4 · AKIRA-1', pieces: 195, percentage: 65 },
            { name: 'VMC-5 · AKIRA-2', pieces: 175, percentage: 58 },
            { name: 'VMC-6 · AKIRA-2', pieces: 125, percentage: 42 },
        ],
    },
    week: {
        cards: [
            { label: 'Total pieces', value: '7,845', description: 'Pieces produced this week', icon: Package, trend: '+9.8%', up: true },
            { label: 'Good pieces', value: '7,612', description: '97.03% production yield', icon: CheckCircle2, trend: '+6.4%', up: true },
            { label: 'Rejected pieces', value: '233', description: '2.97% rejection rate', icon: XCircle, trend: '-1.5%', up: false },
            { label: 'Production time', value: '512h 45m', description: 'Actual cycle time', icon: Clock3 },
        ],
        trend: [
            { label: 'Mon', good: 950, rejected: 32 },
            { label: 'Tue', good: 1120, rejected: 35 },
            { label: 'Wed', good: 1080, rejected: 29 },
            { label: 'Thu', good: 1250, rejected: 42 },
            { label: 'Fri', good: 1180, rejected: 38 },
            { label: 'Sat', good: 1050, rejected: 31 },
            { label: 'Sun', good: 982, rejected: 26 },
        ],
        machines: [
            { name: 'VMC-1 · PX-30', pieces: 1820, percentage: 95 },
            { name: 'VMC-2 · PX-30', pieces: 1580, percentage: 83 },
            { name: 'VMC-3 · PX-30', pieces: 1390, percentage: 73 },
            { name: 'VMC-4 · AKIRA-1', pieces: 1240, percentage: 65 },
            { name: 'VMC-5 · AKIRA-2', pieces: 1050, percentage: 55 },
            { name: 'VMC-6 · AKIRA-2', pieces: 765, percentage: 40 },
        ],
    },
    month: {
        cards: [
            { label: 'Total pieces', value: '32,480', description: 'Pieces produced this month', icon: Package, trend: '+15.2%', up: true },
            { label: 'Good pieces', value: '31,510', description: '97.01% production yield', icon: CheckCircle2, trend: '+11.4%', up: true },
            { label: 'Rejected pieces', value: '970', description: '2.99% rejection rate', icon: XCircle, trend: '-3.2%', up: false },
            { label: 'Production time', value: '2,140h 20m', description: 'Actual cycle time', icon: Clock3 },
        ],
        trend: [
            { label: 'Week 1', good: 7100, rejected: 220 },
            { label: 'Week 2', good: 7850, rejected: 235 },
            { label: 'Week 3', good: 8200, rejected: 250 },
            { label: 'Week 4', good: 8360, rejected: 265 },
        ],
        machines: [
            { name: 'VMC-1 · PX-30', pieces: 7540, percentage: 95 },
            { name: 'VMC-2 · PX-30', pieces: 6580, percentage: 83 },
            { name: 'VMC-3 · PX-30', pieces: 5780, percentage: 73 },
            { name: 'VMC-4 · AKIRA-1', pieces: 5140, percentage: 65 },
            { name: 'VMC-5 · AKIRA-2', pieces: 4370, percentage: 55 },
            { name: 'VMC-6 · AKIRA-2', pieces: 3670, percentage: 46 },
        ],
    },
};

const attendanceData = {
    today: {
        cards: [
            { label: 'Present', value: '28', description: 'Employees checked in', icon: CheckCircle2 },
            { label: 'Not recorded', value: '4', description: 'Attendance not recorded', icon: Users },
            { label: 'Open shifts', value: '3', description: 'Awaiting checkout', icon: Clock3 },
            { label: 'Attendance rate', value: '87.5%', description: '28 of 32 employees', icon: Activity },
        ],
        trend: [
            { label: 'Mon', present: 27, absent: 5 },
            { label: 'Tue', present: 29, absent: 3 },
            { label: 'Wed', present: 28, absent: 4 },
            { label: 'Thu', present: 30, absent: 2 },
            { label: 'Fri', present: 26, absent: 6 },
            { label: 'Sat', present: 28, absent: 4 },
            { label: 'Sun', present: 28, absent: 4 },
        ],
    },
    week: {
        cards: [
            { label: 'Average present', value: '27.8', description: 'Average employees per day', icon: CheckCircle2 },
            { label: 'Not recorded', value: '18', description: 'Missing attendance records', icon: Users },
            { label: 'Open shifts', value: '5', description: 'Currently awaiting checkout', icon: Clock3 },
            { label: 'Attendance rate', value: '86.9%', description: 'Average weekly attendance', icon: Activity },
        ],
        trend: [
            { label: 'Mon', present: 27, absent: 5 },
            { label: 'Tue', present: 29, absent: 3 },
            { label: 'Wed', present: 28, absent: 4 },
            { label: 'Thu', present: 30, absent: 2 },
            { label: 'Fri', present: 26, absent: 6 },
            { label: 'Sat', present: 28, absent: 4 },
            { label: 'Sun', present: 27, absent: 5 },
        ],
    },
    month: {
        cards: [
            { label: 'Average present', value: '27.5', description: 'Average employees per day', icon: CheckCircle2 },
            { label: 'Not recorded', value: '42', description: 'Missing attendance records', icon: Users },
            { label: 'Open shifts', value: '8', description: 'Currently awaiting checkout', icon: Clock3 },
            { label: 'Attendance rate', value: '85.9%', description: 'Average monthly attendance', icon: Activity },
        ],
        trend: [
            { label: 'Week 1', present: 27, absent: 5 },
            { label: 'Week 2', present: 29, absent: 3 },
            { label: 'Week 3', present: 26, absent: 6 },
            { label: 'Week 4', present: 28, absent: 4 },
        ],
    },
};

const salaryCards = [
    { label: 'Monthly salary estimate', value: '₹4,85,000', description: 'Estimated employee salary', icon: IndianRupee },
    { label: 'Paid amount', value: '₹3,25,000', description: 'Already paid this month', icon: CheckCircle2 },
    { label: 'Pending amount', value: '₹1,60,000', description: 'Awaiting payment', icon: Clock3 },
    { label: 'Employees paid', value: '24 / 32', description: '8 payments remaining', icon: Users },
];

const salaryTrend = [
    { label: 'Jun', paid: 380000, pending: 45000 },
    { label: 'Jul', paid: 395000, pending: 30000 },
    { label: 'Aug', paid: 410000, pending: 55000 },
    { label: 'Sep', paid: 420000, pending: 40000 },
    { label: 'Oct', paid: 390000, pending: 65000 },
    { label: 'Nov', paid: 325000, pending: 160000 },
];

// Static demonstration stock data.
// Low stock: quantity > 0 and < 10.
// Out of stock: quantity = 0.
// Healthy stock: quantity >= 10.
const stockItems = [
    { id: 1, name: 'MS Round Bar 25mm', category: 'raw_material', quantity: 8, unit: 'Kg' },
    { id: 2, name: 'Aluminium Rod 40mm', category: 'raw_material', quantity: 4, unit: 'Kg' },
    { id: 3, name: 'SS Sheet 2mm', category: 'raw_material', quantity: 0, unit: 'Sheets' },
    { id: 4, name: 'MS Plate 10mm', category: 'raw_material', quantity: 125, unit: 'Kg' },
    { id: 5, name: 'Copper Rod 20mm', category: 'raw_material', quantity: 45, unit: 'Kg' },
    { id: 6, name: 'MS Square Bar 30mm', category: 'raw_material', quantity: 0, unit: 'Kg' },
    { id: 7, name: 'SS Round Bar 20mm', category: 'raw_material', quantity: 75, unit: 'Kg' },
    { id: 8, name: 'Aluminium Sheet 3mm', category: 'raw_material', quantity: 6, unit: 'Sheets' },

    { id: 9, name: 'Carbide Insert CNMG', category: 'tool', quantity: 3, unit: 'Pcs' },
    { id: 10, name: 'End Mill 10mm', category: 'tool', quantity: 0, unit: 'Pcs' },
    { id: 11, name: 'Drill Bit 8mm', category: 'tool', quantity: 4, unit: 'Pcs' },
    { id: 12, name: 'Face Mill Cutter', category: 'tool', quantity: 18, unit: 'Pcs' },
    { id: 13, name: 'Tap M10', category: 'tool', quantity: 25, unit: 'Pcs' },
    { id: 14, name: 'Boring Bar', category: 'tool', quantity: 0, unit: 'Pcs' },
    { id: 15, name: 'Reamer 12mm', category: 'tool', quantity: 12, unit: 'Pcs' },
    { id: 16, name: 'Drill Bit 12mm', category: 'tool', quantity: 8, unit: 'Pcs' },
    { id: 17, name: 'Drill Bit 15mm', category: 'tool', quantity: 2, unit: 'Pcs' },
    { id: 18, name: 'Drill Bit 18mm', category: 'tool', quantity: 5, unit: 'Pcs' },
    { id: 19, name: 'Drill Bit 20mm', category: 'tool', quantity: 0, unit: 'Pcs' },
    { id: 20, name: 'Drill Bit 25mm', category: 'tool', quantity: 0, unit: 'Pcs' },
];

const currentData = computed(() =>
    activeTab.value === 'production'
        ? productionData[period.value]
        : attendanceData[period.value],
);

const selectedStockItems = computed(() =>
    stockItems.filter(item => item.category === stockCategory.value),
);

const stockSummary = computed(() => {
    const items = selectedStockItems.value;

    return {
        total: items.length,
        healthy: items.filter(item => item.quantity >= 10).length,
        low: items.filter(item => item.quantity > 0 && item.quantity < 10).length,
        out: items.filter(item => item.quantity === 0).length,
    };
});

const stockSummaryCards = computed(() => [
    {
        label: 'Total items',
        value: stockSummary.value.total.toString(),
        description: 'Items in this category',
        icon: Boxes,
        tone: 'default',
    },
    {
        label: 'Healthy stock',
        value: stockSummary.value.healthy.toString(),
        description: '10 or more available',
        icon: CheckCircle2,
        tone: 'success',
    },
    {
        label: 'Low stock',
        value: stockSummary.value.low.toString(),
        description: 'Between 1 and 9 available',
        icon: AlertTriangle,
        tone: 'warning',
    },
    {
        label: 'Out of stock',
        value: stockSummary.value.out.toString(),
        description: 'No stock available',
        icon: XCircle,
        tone: 'danger',
    },
]);

const stockStatusDistribution = computed(() => {
    const summary = stockSummary.value;
    const total = summary.total || 1;

    return [
        {
            key: 'healthy',
            label: 'Healthy stock',
            count: summary.healthy,
            percentage: (summary.healthy / total) * 100,
            color: 'bg-emerald-500',
            textColor: 'text-emerald-600 dark:text-emerald-400',
            dotColor: 'bg-emerald-500',
        },
        {
            key: 'low',
            label: 'Low stock',
            count: summary.low,
            percentage: (summary.low / total) * 100,
            color: 'bg-amber-500',
            textColor: 'text-amber-600 dark:text-amber-400',
            dotColor: 'bg-amber-500',
        },
        {
            key: 'out',
            label: 'Out of stock',
            count: summary.out,
            percentage: (summary.out / total) * 100,
            color: 'bg-rose-500',
            textColor: 'text-rose-600 dark:text-rose-400',
            dotColor: 'bg-rose-500',
        },
    ];
});

const stockAlertCounts = computed(() => ({
    all: stockSummary.value.low + stockSummary.value.out,
    low: stockSummary.value.low,
    out: stockSummary.value.out,
}));

const stockAlertItems = computed(() => {
    return selectedStockItems.value
        .filter(item => {
            if (item.quantity >= 10) {
                return false;
            }

            if (stockAlertFilter.value === 'low') {
                return item.quantity > 0;
            }

            if (stockAlertFilter.value === 'out') {
                return item.quantity === 0;
            }

            return true;
        })
        .sort((a, b) => a.quantity - b.quantity);
});

const cards = computed(() => {
    if (activeTab.value === 'production' || activeTab.value === 'attendance') {
        return currentData.value.cards;
    }

    return activeTab.value === 'salary'
        ? salaryCards
        : stockSummaryCards.value;
});

const trend = computed(() => {
    if (activeTab.value === 'production' || activeTab.value === 'attendance') {
        return currentData.value.trend;
    }

    return [];
});

const trendMax = computed(() => {
    if (activeTab.value === 'production') {
        return Math.max(...productionData[period.value].trend.map(
            item => item.good + item.rejected,
        ));
    }

    if (activeTab.value === 'attendance') {
        return 32;
    }

    return 1;
});

const tabTitle = computed(() => ({
    production: 'Production overview',
    attendance: 'Attendance overview',
    salary: 'Salary overview',
    stocks: 'Inventory overview',
})[activeTab.value]);

const tabDescription = computed(() => ({
    production: 'Monitor factory output, quality and machine performance.',
    attendance: 'Monitor employee attendance and shift activity.',
    salary: 'Track employee salary estimates and payment progress.',
    stocks: 'Monitor raw materials, tools and inventory availability.',
})[activeTab.value]);

function formatCurrency(value: number): string {
    if (value >= 100000) {
        return `₹${(value / 100000).toFixed(2)}L`;
    }

    return `₹${value.toLocaleString('en-IN')}`;
}

function barHeight(value: number): string {
    return `${(value / trendMax.value) * 100}%`;
}

const totalSalary = 485000;
const totalPaid = 325000;
const totalPending = 160000;
const paidPercentage = (totalPaid / totalSalary) * 100;
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <header class="space-y-1">
            <p class="text-sm text-muted-foreground">
                Management overview
            </p>

            <h1 class="text-2xl font-semibold tracking-tight">
                Administration dashboard
            </h1>

            <p class="text-sm text-muted-foreground">
                {{ tabDescription }}
            </p>
        </header>

        <!-- Main tabs -->
        <div class="overflow-x-auto border-b border-border">
            <div class="flex min-w-max gap-6" role="tablist" aria-label="Dashboard sections">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.value"
                    class="flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                    :class="activeTab === tab.value
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'"
                    @click="activeTab = tab.value"
                >
                    <component :is="tab.icon" class="size-4" />
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- Section heading and controls -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold">
                    {{ tabTitle }}
                </h2>

                <p class="text-sm text-muted-foreground">
                    <template v-if="activeTab === 'stocks'">
                        Current inventory status and stock alerts.
                    </template>
                    <template v-else-if="activeTab === 'salary'">
                        Current month payment summary and history.
                    </template>
                    <template v-else>
                        Summary and performance for the selected period.
                    </template>
                </p>
            </div>

            <div
                v-if="activeTab === 'production' || activeTab === 'attendance'"
                class="inline-flex rounded-lg border border-border bg-card p-1"
                aria-label="Dashboard period"
            >
                <button
                    v-for="item in periods"
                    :key="item.value"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="period === item.value
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'"
                    :aria-pressed="period === item.value"
                    @click="period = item.value"
                >
                    {{ item.label }}
                </button>
            </div>

            <div
                v-else-if="activeTab === 'salary'"
                class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm"
            >
                <CalendarDays class="size-4 text-muted-foreground" />
                <span>November 2026</span>
            </div>

            <!-- Stock category tabs -->
            <div
                v-else-if="activeTab === 'stocks'"
                class="inline-flex rounded-lg border border-border bg-card p-1"
                aria-label="Stock category"
            >
                <button
                    v-for="category in stockCategories"
                    :key="category.value"
                    type="button"
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors"
                    :class="stockCategory === category.value
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'"
                    :aria-pressed="stockCategory === category.value"
                    @click="stockCategory = category.value; stockAlertFilter = 'all'"
                >
                    <component :is="category.icon" class="size-3.5" />
                    {{ category.label }}
                </button>
            </div>
        </div>

        <!-- Statistics cards -->
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in cards"
                :key="card.label"
                class="rounded-xl border border-border bg-card p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </p>

                    <div
                        class="rounded-lg p-2"
                        :class="'tone' in card
                            ? {
                                'bg-muted text-muted-foreground': card.tone === 'default',
                                'bg-emerald-500/10 text-emerald-600': card.tone === 'success',
                                'bg-amber-500/10 text-amber-600': card.tone === 'warning',
                                'bg-rose-500/10 text-rose-600': card.tone === 'danger',
                            }
                            : 'bg-muted text-muted-foreground'"
                    >
                        <component :is="card.icon" class="size-4" />
                    </div>
                </div>

                <p class="mt-3 text-2xl font-semibold tracking-tight tabular-nums">
                    {{ card.value }}
                </p>

                <div class="mt-1 flex flex-wrap items-center gap-1 text-xs">
                    <template v-if="'trend' in card && card.trend">
                        <span
                            class="flex items-center gap-0.5 font-medium"
                            :class="card.up ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            <component
                                :is="card.up ? ArrowUpRight : ArrowDownRight"
                                class="size-3"
                            />
                            {{ card.trend }}
                        </span>

                        <span class="text-muted-foreground">
                            vs previous period
                        </span>
                    </template>

                    <span v-else class="text-muted-foreground">
                        {{ card.description }}
                    </span>
                </div>
            </article>
        </div>

        <!-- PRODUCTION -->
        <template v-if="activeTab === 'production'">
            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <h3 class="font-semibold">Production trend</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Good pieces versus rejected pieces
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-primary" />
                            Good pieces
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-rose-500" />
                            Rejected pieces
                        </div>
                    </div>

                    <div class="flex h-56 items-end gap-3 sm:gap-6">
                        <div
                            v-for="item in trend"
                            :key="item.label"
                            class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                        >
                            <div class="flex h-full w-full items-end justify-center gap-1">
                                <div
                                    class="w-3/5 max-w-9 rounded-t-sm bg-primary transition-all"
                                    :style="{ height: barHeight(item.good) }"
                                    :title="`Good: ${item.good}`"
                                />

                                <div
                                    class="w-1/4 max-w-3 rounded-t-sm bg-rose-500 transition-all"
                                    :style="{ height: barHeight(item.rejected) }"
                                    :title="`Rejected: ${item.rejected}`"
                                />
                            </div>

                            <span class="text-[10px] text-muted-foreground sm:text-xs">
                                {{ item.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <h3 class="font-semibold">Machine-wise production</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Output contribution by machine
                    </p>
                </div>

                <div class="divide-y divide-border">
                    <div
                        v-for="machine in productionData[period].machines"
                        :key="machine.name"
                        class="space-y-2 p-4"
                    >
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium">{{ machine.name }}</span>

                            <span class="text-muted-foreground tabular-nums">
                                {{ machine.pieces.toLocaleString('en-IN') }} pieces
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${machine.percentage}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <!-- ATTENDANCE -->
        <template v-else-if="activeTab === 'attendance'">
            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <h3 class="font-semibold">Attendance trend</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Present versus not recorded employees
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-emerald-500" />
                            Present
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-amber-500" />
                            Not recorded
                        </div>
                    </div>

                    <div class="flex h-52 items-end gap-3 sm:gap-6">
                        <div
                            v-for="item in trend"
                            :key="item.label"
                            class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2"
                        >
                            <div class="flex h-full w-full items-end justify-center gap-1">
                                <div
                                    class="w-3/5 max-w-9 rounded-t-sm bg-emerald-500"
                                    :style="{ height: barHeight(item.present) }"
                                />

                                <div
                                    class="w-1/4 max-w-3 rounded-t-sm bg-amber-500"
                                    :style="{ height: barHeight(item.absent) }"
                                />
                            </div>

                            <span class="text-[10px] text-muted-foreground sm:text-xs">
                                {{ item.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-4">
                <h3 class="font-semibold">Shift overview</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    Illustrative employee distribution by shift
                </p>

                <div class="mt-5 space-y-4">
                    <div
                        v-for="shift in [
                            { name: 'Day shift', employees: 16, percent: 50 },
                            { name: 'Night shift', employees: 12, percent: 37.5 },
                            { name: '8-hour shift', employees: 4, percent: 12.5 },
                        ]"
                        :key="shift.name"
                    >
                        <div class="flex justify-between text-sm">
                            <span class="font-medium">{{ shift.name }}</span>
                            <span class="text-muted-foreground">
                                {{ shift.employees }} employees
                            </span>
                        </div>

                        <div class="mt-2 h-2 rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${shift.percent}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <!-- SALARY -->
        <template v-else-if="activeTab === 'salary'">
            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <h3 class="font-semibold">Payment progress</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Current month salary payment summary
                    </p>
                </div>

                <div class="space-y-4 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-muted-foreground">
                            Total estimated salary
                        </span>
                        <span class="font-semibold tabular-nums">
                            ₹{{ totalSalary.toLocaleString('en-IN') }}
                        </span>
                    </div>

                    <div class="flex h-4 overflow-hidden rounded-full bg-muted">
                        <div
                            class="bg-emerald-500 transition-all"
                            :style="{ width: `${paidPercentage}%` }"
                        />
                        <div
                            class="bg-amber-500"
                            :style="{ width: `${100 - paidPercentage}%` }"
                        />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-emerald-500" />
                            <div>
                                <p class="text-xs text-muted-foreground">Paid</p>
                                <p class="text-sm font-semibold">
                                    {{ formatCurrency(totalPaid) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-amber-500" />
                            <div>
                                <p class="text-xs text-muted-foreground">Pending</p>
                                <p class="text-sm font-semibold">
                                    {{ formatCurrency(totalPending) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <h3 class="font-semibold">Monthly salary trend</h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Paid versus pending salary amounts
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-emerald-500" />
                            Paid
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-sm bg-amber-500" />
                            Pending
                        </div>
                    </div>

                    <div
                        v-for="item in salaryTrend"
                        :key="item.label"
                        class="space-y-2"
                    >
                        <div class="flex justify-between text-xs">
                            <span class="font-medium">{{ item.label }}</span>
                            <span class="text-muted-foreground tabular-nums">
                                {{ formatCurrency(item.paid + item.pending) }}
                            </span>
                        </div>

                        <div class="flex h-3 overflow-hidden rounded-full bg-muted">
                            <div
                                class="bg-emerald-500"
                                :style="{ width: `${item.paid / (item.paid + item.pending) * 100}%` }"
                            />
                            <div
                                class="bg-amber-500"
                                :style="{ width: `${item.pending / (item.paid + item.pending) * 100}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-4">
                <h3 class="font-semibold">Payment status</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    Employee payment distribution
                </p>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-emerald-500/10 p-4">
                        <p class="text-sm text-emerald-700 dark:text-emerald-400">
                            Paid employees
                        </p>
                        <p class="mt-2 text-2xl font-semibold">24</p>
                    </div>

                    <div class="rounded-lg bg-amber-500/10 p-4">
                        <p class="text-sm text-amber-700 dark:text-amber-400">
                            Pending employees
                        </p>
                        <p class="mt-2 text-2xl font-semibold">8</p>
                    </div>
                </div>
            </section>
        </template>

        <!-- STOCKS -->
        <template v-else-if="activeTab === 'stocks'">
            <!-- Stock status distribution -->
            <section class="rounded-xl border border-border bg-card">
                <div class="border-b border-border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-semibold">Stock status distribution</h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Inventory health across {{ stockSummary.total }} items
                            </p>
                        </div>

                        <span class="text-xs text-muted-foreground">
                            {{ stockSummary.total }} total items
                        </span>
                    </div>
                </div>

                <div class="space-y-5 p-4">
                    <!-- Stacked distribution bar -->
                    <div class="flex h-4 overflow-hidden rounded-full bg-muted">
                        <div
                            v-for="status in stockStatusDistribution"
                            :key="status.key"
                            class="h-full transition-all"
                            :class="status.color"
                            :style="{ width: `${status.percentage}%` }"
                            :title="`${status.label}: ${status.count}`"
                        />
                    </div>

                    <!-- Legend -->
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div
                            v-for="status in stockStatusDistribution"
                            :key="status.key"
                            class="flex items-start gap-2"
                        >
                            <span
                                class="mt-1 size-2.5 shrink-0 rounded-full"
                                :class="status.dotColor"
                            />

                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground">
                                    {{ status.label }}
                                </p>

                                <div class="mt-1 flex items-baseline gap-2">
                                    <span class="text-lg font-semibold tabular-nums">
                                        {{ status.count }}
                                    </span>

                                    <span
                                        class="text-xs font-medium tabular-nums"
                                        :class="status.textColor"
                                    >
                                        {{ status.percentage.toFixed(0) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stock alerts -->
            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border p-4">
                    <div>
                        <h3 class="font-semibold">Stock alerts</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Items that need stock replenishment
                        </p>
                    </div>

                    <span
                        class="rounded-md px-2.5 py-1 text-xs font-medium"
                        :class="stockAlertCounts.all > 0
                            ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400'
                            : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'"
                    >
                        {{ stockAlertCounts.all }} alerts
                    </span>
                </div>

                <!-- Alert filters -->
                <div class="flex flex-wrap gap-2 border-b border-border p-3">
                    <button
                        v-for="filter in stockAlertFilters"
                        :key="filter.value"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors"
                        :class="stockAlertFilter === filter.value
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground hover:text-foreground'"
                        :aria-pressed="stockAlertFilter === filter.value"
                        @click="stockAlertFilter = filter.value"
                    >
                        {{ filter.label }}

                        <span
                            class="rounded px-1.5 py-0.5 text-[10px] tabular-nums"
                            :class="stockAlertFilter === filter.value
                                ? 'bg-primary-foreground/15 text-primary-foreground'
                                : 'bg-background text-muted-foreground'"
                        >
                            {{ stockAlertCounts[filter.value] }}
                        </span>
                    </button>
                </div>

                <!-- Alert list -->
                <div
                    v-if="stockAlertItems.length"
                    class="max-h-[360px] divide-y divide-border overflow-y-auto"
                >
                    <div
                        v-for="item in stockAlertItems"
                        :key="item.id"
                        class="flex flex-wrap items-center justify-between gap-3 p-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                                :class="item.quantity === 0
                                    ? 'bg-rose-500/10 text-rose-600'
                                    : 'bg-amber-500/10 text-amber-600'"
                            >
                                <component
                                    :is="item.quantity === 0 ? XCircle : AlertTriangle"
                                    class="size-4"
                                />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ item.name }}
                                </p>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ item.unit }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p
                                    class="text-sm font-semibold tabular-nums"
                                    :class="item.quantity === 0
                                        ? 'text-rose-600 dark:text-rose-400'
                                        : 'text-amber-600 dark:text-amber-400'"
                                >
                                    {{ item.quantity }} {{ item.unit }}
                                </p>

                                <p class="text-xs text-muted-foreground">
                                    Available
                                </p>
                            </div>

                            <span
                                class="min-w-24 rounded-md px-2 py-1 text-center text-[11px] font-medium"
                                :class="item.quantity === 0
                                    ? 'bg-rose-500/10 text-rose-700 dark:text-rose-400'
                                    : 'bg-amber-500/10 text-amber-700 dark:text-amber-400'"
                            >
                                {{ item.quantity === 0 ? 'Out of stock' : 'Low stock' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center px-4 py-12 text-center">
                    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                        <CheckCircle2 class="size-6" />
                    </div>

                    <h4 class="mt-3 text-sm font-semibold">
                        No stock alerts
                    </h4>

                    <p class="mt-1 max-w-xs text-xs text-muted-foreground">
                        {{
                            stockAlertFilter === 'low'
                                ? 'There are no low-stock items in this category.'
                                : stockAlertFilter === 'out'
                                    ? 'There are no out-of-stock items in this category.'
                                    : 'All items in this category have sufficient stock.'
                        }}
                    </p>
                </div>
            </section>
        </template>

        <!-- Demo data notice -->
        <p class="text-center text-xs text-muted-foreground">
            Dashboard currently displays static demonstration data.
        </p>
    </div>
</template>
