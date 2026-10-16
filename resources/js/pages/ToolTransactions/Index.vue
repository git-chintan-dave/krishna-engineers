<script setup lang="ts">
import {router} from '@inertiajs/vue3';
import {ref} from 'vue';

import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppToolSelect from '@/components/selects/AppToolSelect.vue';
import AppToolTransactionTypeSelect from '@/components/selects/AppToolTransactionTypeSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
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
    transactions: ToolTransactionPagination;
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

const handleToolChange = (value: number | null) => {
    filterToolId.value = value ?? 'all';
};

const handleTypeChange = (value: string | number | null) => {
    filterType.value =
        typeof value === 'string'
            ? (value as ToolTransactionType)
            : 'all';
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

const columns = createToolTransactionColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<ToolTransaction, ToolTransactionSort>({
    data: () => props.transactions,
    columns,
    filters: {
        search: props.filters.search ?? '',
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: toolTransactionsRoute.url(),
    searchable:false,
    additionalFilters: () => ({
        tool_id:
            filterToolId.value === 'all'
                ? undefined
                : filterToolId.value,

        type:
            filterType.value === 'all'
                ? undefined
                : filterType.value,

        from_user_id:
            filterFromUserId.value === 'all'
                ? undefined
                : filterFromUserId.value,

        to_user_id:
            filterToUserId.value === 'all'
                ? undefined
                : filterToUserId.value,

        part_id:
            filterPartId.value === 'all'
                ? undefined
                : filterPartId.value,

        setup_id:
            filterSetupId.value === 'all'
                ? undefined
                : filterSetupId.value,
    }),
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const hasAdditionalFilters = () =>
    filterToolId.value !== 'all' ||
    filterType.value !== 'all' ||
    filterFromUserId.value !== 'all' ||
    filterToUserId.value !== 'all' ||
    filterPartId.value !== 'all' ||
    filterSetupId.value !== 'all';

const handleClearFilters = () => {
    filterToolId.value = 'all';
    filterType.value = 'all';
    filterFromUserId.value = 'all';
    filterToUserId.value = 'all';
    filterPartId.value = 'all';
    filterSetupId.value = 'all';

    clearFilters();
};

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="toolTransactionPage.title"
        :description="toolTransactionPage.description"
    >
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="toolTransactionPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="toolTransactionPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                :searchable="false"
                v-model:search="searchQuery"
                :search-placeholder="toolTransactionPage.searchPlaceholder"
                :search-button-label="toolTransactionPage.searchButtonLabel"
                :clear-button-label="toolTransactionPage.clearButtonLabel"
                :clear-disabled="
        !searchQuery.trim() && !hasAdditionalFilters()
    "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
                        <AppToolSelect
                            :model-value="
                    filterToolId === 'all'
                        ? null
                        : filterToolId
                "
                            :placeholder="toolTransactionPage.toolPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleToolChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppToolTransactionTypeSelect
                            :model-value="
                    filterType === 'all'
                        ? null
                        : filterType
                "
                            :placeholder="toolTransactionPage.typePlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleTypeChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppEmployeeSelect
                            :model-value="
                    filterFromUserId === 'all'
                        ? null
                        : filterFromUserId
                "
                            :placeholder="toolTransactionPage.fromUserPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleFromUserChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppEmployeeSelect
                            :model-value="
                    filterToUserId === 'all'
                        ? null
                        : filterToUserId
                "
                            :placeholder="toolTransactionPage.toUserPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleToUserChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppPartSelect
                            :model-value="
                    filterPartId === 'all'
                        ? null
                        : filterPartId
                "
                            :placeholder="toolTransactionPage.partPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppSetupSelect
                            :model-value="
                    filterSetupId === 'all'
                        ? null
                        : filterSetupId
                "
                            :part-id="
                    filterPartId === 'all'
                        ? null
                        : filterPartId
                "
                            :placeholder="toolTransactionPage.setupPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleSetupChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="transactions.meta.total"
                :page-index="transactions.meta.current_page - 1"
                :page-size="transactions.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="toolTransactionPage.emptyText"
                :item-label="toolTransactionPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
