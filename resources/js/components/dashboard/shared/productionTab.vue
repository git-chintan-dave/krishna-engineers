<script lang="ts" setup>
import {computed, ref} from 'vue';
import {
    Activity,
    BarChart3,
    Boxes,
    CalendarDays,
    CheckCircle2,
    Factory,
    Package,
    Scissors,
    TrendingUp,
    XCircle,
} from '@lucide/vue';

import ListingStats from '@/components/common/ListingStats.vue';
import SectionHeader from '@/components/common/SectionHeader.vue';
import SegmentedTabs from "@/components/common/SegmentedTabs.vue";

type Period = 'current' | 'previous';

interface MachineProduction {
    name: string;
    quantity: number;
    rejected: number;
}

interface SectionSizeProduction {
    name: string;
    quantity: number;
}

interface DailyProduction {
    date: string;
    operatorQuantity: number;
    operatorRejected: number;
    bandsawQuantity: number;
}

interface ProductionPeriod {
    label: string;
    operator: {
        totalQuantity: number;
        rejectionQuantity: number;
        productionSeconds: number;
        effectiveWorkingSeconds: number;
        workEntries: number;
        machineCount: number;
        partCount: number;
        machines: MachineProduction[];
    };
    bandsaw: {
        totalQuantity: number;
        workEntries: number;
        partCount: number;
        sectionSizeCount: number;
        sectionSizes: SectionSizeProduction[];
    };
    daily: DailyProduction[];
}

const selectedPeriod = ref<Period>('current');

const periodTabs = [
    { value: 'current', label: 'Current Month' },
    { value: 'previous', label: 'Previous Month' },
] as const;

const productionPeriods: Record<Period, ProductionPeriod> = {
    current: {
        label: 'November 2026',
        operator: {
            totalQuantity: 2450,
            rejectionQuantity: 65,
            productionSeconds: 88200,
            effectiveWorkingSeconds: 108000,
            workEntries: 28,
            machineCount: 3,
            partCount: 8,
            machines: [
                { name: 'VMC-1', quantity: 1050, rejected: 25 },
                { name: 'VMC-2', quantity: 850, rejected: 28 },
                { name: 'VMC-3', quantity: 550, rejected: 12 },
            ],
        },
        bandsaw: {
            totalQuantity: 8250,
            workEntries: 42,
            partCount: 12,
            sectionSizeCount: 5,
            sectionSizes: [
                { name: '25 × 25 mm', quantity: 2200 },
                { name: '40 × 40 mm', quantity: 1850 },
                { name: '50 × 50 mm', quantity: 1700 },
                { name: '60 × 40 mm', quantity: 1400 },
                { name: 'Other', quantity: 1100 },
            ],
        },
        daily: [
            { date: '04 Nov', operatorQuantity: 620, operatorRejected: 15, bandsawQuantity: 1850 },
            { date: '03 Nov', operatorQuantity: 580, operatorRejected: 18, bandsawQuantity: 1750 },
            { date: '02 Nov', operatorQuantity: 650, operatorRejected: 12, bandsawQuantity: 2100 },
            { date: '01 Nov', operatorQuantity: 600, operatorRejected: 20, bandsawQuantity: 2550 },
        ],
    },

    previous: {
        label: 'October 2026',
        operator: {
            totalQuantity: 5820,
            rejectionQuantity: 142,
            productionSeconds: 209520,
            effectiveWorkingSeconds: 270000,
            workEntries: 64,
            machineCount: 4,
            partCount: 14,
            machines: [
                { name: 'VMC-1', quantity: 1850, rejected: 42 },
                { name: 'VMC-2', quantity: 1650, rejected: 40 },
                { name: 'VMC-3', quantity: 1320, rejected: 35 },
                { name: 'VMC-4', quantity: 1000, rejected: 25 },
            ],
        },
        bandsaw: {
            totalQuantity: 18750,
            workEntries: 96,
            partCount: 18,
            sectionSizeCount: 7,
            sectionSizes: [
                { name: '25 × 25 mm', quantity: 4800 },
                { name: '40 × 40 mm', quantity: 4200 },
                { name: '50 × 50 mm', quantity: 3500 },
                { name: '60 × 40 mm', quantity: 3000 },
                { name: 'Other', quantity: 3250 },
            ],
        },
        daily: [
            { date: '31 Oct', operatorQuantity: 620, operatorRejected: 14, bandsawQuantity: 1900 },
            { date: '30 Oct', operatorQuantity: 580, operatorRejected: 12, bandsawQuantity: 1750 },
            { date: '29 Oct', operatorQuantity: 650, operatorRejected: 16, bandsawQuantity: 2100 },
            { date: '28 Oct', operatorQuantity: 590, operatorRejected: 15, bandsawQuantity: 1850 },
        ],
    },
};

