<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, RotateCcw, SearchX} from '@lucide/vue';

import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppToolSelect from '@/components/selects/AppToolSelect.vue';
import AppToolTransactionTypeSelect from '@/components/selects/AppToolTransactionTypeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';
import {toolTransactionPage} from '@/config/toolTransactions';
import {bulkDestroy, create, destroy, index as toolTransactionsRoute,} from '@/routes/tool-transactions';
import {createToolTransactionColumns} from '@/table/toolTransactions';
import type {ToolTransaction, ToolTransactionType,} from '@/types/toolTransaction';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ToolTransactionSort = 'created_at' | 'quantity' | 'type';

type ToolTransactionFilters = {
    search: string;
    toolId?: number | null;
    type?: ToolTransactionType | null;
    fromUserId?: number | null;
    toUserId?: number | null;
    partId?: number | null;
    setupId?: number | null;
    sort?: ToolTransactionSort;
    direction?: 'asc' | 'desc';
};

type ToolTransactionPagination = {
    data: ToolTransaction[];
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
    };
};

/*
|--------------------------------------------------------------------------
| Props & Layout
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    transactions?: ToolTransactionPagination;
    hasAnyRecords: boolean;
    filters: ToolTransactionFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: toolTransactionPage.title,
            resourceUrl: toolTransactionsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterToolId = ref<number | 'all'>(
    props.filters.toolId ?? 'all',
);

const filterType = ref<ToolTransactionType | 'all'>(
    props.filters.type ?? 'all',
);

const filterFromUserId = ref<number | 'all'>(
    props.filters.fromUserId ?? 'all',
);

const filterToUserId = ref<number | 'all'>(
    props.filters.toUserId ?? 'all',
);

const filterPartId = ref<number | 'all'>(
    props.filters.partId ?? 'all',
);

const filterSetupId = ref<number | 'all'>(
    props.filters.setupId ?? 'all',
);

const handleToolChange = (value: number | null): void => {
    filterToolId.value = value ?? 'all';
};

const handleTypeChange = (value: string | number | null): void => {
    filterType.value =
        typeof value === 'string'
            ? (value as ToolTransactionType)
            : 'all';
};

const handleFromUserChange = (value: number | null): void => {
    filterFromUserId.value = value ?? 'all';
};

const handleToUserChange = (value: number | null): void => {
    filterToUserId.value = value ?? 'all';
};

const handlePartChange = (value: number | null): void => {
    filterPartId.value = value ?? 'all';

    // Clear the setup selection when its part changes.
    filterSetupId.value = 'all';
};

const handleSetupChange = (value: number | null): void => {
    filterSetupId.value = value ?? 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createToolTransactionColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    hasActiveFilters,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
} = useListingTable<ToolTransaction, ToolTransactionSort>({
    data: () => props.transactions,
    columns,
    filters: {
        search: props.filters.search ?? '',
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: toolTransactionsRoute.url(),
    searchable: false,
    additionalFilters: () => ({
        tool_id:
            filterToolId.value !== 'all'
                ? filterToolId.value
                : undefined,
        type:
            filterType.value !== 'all'
                ? filterType.value
                : undefined,
        from_user_id:
            filterFromUserId.value !== 'all'
                ? filterFromUserId.value
                : undefined,
        to_user_id:
            filterToUserId.value !== 'all'
                ? filterToUserId.value
                : undefined,
        part_id:
            filterPartId.value !== 'all'
                ? filterPartId.value
                : undefined,
        setup_id:
            filterSetupId.value !== 'all'
                ? filterSetupId.value
                : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Active Filters
|--------------------------------------------------------------------------
*/

const hasAnyActiveFilters = computed(
    () =>
        hasActiveFilters.value ||
        filterToolId.value !== 'all' ||
        filterType.value !== 'all' ||
        filterFromUserId.value !== 'all' ||
        filterToUserId.value !== 'all' ||
        filterPartId.value !== 'all' ||
        filterSetupId.value !== 'all',
);

const activeFilterCount = computed(
    () =>
        Number(filterToolId.value !== 'all') +
        Number(filterType.value !== 'all') +
        Number(filterFromUserId.value !== 'all') +
        Number(filterToUserId.value !== 'all') +
        Number(filterPartId.value !== 'all') +
        Number(filterSetupId.value !== 'all'),
);

/*
|--------------------------------------------------------------------------
| Clear Filters
|--------------------------------------------------------------------------
*/

const handleClearFilters = (): void => {
    filterToolId.value = 'all';
    filterType.value = 'all';
    filterFromUserId.value = 'all';
    filterToUserId.value = 'all';
    filterPartId.value = 'all';
    filterSetupId.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
    noRecords: {
        icon: Database,
        title: 'No tool transactions yet',
        description: 'Create a tool transaction to get started.',
        actionLabel: toolTransactionPage.createLabel,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        icon: SearchX,
        title: 'No tool transactions found',
        description: 'Try adjusting or clearing your filters.',
        actionLabel: toolTransactionPage.clearButtonLabel,
        onAction: handleClearFilters,
    },
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const actions = computed<ActionItem[]>(() => [
    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        separatorBefore: true,
    },
]);

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = (): void => {
    if (selectedCount.value > 0) {
        deleteSelected(bulkDestroy().url);
    }
};
</script>

<template>
    <ListingLayout
        :description="toolTransactionPage.description"
        :title="toolTransactionPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="toolTransactionPage.createLabel"
                :create-url="create().url"
                :resource-label="toolTransactionPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :active-filter-count="activeFilterCount"
                :clear-button-label="toolTransactionPage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :search-button-label="toolTransactionPage.searchButtonLabel"
                :search-placeholder="toolTransactionPage.searchPlaceholder"
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <AppToolSelect
                        :model-value="
                            filterToolId === 'all' ? null : filterToolId
                        "
                        :placeholder="toolTransactionPage.toolPlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handleToolChange"
                    />

                    <AppToolTransactionTypeSelect
                        :model-value="
                            filterType === 'all' ? null : filterType
                        "
                        :placeholder="toolTransactionPage.typePlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handleTypeChange"
                    />

                    <AppEmployeeSelect
                        :model-value="
                            filterFromUserId === 'all'
                                ? null
                                : filterFromUserId
                        "
                        :placeholder="toolTransactionPage.fromUserPlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handleFromUserChange"
                    />

                    <AppEmployeeSelect
                        :model-value="
                            filterToUserId === 'all'
                                ? null
                                : filterToUserId
                        "
                        :placeholder="toolTransactionPage.toUserPlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handleToUserChange"
                    />

                    <AppPartSelect
                        :model-value="
                            filterPartId === 'all' ? null : filterPartId
                        "
                        :placeholder="toolTransactionPage.partPlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handlePartChange"
                    />

                    <AppSetupSelect
                        :model-value="
                            filterSetupId === 'all' ? null : filterSetupId
                        "
                        :part-id="
                            filterPartId === 'all' ? null : filterPartId
                        "
                        :placeholder="toolTransactionPage.setupPlaceholder"
                        class="w-full min-w-0"
                        clearable
                        searchable
                        @update:model-value="handleSetupChange"
                    />
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="transactions">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="toolTransactionPage.emptyText"
                        :item-label="toolTransactionPage.itemLabel"
                        :loading="true"
                        :page-index="0"
                        :page-size="10"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="selectedCount"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="transactions"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="toolTransactionPage.emptyText"
                    :item-label="toolTransactionPage.itemLabel"
                    :page-index="transactions.meta.current_page - 1"
                    :page-size="transactions.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="transactions.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
