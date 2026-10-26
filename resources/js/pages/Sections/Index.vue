<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ListingActions, {
    type ActionItem,
} from '@/components/common/ListingActions.vue';
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
import { FileUp, RotateCcw } from '@lucide/vue';
import { view as sectionsImportRoute } from '@/routes/sections/import';

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

const filterStatus = ref<StatusFilter>(props.filters.status);

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = (): void => {
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
    resetPreferences,
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
        status: filterStatus.value !== 'all' ? filterStatus.value : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: sectionsImportRoute().url,
    },
    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        separatorBefore: true,
    },
]);

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = (): void => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :description="sectionPage.description"
        :title="sectionPage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="sectionPage.createLabel"
                :create-url="create().url"
                :resource-label="sectionPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="sectionPage.clearButtonLabel"
                :clear-disabled="!searchQuery.trim() && filterStatus === 'all'"
                :search-button-label="sectionPage.searchButtonLabel"
                :search-placeholder="sectionPage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
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
                :empty-text="sectionPage.emptyText"
                :item-label="sectionPage.itemLabel"
                :page-index="sections.meta.current_page - 1"
                :page-size="sections.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="sections.meta.total"
            />
        </template>
    </ListingLayout>
</template>
