<script setup lang="ts">
import {router} from '@inertiajs/vue3';
import {ref} from 'vue';

import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {machinePage} from '@/config/machines';
import {bulkDestroy, create, destroy, index as machinesRoute,} from '@/routes/machines';
import {createMachineColumns} from '@/table/machines';
import type {Machine, MachineFilters, MachineSort, MachinesResponse,} from '@/types/machine';
import type {StatusFilter} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    machines: MachinesResponse;
    filters: MachineFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: machinePage.title,
            resourceUrl: machinesRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterStatus = ref<StatusFilter>(
    props.filters.status,
);

const handleStatusChange = (
    value: string | null,
) => {
    filterStatus.value =
        (value as StatusFilter | null) ?? 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createMachineColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Machine, MachineSort>({
    data: () => props.machines,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: machinesRoute.url(),

    additionalFilters: () => ({
        status:
            filterStatus.value !== 'all'
                ? filterStatus.value
                : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

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
        :title="machinePage.title"
        :description="machinePage.description"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="machinePage.createLabel"
                :selected-count="selectedCount"
                :resource-label="machinePage.resourceLabel"
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
                    machinePage.searchPlaceholder
                "
                :search-button-label="
                    machinePage.searchButtonLabel
                "
                :clear-button-label="
                    machinePage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterStatus === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
                        <AppStatusSelect
                            :model-value="
                                filterStatus === 'all'
                                    ? null
                                    : filterStatus
                            "
                            placeholder="All statuses"
                            clearable
                            @update:model-value="
                                handleStatusChange
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
                :total="machines.meta.total"
                :page-index="
                    machines.meta.current_page - 1
                "
                :page-size="machines.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="machinePage.emptyText"
                :item-label="machinePage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
