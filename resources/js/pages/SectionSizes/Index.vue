<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { sectionSizePage } from '@/config/sectionSizes';
import {
    bulkDestroy,
    create,
    destroy,
    index as sectionSizesRoute,
} from '@/routes/section-sizes';
import { createSectionSizeColumns } from '@/table/sectionSizes';
import type {
    SectionSize,
    SectionSizeFilters,
    SectionSizeSort,
    SectionSizesResponse,
} from '@/types/sectionSize';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    sectionSizes: SectionSizesResponse;
    filters: SectionSizeFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: sectionSizePage.title,
            resourceUrl: sectionSizesRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterSectionId = ref<number | 'all'>(props.filters.sectionId);

const handleSectionChange = (value: number | null) => {
    filterSectionId.value = value ?? 'all';
};

const handleClearFilters = () => {
    filterSectionId.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createSectionSizeColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<SectionSize, SectionSizeSort>({
    data: () => props.sectionSizes,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: sectionSizesRoute.url(),

    additionalFilters: () => ({
        section_id:
            filterSectionId.value !== 'all' ? filterSectionId.value : undefined,
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
    <ListingLayout
        :title="sectionSizePage.title"
        :description="sectionSizePage.description"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="sectionSizePage.createLabel"
                :selected-count="selectedCount"
                :resource-label="sectionSizePage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="sectionSizePage.searchPlaceholder"
                :search-button-label="sectionSizePage.searchButtonLabel"
                :clear-button-label="sectionSizePage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() && filterSectionId === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="sectionSizePage.sectionPlaceholder"
                            clearable
                            @update:model-value="handleSectionChange"
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
                :total="sectionSizes.meta.total"
                :page-index="sectionSizes.meta.current_page - 1"
                :page-size="sectionSizes.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="sectionSizePage.emptyText"
                :item-label="sectionSizePage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
