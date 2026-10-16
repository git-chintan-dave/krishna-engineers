<script lang="ts" setup>
import {router} from '@inertiajs/vue3';
import {ref} from 'vue';

import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {setupPage} from '@/config/setups';
import {bulkDestroy, create, destroy, index as setupsRoute,} from '@/routes/setups';
import {createSetupColumns} from '@/table/setups';
import type {Setup, SetupFilters, SetupSort, SetupsResponse,} from '@/types/setup';
import type {StatusFilter} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    setups: SetupsResponse;
    filters: SetupFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: setupPage.title,
            resourceUrl: setupsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterStatus = ref<StatusFilter>(props.filters.status);

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createSetupColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Setup, SetupSort>({
    data: () => props.setups,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: setupsRoute.url(),

    additionalFilters: () => ({
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleStatusChange = (value: string | null) => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = () => {
    filterStatus.value = 'all';

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
        :description="setupPage.description"
        :title="setupPage.title"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-label="setupPage.createLabel"
                :create-url="create()"
                :resource-label="setupPage.resourceLabel"
                :selected-count="selectedCount"
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
                :clear-button-label="setupPage.clearButtonLabel"
                :clear-disabled="!searchQuery.trim() && filterStatus === 'all'"
                :search-button-label="setupPage.searchButtonLabel"
                :search-placeholder="setupPage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
                        <AppStatusSelect
                            :model-value="
                                filterStatus === 'all' ? null : filterStatus
                            "
                            clearable
                            placeholder="All statuses"
                            @update:model-value="handleStatusChange"
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
                :columns-count="columns.length"
                :empty-text="setupPage.emptyText"
                :item-label="setupPage.itemLabel"
                :page-index="setups.meta.current_page - 1"
                :page-size="setups.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="setups.meta.total"
            />
        </template>
    </ListingLayout>
</template>
