<script lang="ts" setup>
import {router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {RotateCcw} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {partSetupPage} from '@/config/partSetups';
import {bulkDestroy, create, destroy, index as partSetupsRoute,} from '@/routes/part-setups';
import {createPartSetupColumns} from '@/table/partSetups';
import type {PartSetup, PartSetupFilters, PartSetupSort, PartSetupsResponse,} from '@/types/partSetup';

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

const filterPart = ref<number | null>(props.filters.partId);
const filterSetup = ref<number | null>(props.filters.setupId);

const handleClearFilters = () => {
    filterPart.value = null;
    filterSetup.value = null;

    clearFilters();
};

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
    resetPreferences,
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
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :description="partSetupPage.description"
        :title="partSetupPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="partSetupPage.createLabel"
                :create-url="create().url"
                :resource-label="partSetupPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="partSetupPage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterPart === null &&
                    filterSetup === null
                "
                :search-button-label="partSetupPage.searchButtonLabel"
                :search-placeholder="partSetupPage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppPartSelect
                            v-model="filterPart"
                            :placeholder="partSetupPage.partPlaceholder"
                            clearable
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSetupSelect
                            v-model="filterSetup"
                            :part-id="filterPart"
                            :placeholder="partSetupPage.setupPlaceholder"
                            clearable
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :columns-count="columns.length"
                :empty-text="partSetupPage.emptyText"
                :item-label="partSetupPage.itemLabel"
                :page-index="partSetups.meta.current_page - 1"
                :page-size="partSetups.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="partSetups.meta.total"
            />
        </template>
    </ListingLayout>
</template>
