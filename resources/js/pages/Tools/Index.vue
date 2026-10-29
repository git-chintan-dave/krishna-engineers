<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Ban, CheckCircle, CircleX, Database, FileUp, Package, RotateCcw, SearchX, TriangleAlert,} from '@lucide/vue';

import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import ListingStats, {type ListingStatItem} from '@/components/common/ListingStats.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppStockFilterSelect from '@/components/selects/AppStockFilterSelect.vue';
import AppToolTypeSelect from '@/components/selects/AppToolTypeSelect.vue';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {toolPage} from '@/config/tools';
import {bulkDestroy, create, destroy, index as toolsRoute} from '@/routes/tools';
import {view as toolsImportRoute} from '@/routes/tools/import';
import {getQuantityRowHighlight} from '@/lib/row-highlights';
import {createToolColumns} from '@/table/tools';

import type {StatusFilter} from '@/types/listing';
import type {StockFilter, Tool, ToolFilters, ToolSort, ToolsResponse, ToolStatistics, ToolType,} from '@/types/tool';

const props = defineProps<{
    tools?: ToolsResponse;
    hasAnyRecords: boolean;
    filters: ToolFilters;
    statistics?: ToolStatistics;
    low_stock_threshold: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: toolPage.title,
            resourceUrl: toolsRoute().url,
        }),
    },
});

const filterType = ref<ToolType | 'all'>(props.filters.type);
const filterStatus = ref<StatusFilter>(props.filters.status);
const stockFilter = ref<StockFilter | null>(props.filters.stock);

const handleTypeChange = (value: string | null): void => {
    filterType.value = (value as ToolType | null) ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleStockChange = (value: StockFilter | null): void => {
    stockFilter.value = value;
};

const columns = createToolColumns((id) => {
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
} = useListingTable<Tool, ToolSort>({
    data: () => props.tools,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: toolsRoute.url(),
    reloadProps: ['tools'],
    additionalFilters: () => ({
        type: filterType.value !== 'all' ? filterType.value : undefined,
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
        stock: stockFilter.value ?? undefined,
    }),
    onClearFilters: () => {
        filterType.value = 'all';
        filterStatus.value = 'all';
        stockFilter.value = null;
    },
});

const hasAnyActiveFilters = computed(
    () =>
        hasActiveFilters.value ||
        filterType.value !== 'all' ||
        filterStatus.value !== 'all' ||
        stockFilter.value !== null,
);

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
    noRecords: {
        title: 'No tools yet',
        description: 'Get started by adding your first tool.',
        actionLabel: toolPage.createLabel || 'Add tool',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: toolPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: toolsImportRoute().url,
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
            handler: () => router.visit(toolsImportRoute().url),
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
        description: 'No stock available',
        clickable: true,
        active: stockFilter.value === 'out_of_stock',
    },
    {
        label: 'Active',
        value: props.statistics?.active ?? 0,
        icon: CheckCircle,
        variant: 'success',
        description: 'Active tools',
        clickable: true,
        active: filterStatus.value === 'active',
    },
    {
        label: 'Inactive',
        value: props.statistics?.inactive ?? 0,
        icon: Ban,
        variant: 'default',
        description: 'Inactive tools',
        clickable: true,
        active: filterStatus.value === 'inactive',
    },
    {
        label: 'Insert',
        value: props.statistics?.insert ?? 0,
        icon: Package,
        variant: 'primary',
        description: 'Insert tools',
        clickable: true,
        active: filterType.value === 'insert',
    },
    {
        label: 'Drill',
        value: props.statistics?.drill ?? 0,
        icon: Package,
        variant: 'info',
        description: 'Drill tools',
        clickable: true,
        active: filterType.value === 'drill',
    },
    {
        label: 'Endmill',
        value: props.statistics?.endmill ?? 0,
        icon: Package,
        variant: 'success',
        description: 'Endmill tools',
        clickable: true,
        active: filterType.value === 'endmill',
    },
    {
        label: 'Special Tools',
        value: props.statistics?.special_tools ?? 0,
        icon: Package,
        variant: 'default',
        description: 'Special tools',
        clickable: true,
        active: filterType.value === 'special_tools',
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

        case 'Active':
            filterStatus.value =
                filterStatus.value === 'active' ? 'all' : 'active';
            break;

        case 'Inactive':
            filterStatus.value =
                filterStatus.value === 'inactive' ? 'all' : 'inactive';
            break;

        case 'Insert':
        case 'Drill':
        case 'Endmill':
        case 'Special Tools': {
            const type = item.label.toLowerCase().replace(' ', '_') as ToolType;

            filterType.value =
                filterType.value === type ? 'all' : type;
            break;
        }
    }
};
</script>

<template>
    <ListingLayout
        :description="toolPage.description"
        :title="toolPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="toolPage.createLabel"
                :create-url="create().url"
                :resource-label="toolPage.resourceLabel"
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
                        :skeleton-count="8"
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
                :active-filter-count="
            Number(filterType !== 'all') +
            Number(filterStatus !== 'all') +
            Number(stockFilter !== null)
        "
                :clear-button-label="toolPage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :search-button-label="toolPage.searchButtonLabel"
                :search-placeholder="toolPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppToolTypeSelect
                            :model-value="filterType === 'all' ? null : filterType"
                            clearable
                            placeholder="All types"
                            @update:model-value="handleTypeChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="filterStatus === 'all' ? null : filterStatus"
                            clearable
                            placeholder="All statuses"
                            @update:model-value="handleStatusChange"
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
            <Deferred data="tools">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="toolPage.emptyText"
                        :item-label="toolPage.itemLabel"
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
                    v-if="tools"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="toolPage.emptyText"
                    :item-label="toolPage.itemLabel"
                    :page-index="tools.meta.current_page - 1"
                    :page-size="tools.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :row-highlight="getQuantityRowHighlight"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="tools.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
