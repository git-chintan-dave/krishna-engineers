<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {AlertTriangle, Boxes, CheckCircle2, Wrench, XCircle,} from '@lucide/vue';

import type {ListingStatItem} from '@/components/common/ListingStats.vue';
import ListingStats from '@/components/common/ListingStats.vue';

import type {SegmentedTabItem} from '@/components/common/SegmentedTabs.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

import type {DistributionItem} from '@/components/common/DistributionCard.vue';
import DistributionCard from '@/components/common/DistributionCard.vue';
import SectionHeader from "@/components/common/SectionHeader.vue";

type StockCategory = 'raw_material' | 'tool';
type StockAlertFilter = 'all' | 'low' | 'out';

const stockCategory = ref<StockCategory>('raw_material');
const stockAlertFilter = ref<StockAlertFilter>('all');

const stockCategories = [
    { value: 'raw_material', label: 'Raw materials', icon: Boxes },
    { value: 'tool', label: 'Tools', icon: Wrench },
] satisfies readonly SegmentedTabItem[];

const stockAlertFilters = [
    { value: 'all', label: 'All alerts' },
    { value: 'low', label: 'Low stock' },
    { value: 'out', label: 'Out of stock' },
] as const;

// Static demonstration stock data.
// Demo threshold:
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
] as const;

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

const stockSummaryCards = computed<ListingStatItem[]>(() => [
    {
        label: 'Total items',
        value: stockSummary.value.total,
        description: 'Items in this category',
        icon: Boxes,
        variant: 'default',
    },
    {
        label: 'Healthy stock',
        value: stockSummary.value.healthy,
        description: '10 or more available',
        icon: CheckCircle2,
        variant: 'success',
    },
    {
        label: 'Low stock',
        value: stockSummary.value.low,
        description: 'Between 1 and 9 available',
        icon: AlertTriangle,
        variant: 'warning',
    },
    {
        label: 'Out of stock',
        value: stockSummary.value.out,
        description: 'No stock available',
        icon: XCircle,
        variant: 'destructive',
    },
]);

const stockStatusDistribution = computed<DistributionItem[]>(() => {
    const summary = stockSummary.value;

    return [
        {
            key: 'healthy',
            label: 'Healthy stock',
            count: summary.healthy,
            color: 'bg-emerald-500',
            dotColor: 'bg-emerald-500',
            textColor: 'text-emerald-600 dark:text-emerald-400',
        },
        {
            key: 'low',
            label: 'Low stock',
            count: summary.low,
            color: 'bg-amber-500',
            dotColor: 'bg-amber-500',
            textColor: 'text-amber-600 dark:text-amber-400',
        },
        {
            key: 'out',
            label: 'Out of stock',
            count: summary.out,
            color: 'bg-rose-500',
            dotColor: 'bg-rose-500',
            textColor: 'text-rose-600 dark:text-rose-400',
        },
    ];
});

const stockAlertCounts = computed(() => ({
    all: stockSummary.value.low + stockSummary.value.out,
    low: stockSummary.value.low,
    out: stockSummary.value.out,
}));

const stockAlertItems = computed(() =>
    selectedStockItems.value
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
        .sort((a, b) => a.quantity - b.quantity),
);

// Reset alert filter when switching stock category.
watch(stockCategory, () => {
    stockAlertFilter.value = 'all';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header and category selection -->
        <SectionHeader
            title="Inventory overview"
            description="Current inventory status and stock alerts."
            :icon="Boxes"
        >
            <template #actions>
                <SegmentedTabs
                    v-model="stockCategory"
                    :items="stockCategories"
                    aria-label="Stock category"
                    variant="primary"
                    size="sm"
                />
            </template>
        </SectionHeader>

        <!-- Summary statistics -->
        <ListingStats
            :items="stockSummaryCards"
            :columns="4"
        />

        <!-- Stock status distribution -->
        <DistributionCard
            title="Stock status distribution"
            description="Inventory health by stock status"
            :total="stockSummary.total"
            :items="stockStatusDistribution"
        />

        <!-- Stock alerts -->
        <section class="overflow-hidden rounded-xl border border-border bg-card">
            <!-- Header -->
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
                    class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1"
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

            <!-- Scrollable alert list -->
            <div
                v-if="stockAlertItems.length"
                class="max-h-[360px] divide-y divide-border overflow-y-auto"
            >
                <div
                    v-for="item in stockAlertItems"
                    :key="item.id"
                    class="flex flex-wrap items-center justify-between gap-3 p-4 transition-colors hover:bg-muted/30"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="item.quantity === 0
                                ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'"
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
            <div
                v-else
                class="flex flex-col items-center justify-center px-4 py-12 text-center"
            >
                <div class="flex size-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
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

        <p class="text-center text-xs text-muted-foreground">
            Dashboard currently displays static demonstration data.
        </p>
    </div>
</template>
