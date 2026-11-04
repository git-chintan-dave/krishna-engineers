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
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

import type {ProductionDashboard, ProductionPeriod} from '@/types/dashboard';

type Period = 'current' | 'previous';

const props = defineProps<{
    productionDashboard?: ProductionDashboard | null;
}>();

const selectedPeriod = ref<Period>('current');

const periodTabs = [
    {value: 'current', label: 'Current Month'},
    {value: 'previous', label: 'Previous Month'},
] as const;

const isLoading = computed(() => props.productionDashboard === undefined);

const currentPeriod = computed<ProductionPeriod | null>(() => {
    if (!props.productionDashboard) return null;

    return selectedPeriod.value === 'current'
        ? props.productionDashboard.currentPeriod
        : props.productionDashboard.previousPeriod;
});

const operatorPrimaryStats = computed(() => {
    const operator = currentPeriod.value?.operator;

    if (!operator) return [];

    return [
        {
            label: 'Total quantity',
            value: operator.totalQuantityFormatted,
            icon: Package,
            description: 'Produced quantity',
            variant: 'default' as const,
        },
        {
            label: 'Good quantity',
            value: operator.goodQuantityFormatted,
            icon: CheckCircle2,
            description: `${operator.goodRateFormatted} of total`,
            variant: 'success' as const,
        },
        {
            label: 'Rejected quantity',
            value: operator.rejectionQuantityFormatted,
            icon: XCircle,
            description: `${operator.rejectionRateFormatted} rejection rate`,
            variant: 'destructive' as const,
        },
        {
            label: 'Efficiency',
            value: operator.productionEfficiency ?? '—',
            icon: TrendingUp,
            description: 'Production time / effective time',
            variant: 'info' as const,
        },
    ];
});

const operatorSecondaryStats = computed(() => {
    const operator = currentPeriod.value?.operator;

    if (!operator) return [];

    return [
        {
            label: 'Production time',
            value: operator.productionTimeFormatted,
            icon: Activity,
            variant: 'default' as const,
        },
        {
            label: 'Work entries',
            value: operator.workEntries,
            icon: BarChart3,
            variant: 'default' as const,
        },
        {
            label: 'Machines used',
            value: operator.machineCount,
            icon: Factory,
            variant: 'default' as const,
        },
        {
            label: 'Parts processed',
            value: operator.partCount,
            icon: Package,
            variant: 'default' as const,
        },
    ];
});

const bandsawStats = computed(() => {
    const bandsaw = currentPeriod.value?.bandsaw;

    if (!bandsaw) return [];

    return [
        {
            label: 'Total quantity',
            value: bandsaw.totalQuantityFormatted,
            icon: Package,
            description: 'Total cutting quantity',
            variant: 'default' as const,
        },
        {
            label: 'Work entries',
            value: bandsaw.workEntries,
            icon: Activity,
            description: 'Recorded entries',
            variant: 'default' as const,
        },
        {
            label: 'Parts processed',
            value: bandsaw.partCount,
            icon: Package,
            description: 'Unique parts',
            variant: 'default' as const,
        },
        {
            label: 'Section sizes',
            value: bandsaw.sectionSizeCount,
            icon: Boxes,
            description: 'Unique sizes',
            variant: 'default' as const,
        },
    ];
});

const maxMachineQuantity = computed(() =>
    Math.max(
        ...(currentPeriod.value?.operator.machines.map(machine => machine.quantity) ?? []),
        1,
    ),
);

const maxSectionQuantity = computed(() =>
    Math.max(
        ...(currentPeriod.value?.bandsaw.sectionSizes.map(section => section.quantity) ?? []),
        1,
    ),
);

