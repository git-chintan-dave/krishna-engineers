<script lang="ts" setup>
import {Deferred, router, usePage} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, RotateCcw, SearchX} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppRawMaterialTransactionTypeSelect from '@/components/selects/AppRawMaterialTransactionTypeSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';
import {rawMaterialTransactionPage} from '@/config/rawMaterialTransactions';
import {bulkDestroy, create, destroy, index as rawMaterialTransactionsRoute,} from '@/routes/raw-material-transactions';
import {createRawMaterialTransactionColumns} from '@/table/rawMaterialTransactions';
import type {
    RawMaterialTransaction,
    RawMaterialTransactionFilters,
    RawMaterialTransactionSort,
    RawMaterialTransactionsResponse,
} from '@/types/rawMaterialTransaction';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    transactions?: RawMaterialTransactionsResponse;
    hasAnyRecords: boolean;
    filters: RawMaterialTransactionFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: rawMaterialTransactionPage.title,
            resourceUrl: rawMaterialTransactionsRoute().url,
        }),
    },
});

const page = usePage();

const canManage = computed(
    () => page.props.auth.user?.role === 'super_admin',
);

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterSectionId = ref<number | 'all'>(
    props.filters.sectionId ?? 'all',
);

const filterSectionSizeId = ref<number | 'all'>(
    props.filters.sectionSizeId ?? 'all',
);

const filterType = ref(props.filters.type || 'all');

const filterUserId = ref<number | 'all'>(
    props.filters.userId ?? 'all',
);

const filterStartDate = ref(props.filters.startDate ?? '');
const filterEndDate = ref(props.filters.endDate ?? '');

const handleSectionChange = (value: number | null) => {
    filterSectionId.value = value ?? 'all';
    filterSectionSizeId.value = 'all';
};

const handleSectionSizeChange = (value: number | null) => {
    filterSectionSizeId.value = value ?? 'all';
};

const handleTypeChange = (value: string | null) => {
    filterType.value = value ?? 'all';
};

const handleUserChange = (value: number | null) => {
    filterUserId.value = value ?? 'all';
};

const handleClearFilters = () => {
    filterSectionId.value = 'all';
    filterSectionSizeId.value = 'all';
    filterType.value = 'all';
    filterUserId.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createRawMaterialTransactionColumns(
    (uuid) => {
        if (canManage.value) {
            router.delete(destroy(uuid).url);
        }
    },
    canManage.value,
    canManage.value,
);

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
    hasActiveFilters,
} = useListingTable<
    RawMaterialTransaction,
    RawMaterialTransactionSort
>({
    data: () => props.transactions,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: rawMaterialTransactionsRoute.url(),
    searchable: false,
    additionalFilters: () => ({
        section_id:
            filterSectionId.value !== 'all'
                ? filterSectionId.value
                : undefined,
        section_size_id:
            filterSectionSizeId.value !== 'all'
                ? filterSectionSizeId.value
                : undefined,
        type:
            filterType.value !== 'all'
                ? filterType.value
                : undefined,
        user_id:
            filterUserId.value !== 'all'
                ? filterUserId.value
                : undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
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
        filterSectionId.value !== 'all' ||
        filterSectionSizeId.value !== 'all' ||
        filterType.value !== 'all' ||
        filterUserId.value !== 'all' ||
        Boolean(filterStartDate.value) ||
        Boolean(filterEndDate.value),
);

const activeFilterCount = computed(
    () =>
        Number(filterSectionId.value !== 'all') +
        Number(filterSectionSizeId.value !== 'all') +
        Number(filterType.value !== 'all') +
        Number(filterUserId.value !== 'all') +
        Number(Boolean(filterStartDate.value)) +
        Number(Boolean(filterEndDate.value)),
);

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
    noRecords: {
        title: 'No transactions yet',
        description: 'Create a raw material transaction to get started.',
        actionLabel: canManage.value
            ? rawMaterialTransactionPage.createLabel
            : undefined,
        icon: Database,
        onAction: canManage.value
            ? () => router.visit(create().url)
            : undefined,
    },
    noResults: {
        title: 'No transactions found',
        description: 'Try adjusting your search or filters.',
        actionLabel: rawMaterialTransactionPage.clearButtonLabel,
        icon: SearchX,
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

const handleDeleteSelected = () => {
    if (canManage.value && selectedCount.value > 0) {
        deleteSelected(bulkDestroy().url);
    }
};
</script>

<template>
    <ListingLayout
        :description="rawMaterialTransactionPage.description"
        :title="rawMaterialTransactionPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="rawMaterialTransactionPage.createLabel"
                :create-url="canManage ? create().url : undefined"
                :resource-label="rawMaterialTransactionPage.resourceLabel"
                :selected-count="canManage ? selectedCount : 0"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :active-filter-count="activeFilterCount"
                :clear-button-label="
                    rawMaterialTransactionPage.clearButtonLabel
                "
                :clear-disabled="!hasAnyActiveFilters"
                :search-button-label="
                    rawMaterialTransactionPage.searchButtonLabel
                "
                :search-placeholder="
                    rawMaterialTransactionPage.searchPlaceholder
                "
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            clearable
                            placeholder="All Sections"
                            searchable
                            @update:model-value="handleSectionChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSectionSizeSelect
                            :model-value="
                                filterSectionSizeId === 'all'
                                    ? null
                                    : filterSectionSizeId
                            "
                            :section-id="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            clearable
                            placeholder="All Section Sizes"
                            searchable
                            @update:model-value="handleSectionSizeChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppRawMaterialTransactionTypeSelect
                            :model-value="
                                filterType === 'all' ? null : filterType
                            "
                            :placeholder="
                                rawMaterialTransactionPage.typePlaceholder
                            "
                            clearable
                            searchable
                            @update:model-value="handleTypeChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterStartDate"
                            aria-label="From date"
                            type="date"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterEndDate"
                            aria-label="To date"
                            type="date"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="transactions">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :item-label="rawMaterialTransactionPage.itemLabel"
                        :loading="true"
                        :page-index="0"
                        :page-size="filters.perPage ?? 10"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="canManage ? selectedCount : 0"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="transactions"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :item-label="rawMaterialTransactionPage.itemLabel"
                    :page-index="transactions.meta.current_page - 1"
                    :page-size="transactions.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="canManage ? selectedCount : 0"
                    :table="table"
                    :total="transactions.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
