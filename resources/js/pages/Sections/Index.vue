<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { sectionPage } from '@/config/sections';
import {
    bulkDestroy,
    create,
    destroy,
    index as sectionsRoute,
} from '@/routes/sections';
import { createSectionColumns } from '@/table/sections';
import type {
    Section,
    SectionFilters,
    SectionSort,
    SectionsResponse,
} from '@/types/section';
import type { StatusFilter } from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    sections: SectionsResponse;
    filters: SectionFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: sectionPage.title,
            resourceUrl: sectionsRoute().url,
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

const handleClearFilters = () => {
    filterStatus.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createSectionColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Section, SectionSort>({
    data: () => props.sections,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: sectionsRoute.url(),

    additionalFilters: () => ({
        status:
            filterStatus.value !== 'all'
                ? filterStatus.value
                : undefined,
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
        :title="sectionPage.title"
        :description="sectionPage.description"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="sectionPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="sectionPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="
                    sectionPage.searchPlaceholder
                "
                :search-button-label="
                    sectionPage.searchButtonLabel
                "
                :clear-button-label="
                    sectionPage.clearButtonLabel
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

        <!-- Table -->
        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="sections.meta.total"
                :page-index="
                    sections.meta.current_page - 1
                "
                :page-size="sections.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="sectionPage.emptyText"
                :item-label="sectionPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
