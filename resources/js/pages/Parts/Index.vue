<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { partPage } from '@/config/parts';
import {
    bulkDestroy,
    create,
    destroy,
    index as partsRoute,
} from '@/routes/parts';
import { createPartColumns } from '@/table/parts';
import type { Part, PartFilters, PartSort, PartsResponse } from '@/types/part';
import type { StatusFilter } from '@/types/listing';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    parts: PartsResponse;
    filters: PartFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: partPage.title,
            resourceUrl: partsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterSectionId = ref<number | 'all'>(props.filters.sectionId);

const filterStatus = ref<StatusFilter>(props.filters.status);

const handleSectionChange = (value: number | null) => {
    filterSectionId.value = value ?? 'all';
};

const handleStatusChange = (value: string | null) => {
    filterSectionId.value = 'all';

    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = () => {
    filterSectionId.value = 'all';
    filterStatus.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createPartColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Part, PartSort>({
    data: () => props.parts,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: partsRoute.url(),

    additionalFilters: () => ({
        section_id:
            filterSectionId.value !== 'all' ? filterSectionId.value : undefined,
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
    }),
});

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
    <ListingLayout :description="partPage.description" :title="partPage.title">
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :create-label="partPage.createLabel"
                :create-url="create()"
                :resource-label="partPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="partPage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterSectionId === 'all' &&
                    filterStatus === 'all'
                "
                :search-button-label="partPage.searchButtonLabel"
                :search-placeholder="partPage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="partPage.sectionPlaceholder"
                            clearable
                            @update:model-value="handleSectionChange"
                        />
                    </div>
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

        <!-- Table -->
        <template #table>
            <DataTable
                :columns-count="columns.length"
                :empty-text="partPage.emptyText"
                :item-label="partPage.itemLabel"
                :page-index="parts.meta.current_page - 1"
                :page-size="parts.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="parts.meta.total"
            />
        </template>
    </ListingLayout>
</template>