const currentPeriod = computed(
    () => productionPeriods[selectedPeriod.value],
);

const operatorGoodQuantity = computed(
    () =>
        currentPeriod.value.operator.totalQuantity -
        currentPeriod.value.operator.rejectionQuantity,
);

const operatorEfficiency = computed(() => {
    const { productionSeconds, effectiveWorkingSeconds } =
        currentPeriod.value.operator;

    if (!effectiveWorkingSeconds) return 0;

    return Math.round(
        (productionSeconds / effectiveWorkingSeconds) * 100,
    );
});

const operatorRejectionRate = computed(() => {
    const { totalQuantity, rejectionQuantity } =
        currentPeriod.value.operator;

    if (!totalQuantity) return 0;

    return Number(
        ((rejectionQuantity / totalQuantity) * 100).toFixed(1),
    );
});

const operatorGoodRate = computed(() => {
    const total = currentPeriod.value.operator.totalQuantity;

    if (!total) return 0;

    return Number(
        ((operatorGoodQuantity.value / total) * 100).toFixed(1),
    );
});

const maxMachineQuantity = computed(() =>
    Math.max(
        ...currentPeriod.value.operator.machines.map(
            (machine) => machine.quantity,
        ),
        1,
    ),
);

const maxSectionQuantity = computed(() =>
    Math.max(
        ...currentPeriod.value.bandsaw.sectionSizes.map(
            (section) => section.quantity,
        ),
        1,
    ),
);

const maxDailyQuantity = computed(() =>
    Math.max(
        ...currentPeriod.value.daily.map((day) =>
            Math.max(day.operatorQuantity, day.bandsawQuantity),
        ),
        1,
    ),
);

const formatNumber = (value: number) =>
    new Intl.NumberFormat('en-IN').format(value);

const formatDuration = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};

const operatorPrimaryStats = computed(() => [
    {
        label: 'Total quantity',
        value: formatNumber(currentPeriod.value.operator.totalQuantity),
        icon: Package,
        description: 'Produced quantity',
        variant: 'default' as const,
    },
    {
        label: 'Good quantity',
        value: formatNumber(operatorGoodQuantity.value),
        icon: CheckCircle2,
        description: `${operatorGoodRate.value}% of total`,
        variant: 'success' as const,
    },
    {
        label: 'Rejected quantity',
        value: formatNumber(currentPeriod.value.operator.rejectionQuantity),
        icon: XCircle,
        description: `${operatorRejectionRate.value}% rejection rate`,
        variant: 'destructive' as const,
    },
    {
        label: 'Efficiency',
        value: `${operatorEfficiency.value}%`,
        icon: TrendingUp,
        description: 'Production time / effective time',
        variant: 'info' as const,
    },
]);

const operatorSecondaryStats = computed(() => [
    {
        label: 'Production time',
        value: formatDuration(currentPeriod.value.operator.productionSeconds),
        icon: Activity,
        variant: 'default' as const,
    },
    {
        label: 'Work entries',
        value: formatNumber(currentPeriod.value.operator.workEntries),
        icon: BarChart3,
        variant: 'default' as const,
    },
    {
        label: 'Machines used',
        value: currentPeriod.value.operator.machineCount,
        icon: Factory,
        variant: 'default' as const,
    },
    {
        label: 'Parts processed',
        value: currentPeriod.value.operator.partCount,
        icon: Package,
        variant: 'default' as const,
    },
]);

const bandsawStats = computed(() => [
    {
        label: 'Total quantity',
        value: formatNumber(currentPeriod.value.bandsaw.totalQuantity),
        icon: Package,
        description: 'Total cutting quantity',
        variant: 'default' as const,
    },
    {
        label: 'Work entries',
        value: formatNumber(currentPeriod.value.bandsaw.workEntries),
        icon: Activity,
        description: 'Recorded entries',
        variant: 'default' as const,
    },
    {
        label: 'Parts processed',
        value: currentPeriod.value.bandsaw.partCount,
        icon: Package,
        description: 'Unique parts',
        variant: 'default' as const,
    },
    {
        label: 'Section sizes',
        value: currentPeriod.value.bandsaw.sectionSizeCount,
        icon: Boxes,
        description: 'Unique sizes',
        variant: 'default' as const,
    },
]);
</script>

