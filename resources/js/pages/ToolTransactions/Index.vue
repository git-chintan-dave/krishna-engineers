<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppToolSelect from '@/components/selects/AppToolSelect.vue';
import AppToolTransactionTypeSelect from '@/components/selects/AppToolTransactionTypeSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';

import { toolTransactionPage } from '@/config/toolTransactions';

import {
    bulkDestroy,
    create,
    destroy,
    index as toolTransactionsRoute,
} from '@/routes/tool-transactions';

import { createToolTransactionColumns } from '@/table/toolTransactions';

import type {
    ToolTransaction,
    ToolTransactionType,
} from '@/types/toolTransaction';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type ToolTransactionSort =
    | 'created_at'
    | 'quantity'
    | 'type';

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
    links?: unknown;
};

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    transactions: ToolTransactionPagination;
    filters: ToolTransactionFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleToolChange = (value: number | null) => {
    filterToolId.value = value ?? 'all';
};

const handleTypeChange = (value: string | null) => {
    filterType.value =
        (value as ToolTransactionType | null) ?? 'all';
};

const handleFromUserChange = (value: number | null) => {
    filterFromUserId.value = value ?? 'all';
};

const handleToUserChange = (value: number | null) => {
    filterToUserId.value = value ?? 'all';
};

const handlePartChange = (value: number | null) => {
    filterPartId.value = value ?? 'all';
};

const handleSetupChange = (value: number | null) => {
    filterSetupId.value = value ?? 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createToolTransactionColumns(
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
    ToolTransaction,
    ToolTransactionSort
>({
    data: () => props.transactions,

    columns,

    filters: {
        search: props.filters.search ?? '',
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: toolTransactionsRoute.url(),

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
| Clear Filters
|--------------------------------------------------------------------------
*/

const handleClearFilters = () => {
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
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="toolTransactionPage.title"
        :description="toolTransactionPage.description"
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
                    toolTransactionPage.createLabel
                "
                :selected-count="selectedCount"
                :resource-label="
                    toolTransactionPage.resourceLabel
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
                    toolTransactionPage.searchPlaceholder
                "
                :search-button-label="
                    toolTransactionPage.searchButtonLabel
                "
                :clear-button-label="
                    toolTransactionPage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterToolId === 'all' &&
                    filterType === 'all' &&
                    filterFromUserId === 'all' &&
                    filterToUserId === 'all' &&
                    filterPartId === 'all' &&
                    filterSetupId === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <!-- Tool -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppToolSelect
                            :model-value="
                                filterToolId === 'all'
                                    ? null
                                    : filterToolId
                            "
                            :placeholder="
                                toolTransactionPage.toolPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleToolChange
                            "
                        />
                    </div>

                    <!-- Transaction Type -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppToolTransactionTypeSelect
                            :model-value="
                                filterType === 'all'
                                    ? null
                                    : filterType
                            "
                            :placeholder="
                                toolTransactionPage.typePlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleTypeChange
                            "
                        />
                    </div>

                    <!-- From User -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppEmployeeSelect
                            :model-value="
                                filterFromUserId === 'all'
                                    ? null
                                    : filterFromUserId
                            "
                            :placeholder="
                                toolTransactionPage.fromUserPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleFromUserChange
                            "
                        />
                    </div>

                    <!-- To User -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppEmployeeSelect
                            :model-value="
                                filterToUserId === 'all'
                                    ? null
                                    : filterToUserId
                            "
                            :placeholder="
                                toolTransactionPage.toUserPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleToUserChange
                            "
                        />
                    </div>

                    <!-- Part -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all'
                                    ? null
                                    : filterPartId
                            "
                            :placeholder="
                                toolTransactionPage.partPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handlePartChange
                            "
                        />
                    </div>

                    <!-- Setup -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppSetupSelect
                            :model-value="
                                filterSetupId === 'all'
                                    ? null
                                    : filterSetupId
                            "
                            :placeholder="
                                toolTransactionPage.setupPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="
                                handleSetupChange
                            "
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
                    toolTransactionPage.emptyText
                "
                :item-label="
                    toolTransactionPage.itemLabel
                "
            />
        </template>
    </ListingLayout>
</template>
