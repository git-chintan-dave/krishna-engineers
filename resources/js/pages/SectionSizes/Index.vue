<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {CheckCircle, CircleX, Database, FileUp, Layers, Package, RotateCcw, SearchX, TriangleAlert,} from '@lucide/vue';
import {computed, ref, watch} from 'vue';

import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import ListingStats, {type ListingStatItem} from '@/components/common/ListingStats.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppStockFilterSelect from '@/components/selects/AppStockFilterSelect.vue';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {sectionSizePage} from '@/config/sectionSizes';

import {bulkDestroy, create, destroy, index as sectionSizesRoute,} from '@/routes/section-sizes';
import {view as sectionSizesImportRoute} from '@/routes/section-sizes/import';

import {getQuantityRowHighlight} from '@/lib/row-highlights';
import {createSectionSizeColumns} from '@/table/sectionSizes';

import type {StockFilter} from '@/types/tool';
import type {
    SectionSize,
    SectionSizeFilters,
    SectionSizeSort,
    SectionSizesResponse,
    SectionSizeStatistics,
} from '@/types/sectionSize';

const props = defineProps<{
    sectionSizes?: SectionSizesResponse;
    hasAnyRecords: boolean;
    filters: SectionSizeFilters;
    statistics?: SectionSizeStatistics;
    low_stock_threshold: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: sectionSizePage.title,
            resourceUrl: sectionSizesRoute().url,
        }),
    },
});

const filterSectionId = ref<number | 'all'>(props.filters.sectionId);
const stockFilter = ref<StockFilter | null>(props.filters.stock);

watch(
    () => props.filters,
    (filters) => {
        filterSectionId.value = filters.sectionId;
        stockFilter.value = filters.stock;
    },
    {deep: true},
);

const handleSectionChange = (value: number | null): void => {
    filterSectionId.value = value ?? 'all';
};

const handleStockChange = (value: StockFilter | null): void => {
    stockFilter.value = value;
};

const columns = createSectionSizeColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
    hasActiveFilters,
} = useListingTable<SectionSize, SectionSizeSort>({
    data: () => props.sectionSizes,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: sectionSizesRoute.url(),
    reloadProps: ['sectionSizes'],
    additionalFilters: () => ({
        section_id: filterSectionId.value !== 'all'
            ? filterSectionId.value
            : undefined,
        stock: stockFilter.value ?? undefined,
    }),
    onClearFilters: () => {
        filterSectionId.value = 'all';
        stockFilter.value = null;
    },
});

const hasAnyActiveFilters = computed(
    () =>
        hasActiveFilters.value ||
        filterSectionId.value !== 'all' ||
        stockFilter.value !== null,
);

const activeFilterCount = computed(
    () =>
        Number(filterSectionId.value !== 'all') +
        Number(stockFilter.value !== null),
);

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
    noRecords: {
        title: 'No section sizes yet',
        description: 'Get started by adding your first section size.',
        actionLabel: sectionSizePage.createLabel || 'Add section size',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: sectionSizePage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: sectionSizesImportRoute().url,
        shortcut: ACTION_SHORTCUTS.import.label,
    },
    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        shortcut: ACTION_SHORTCUTS.resetPreferences.label,
        separatorBefore: true,
    },
]);

useActionShortcuts(
    computed(() => ({
        import: {
            handler: () => router.visit(sectionSizesImportRoute().url),
        },
        resetPreferences: {
            handler: resetPreferences,
        },
    })),
);

const handleDeleteSelected = (): void => {
    deleteSelected(bulkDestroy().url);
};

const statistics = computed<ListingStatItem[]>(() => [
    {
        label: 'Total Sizes',
        value: props.statistics?.total ?? 0,
        icon: Database,
        variant: 'primary',
        description: 'Section size records',
    },
    {
        label: 'Sections',
        value: props.statistics?.total_sections ?? 0,
        icon: Layers,
        variant: 'info',
        description: 'Sections with sizes',
    },
    {
        label: 'Total Stock',
        value: props.statistics?.total_stock ?? 0,
        icon: Package,
        variant: 'default',
        description: 'Total available quantity',
    },
    {
        label: 'In Stock',
        value: props.statistics?.in_stock ?? 0,
        icon: CheckCircle,
        variant: 'success',
        description: 'Sizes with stock available',
    },
    {
        label: 'Low Stock',
        value: props.statistics?.low_stock ?? 0,
        icon: TriangleAlert,
        variant: 'warning',
        description: `Quantity at or below ${props.low_stock_threshold}`,
        clickable: true,
        active: stockFilter.value === 'low_stock',
    },
    {
        label: 'Out of Stock',
        value: props.statistics?.out_of_stock ?? 0,
        icon: CircleX,
        variant: 'destructive',
        description: 'Sizes with no stock',
        clickable: true,
        active: stockFilter.value === 'out_of_stock',
    },
]);

const handleStatClick = (item: ListingStatItem): void => {
    switch (item.label) {
        case 'Low Stock':
            stockFilter.value =
                stockFilter.value === 'low_stock' ? null : 'low_stock';
            break;

        case 'Out of Stock':
            stockFilter.value =
                stockFilter.value === 'out_of_stock' ? null : 'out_of_stock';
            break;
    }
};
</script>

<template>
    <ListingLayout
        :description="sectionSizePage.description"
        :title="sectionSizePage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="sectionSizePage.createLabel"
                :create-url="create().url"
                :resource-label="sectionSizePage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #stats>
            <Deferred data="statistics">
                <template #fallback>
                    <ListingStats
                        :columns="4"
                        :loading="true"
                        :skeleton-count="6"
                    />
                </template>

                <ListingStats
                    :items="statistics"
                    :columns="4"
                    @stat-click="handleStatClick"
                />
            </Deferred>
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :active-filter-count="activeFilterCount"
                :clear-button-label="sectionSizePage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :search-button-label="sectionSizePage.searchButtonLabel"
                :search-placeholder="sectionSizePage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppSectionSelect
                            :model-value="filterSectionId === 'all' ? null : filterSectionId"
                            :placeholder="sectionSizePage.sectionPlaceholder"
                            clearable
                            @update:model-value="handleSectionChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppStockFilterSelect
                            :model-value="stockFilter"
                            clearable
                            placeholder="All stock levels"
                            @update:model-value="handleStockChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="sectionSizes">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="sectionSizePage.emptyText"
                        :item-label="sectionSizePage.itemLabel"
                        :loading="true"
                        :page-index="0"
                        :page-size="filters.perPage"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="0"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="sectionSizes"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="sectionSizePage.emptyText"
                    :item-label="sectionSizePage.itemLabel"
                    :page-index="sectionSizes.meta.current_page - 1"
                    :page-size="sectionSizes.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :row-highlight="getQuantityRowHighlight"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="sectionSizes.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
