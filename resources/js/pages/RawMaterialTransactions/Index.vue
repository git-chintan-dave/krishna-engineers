<script setup lang="ts">
import {router} from '@inertiajs/vue3';
import {ref} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppRawMaterialTransactionTypeSelect from '@/components/selects/AppRawMaterialTransactionTypeSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
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
    transactions: RawMaterialTransactionsResponse;
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
            resourceUrl:
            rawMaterialTransactionsRoute().url,
        }),
    },
});

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

const filterType = ref(
    props.filters.type ?? 'all',
);

const filterUserId = ref<number | 'all'>(
    props.filters.userId ?? 'all',
);

const filterFromDate = ref(
    props.filters.fromDate ?? '',
);

const filterToDate = ref(
    props.filters.toDate ?? '',
);

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createRawMaterialTransactionColumns(
    (id) => {
        router.delete(destroy(id).url);
    },
);

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
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

        from_date:
            filterFromDate.value || undefined,

        to_date:
            filterToDate.value || undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleSectionChange = (value: number | null) => {
    filterSectionId.value = value ?? 'all';

    // Section Size depends on Section.
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
    filterFromDate.value = '';
    filterToDate.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="rawMaterialTransactionPage.title"
        :description="rawMaterialTransactionPage.description"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="
                    rawMaterialTransactionPage.createLabel
                "
                :selected-count="selectedCount"
                :resource-label="
                    rawMaterialTransactionPage.resourceLabel
                "
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        -->

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="
                    rawMaterialTransactionPage.searchPlaceholder
                "
                :search-button-label="
                    rawMaterialTransactionPage.searchButtonLabel
                "
                :clear-button-label="
                    rawMaterialTransactionPage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterSectionId === 'all' &&
                    filterSectionSizeId === 'all' &&
                    filterType === 'all' &&
                    filterUserId === 'all' &&
                    !filterFromDate &&
                    !filterToDate
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <!-- Section -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="
                                rawMaterialTransactionPage.sectionPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleSectionChange
                            "
                        />
                    </div>

                    <!-- Section Size -->
                    <div class="w-full shrink-0 sm:w-48">
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
                            :placeholder="
                                rawMaterialTransactionPage.sectionSizePlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleSectionSizeChange
                            "
                        />
                    </div>

                    <!-- Transaction Type -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppRawMaterialTransactionTypeSelect
                            :model-value="
                                filterType === 'all'
                                    ? null
                                    : filterType
                            "
                            :placeholder="
                                rawMaterialTransactionPage.typePlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleTypeChange
                            "
                        />
                    </div>

                    <!-- Employee -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all'
                                    ? null
                                    : filterUserId
                            "
                            placeholder="Select Employee"
                            searchable
                            clearable
                            @update:model-value="
                                handleUserChange
                            "
                        />
                    </div>

                    <!-- From Date -->
                    <div class="w-full shrink-0 sm:w-36">
                        <AppInput
                            v-model="filterFromDate"
                            type="date"
                        />
                    </div>

                    <!-- To Date -->
                    <div class="w-full shrink-0 sm:w-36">
                        <AppInput
                            v-model="filterToDate"
                            type="date"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        -->

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="transactions.meta.total"
                :page-index="
                    transactions.meta.current_page - 1
                "
                :page-size="
                    transactions.meta.per_page
                "
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="
                    rawMaterialTransactionPage.emptyText
                "
                :item-label="
                    rawMaterialTransactionPage.itemLabel
                "
            />
        </template>
    </ListingLayout>
</template>
