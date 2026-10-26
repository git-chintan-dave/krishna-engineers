<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ListingActions, {
    type ActionItem,
} from '@/components/common/ListingActions.vue';
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
import { FileUp, RotateCcw } from '@lucide/vue';
import { view as sectionSizesImportRoute } from '@/routes/section-sizes/import';

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

const handleSectionChange = (value: number | null): void => {
    filterSectionId.value = value ?? 'all';
};

const handleClearFilters = (): void => {
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
    resetPreferences,
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
| Actions
|--------------------------------------------------------------------------
*/

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: sectionSizesImportRoute().url,
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
        :description="sectionSizePage.description"
        :title="sectionSizePage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="sectionSizePage.createLabel"
                :create-url="create().url"
                :resource-label="sectionSizePage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="sectionSizePage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() && filterSectionId === 'all'
                "
                :search-button-label="sectionSizePage.searchButtonLabel"
                :search-placeholder="sectionSizePage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
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
                :columns-count="columns.length"
                :empty-text="sectionSizePage.emptyText"
                :item-label="sectionSizePage.itemLabel"
                :page-index="sectionSizes.meta.current_page - 1"
                :page-size="sectionSizes.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="sectionSizes.meta.total"
            />
        </template>
    </ListingLayout>
</template>
