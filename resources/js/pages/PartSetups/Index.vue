<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { partSetupPage } from '@/config/partSetups';
import {
    bulkDestroy,
    create,
    destroy,
    index as partSetupsRoute,
} from '@/routes/part-setups';
import { createPartSetupColumns } from '@/table/partSetups';
import type {
    PartSetup,
    PartSetupFilters,
    PartSetupSort,
    PartSetupsResponse,
} from '@/types/partSetup';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    partSetups: PartSetupsResponse;
    filters: PartSetupFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: partSetupPage.title,
            resourceUrl: partSetupsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterPart = ref<number | null>(
    props.filters.partId,
);

const filterSetup = ref<number | null>(
    props.filters.setupId,
);

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createPartSetupColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<PartSetup, PartSetupSort>({
    data: () => props.partSetups,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: partSetupsRoute.url(),

    additionalFilters: () => ({
        part_id: filterPart.value ?? undefined,
        setup_id: filterSetup.value ?? undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleClearFilters = () => {
    filterPart.value = null;
    filterSetup.value = null;

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
        :title="partSetupPage.title"
        :description="partSetupPage.description"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="partSetupPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="partSetupPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="
                    partSetupPage.searchPlaceholder
                "
                :search-button-label="
                    partSetupPage.searchButtonLabel
                "
                :clear-button-label="
                    partSetupPage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterPart === null &&
                    filterSetup === null
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <!-- Part Filter -->
                    <div class="w-full shrink-0 sm:w-48">
                        <AppPartSelect
                            v-model="filterPart"
                            :placeholder="
                                partSetupPage.partPlaceholder
                            "
                            clearable
                        />
                    </div>

                    <!-- Setup Filter -->
                    <div class="w-full shrink-0 sm:w-40">
                        <AppSetupSelect
                            v-model="filterSetup"
                            :placeholder="
                                partSetupPage.setupPlaceholder
                            "
                            clearable
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!-- Table -->
        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="partSetups.meta.total"
                :page-index="
                    partSetups.meta.current_page - 1
                "
                :page-size="partSetups.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="partSetupPage.emptyText"
                :item-label="partSetupPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