<template>
    <div class="space-y-6">
        <!-- Production overview -->
        <SectionHeader
            title="Production Overview"
            description="Company-wide operator and bandsaw production."
            :icon="Factory"
        >
            <template #actions>
                <SegmentedTabs
                    v-model="selectedPeriod"
                    :items="periodTabs"
                    aria-label="Production period"
                    variant="primary"
                    size="sm"
                />
            </template>
        </SectionHeader>

        <!-- Selected period -->
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <CalendarDays class="size-4" />
            <span>{{ currentPeriod.label }}</span>
        </div>

        <!-- Operator production -->
        <section class="space-y-4">
            <SectionHeader
                title="Operator Production"
                description="CNC machine production"
                :icon="Factory"
            />

            <ListingStats
                :items="operatorPrimaryStats"
                :columns="4"
            />

            <ListingStats
                :items="operatorSecondaryStats"
                :columns="4"
            />

            <!-- Production quality -->
            <div class="rounded-xl border bg-card p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="font-medium">Production Quality</h4>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Good and rejected quantity distribution
                        </p>
                    </div>

                    <p class="text-sm font-semibold tabular-nums">
                        {{ formatNumber(currentPeriod.operator.totalQuantity) }}
                    </p>
                </div>

                <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full bg-green-500 transition-all"
                        :style="{ width: `${operatorGoodRate}%` }"
                    />
                    <div
                        class="h-full bg-red-500 transition-all"
                        :style="{ width: `${operatorRejectionRate}%` }"
                    />
                </div>

                <div class="mt-3 flex flex-wrap gap-5 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-green-500" />
                        <span class="text-muted-foreground">Good</span>
                        <span class="font-medium">
                            {{ operatorGoodRate }}%
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-red-500" />
                        <span class="text-muted-foreground">Rejected</span>
                        <span class="font-medium">
                            {{ operatorRejectionRate }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Machine breakdown -->
            <div class="rounded-xl border bg-card p-4 sm:p-5">
                <SectionHeader
                    title="Machine-wise Production"
                    description="Quantity produced by each machine"
                    :icon="Boxes"
                />

                <div class="mt-5 space-y-5">
                    <div
                        v-for="machine in currentPeriod.operator.machines"
                        :key="machine.name"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium">{{ machine.name }}</span>

                            <div class="text-right">
                                <span class="font-semibold tabular-nums">
                                    {{ formatNumber(machine.quantity) }}
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">
                                    {{ formatNumber(machine.rejected) }} rejected
                                </span>
                            </div>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{
                                    width: `${(machine.quantity / maxMachineQuantity) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bandsaw production -->
        <section class="space-y-4">
            <SectionHeader
                title="Bandsaw Production"
                description="Raw material cutting production"
                :icon="Scissors"
            />

            <ListingStats
                :items="bandsawStats"
                :columns="4"
            />

            <!-- Section size breakdown -->
            <div class="rounded-xl border bg-card p-4 sm:p-5">
                <SectionHeader
                    title="Section Size-wise Production"
                    description="Cutting quantity by section size"
                    :icon="Boxes"
                />

                <div class="mt-5 space-y-5">
                    <div
                        v-for="section in currentPeriod.bandsaw.sectionSizes"
                        :key="section.name"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium">{{ section.name }}</span>
                            <span class="font-semibold tabular-nums">
                                {{ formatNumber(section.quantity) }}
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-orange-500 transition-all"
                                :style="{
                                    width: `${(section.quantity / maxSectionQuantity) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Daily production history -->
        <section class="overflow-hidden rounded-xl border bg-card">
            <div class="border-b p-4 sm:p-5">
                <SectionHeader
                    title="Daily Production"
                    description="Operator and bandsaw production by date"
                    :icon="BarChart3"
                />
            </div>

            <!-- Daily comparison bars -->
            <div class="space-y-5 border-b p-4 sm:p-5">
                <div
                    v-for="day in currentPeriod.daily"
                    :key="day.date"
                    class="space-y-3"
                >
                    <p class="text-sm font-medium">{{ day.date }}</p>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Operator</span>
                            <span class="font-medium tabular-nums">
                                {{ formatNumber(day.operatorQuantity) }}
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${(day.operatorQuantity / maxDailyQuantity) * 100}%`,
                                }"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Bandsaw</span>
                            <span class="font-medium tabular-nums">
                                {{ formatNumber(day.bandsawQuantity) }}
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-orange-500"
                                :style="{
                                    width: `${(day.bandsawQuantity / maxDailyQuantity) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily table -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead class="bg-muted/40 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Operator
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Rejected
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Bandsaw
                        </th>
                    </tr>
                    </thead>

                    <tbody class="divide-y">
                    <tr
                        v-for="day in currentPeriod.daily"
                        :key="day.date"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ day.date }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatNumber(day.operatorQuantity) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums text-red-600">
                            {{ formatNumber(day.operatorRejected) }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ formatNumber(day.bandsawQuantity) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-center text-xs text-muted-foreground">
            Operator and Bandsaw production are shown separately because they represent different production stages.
        </p>
    </div>
</template>