const maxDailyQuantity = computed(() =>
    Math.max(
        ...(currentPeriod.value?.daily.map(day =>
            Math.max(day.operatorQuantity, day.bandsawQuantity),
        ) ?? []),
        1,
    ),
);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
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

        <!-- Loading state -->
        <div
            v-if="isLoading"
            class="space-y-6"
        >
            <!-- Summary cards -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="item in 8"
                    :key="item"
                    class="h-28 animate-pulse rounded-xl border border-border bg-muted/40"
                />
            </div>

            <!-- Production quality -->
            <div class="h-32 animate-pulse rounded-xl border border-border bg-muted/40" />

            <!-- Machine / section-size breakdown -->
            <div class="h-80 animate-pulse rounded-xl border border-border bg-muted/40" />

            <div class="h-80 animate-pulse rounded-xl border border-border bg-muted/40" />

            <!-- Daily production -->
            <div class="h-[420px] animate-pulse rounded-xl border border-border bg-muted/40" />
        </div>

        <!-- Loaded state -->
        <template v-else-if="currentPeriod">
            <!-- Selected period -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <CalendarDays class="size-4"/>
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
                            {{ currentPeriod.operator.totalQuantityFormatted }}
                        </p>
                    </div>

                    <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full bg-green-500 transition-all"
                            :style="{width: `${currentPeriod.operator.goodRate}%`}"
                        />
                        <div
                            class="h-full bg-red-500 transition-all"
                            :style="{width: `${currentPeriod.operator.rejectionRate}%`}"
                        />
                    </div>

                    <div class="mt-3 flex flex-wrap gap-5 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-green-500"/>
                            <span class="text-muted-foreground">Good</span>
                            <span class="font-medium">
                                {{ currentPeriod.operator.goodRateFormatted }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-red-500"/>
                            <span class="text-muted-foreground">Rejected</span>
                            <span class="font-medium">
                                {{ currentPeriod.operator.rejectionRateFormatted }}
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

                    <div
                        v-if="currentPeriod.operator.machines.length"
                        class="mt-5 space-y-5"
                    >
                        <div
                            v-for="machine in currentPeriod.operator.machines"
                            :key="machine.name"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium">{{ machine.name }}</span>

                                <div class="text-right">
                                    <span class="font-semibold tabular-nums">
                                        {{ machine.quantityFormatted }}
                                    </span>
                                    <span class="ml-2 text-xs text-muted-foreground">
                                        {{ machine.rejectedFormatted }} rejected
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

                    <p
                        v-else
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No machine production recorded for this period.
                    </p>
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

                    <div
                        v-if="currentPeriod.bandsaw.sectionSizes.length"
                        class="mt-5 space-y-5"
                    >
                        <div
                            v-for="section in currentPeriod.bandsaw.sectionSizes"
                            :key="section.name"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium">{{ section.name }}</span>
                                <span class="font-semibold tabular-nums">
                                    {{ section.quantityFormatted }}
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

                    <p
                        v-else
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No bandsaw production recorded for this period.
                    </p>
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

                <template v-if="currentPeriod.daily.length">
                    <!-- Daily comparison bars -->
                    <div class="space-y-5 border-b p-4 sm:p-5">
                        <div
                            v-for="day in currentPeriod.daily"
                            :key="day.workDate"
                            class="space-y-3"
                        >
                            <p class="text-sm font-medium">{{ day.date }}</p>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Operator</span>
                                    <span class="font-medium tabular-nums">
                                        {{ day.operatorQuantityFormatted }}
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
                                        {{ day.bandsawQuantityFormatted }}
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
                                <th class="px-4 py-3 text-right font-medium">Operator</th>
                                <th class="px-4 py-3 text-right font-medium">Rejected</th>
                                <th class="px-4 py-3 text-right font-medium">Bandsaw</th>
                            </tr>
                            </thead>

                            <tbody class="divide-y">
                            <tr
                                v-for="day in currentPeriod.daily"
                                :key="day.workDate"
                                class="hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ day.date }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ day.operatorQuantityFormatted }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600">
                                    {{ day.operatorRejectedFormatted }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ day.bandsawQuantityFormatted }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <p
                    v-else
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    No production recorded for this period.
                </p>
            </section>

            <p class="text-center text-xs text-muted-foreground">
                Operator and Bandsaw production are shown separately because they represent different production stages.
            </p>
        </template>

        <!-- Unavailable state -->
        <div
            v-else
            class="rounded-xl border bg-card p-8 text-center"
        >
            <Factory class="mx-auto size-8 text-muted-foreground"/>
            <p class="mt-3 font-medium">Production data unavailable</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Production information could not be loaded.
            </p>
        </div>
    </div>
</template>
