<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import {AlertTriangle, Boxes, CheckCircle2, Wrench, XCircle,} from '@lucide/vue';

import type {ListingStatItem} from '@/components/common/ListingStats.vue';
import ListingStats from '@/components/common/ListingStats.vue';

import type {SegmentedTabItem} from '@/components/common/SegmentedTabs.vue';
import SegmentedTabs from '@/components/common/SegmentedTabs.vue';

import type {DistributionItem} from '@/components/common/DistributionCard.vue';
import DistributionCard from '@/components/common/DistributionCard.vue';

import SectionHeader from '@/components/common/SectionHeader.vue';

import type {StockDashboard} from '@/types/dashboard';

type StockCategory = 'raw_material' | 'tool';
type StockAlertFilter = 'all' | 'low' | 'out';

const props = defineProps<{
    rawMaterialStock?: StockDashboard | null;
    toolStock?: StockDashboard | null;
}>();

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

const stockData = computed(() =>
    stockCategory.value === 'raw_material'
        ? props.rawMaterialStock
        : props.toolStock,
);

const isLoading = computed(() => stockData.value === undefined);

const stockSummary = computed(() => {
    const data = stockData.value;

    return {
        total: data?.total ?? 0,
        healthy: data?.healthy ?? 0,
        low: data?.low ?? 0,
        out: data?.out ?? 0,
        threshold: data?.threshold ?? 0,
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
        description: `${stockSummary.value.threshold} or more available`,
        icon: CheckCircle2,
        variant: 'success',
    },
    {
        label: 'Low stock',
        value: stockSummary.value.low,
        description: `Between 1 and ${Math.max(0, stockSummary.value.threshold - 1)}`,
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

const stockAlertItems = computed(() => {
    const items = stockData.value?.items ?? [];

    return items.filter(item => {
        if (stockAlertFilter.value === 'low') {
            return item.quantity > 0 && item.quantity < stockSummary.value.threshold;
        }

        if (stockAlertFilter.value === 'out') {
            return item.quantity === 0;
        }

        return item.quantity < stockSummary.value.threshold;
    });
});

const hasMoreAlerts = computed(() => stockData.value?.hasMoreAlerts ?? false);

watch(stockCategory, () => {
    stockAlertFilter.value = 'all';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
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

        <!-- Loading state -->
        <div
            v-if="isLoading"
            class="space-y-6"
        >
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="item in 4"
                    :key="item"
                    class="h-28 animate-pulse rounded-xl border border-border bg-muted/40"
                />
            </div>

            <div class="h-52 animate-pulse rounded-xl border border-border bg-muted/40" />

            <div class="h-80 animate-pulse rounded-xl border border-border bg-muted/40" />
        </div>

        <!-- Loaded state -->
        <template v-else>
            <!-- Summary -->
            <ListingStats
                :items="stockSummaryCards"
                :columns="4"
            />

            <!-- Distribution -->
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
                        <h3 class="font-semibold">
                            Stock alerts
                        </h3>

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

                <!-- Filters -->
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

                <!-- Alert list -->
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
                                    {{ item.detail }}
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
                                    {{ item.quantity }}
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

                <!-- Alert limit notice -->
                <div
                    v-if="hasMoreAlerts"
                    class="border-t border-border bg-muted/30 px-4 py-3 text-center"
                >
                    <p class="text-xs text-muted-foreground">
                        Showing up to {{ stockData?.alertLimit ?? 20 }} items per alert category.
                        Additional alerts are available in the inventory listing.
                    </p>
                </div>
            </section>
        </template>
    </div>
</template>
