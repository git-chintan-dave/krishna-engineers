<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {sectionPage} from '@/config/sections';

import {bulkDestroy, create, destroy, index as sectionsRoute,} from '@/routes/sections';
import {view as sectionsImportRoute} from '@/routes/sections/import';

import {createSectionColumns} from '@/table/sections';

import type {Section, SectionFilters, SectionSort, SectionsResponse,} from '@/types/section';
import type {StatusFilter} from '@/types/listing';

const props = defineProps<{
    sections?: SectionsResponse;
    hasAnyRecords: boolean;
    filters: SectionFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: sectionPage.title,
            resourceUrl: sectionsRoute().url,
        }),
    },
});

const filterStatus = ref<StatusFilter>(props.filters.status);

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

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
    hasActiveFilters,
} = useListingTable<Section, SectionSort>({
    data: () => props.sections,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: sectionsRoute.url(),
    additionalFilters: () => ({
        status: filterStatus.value !== 'all'
            ? filterStatus.value
            : undefined,
    }),
    onClearFilters: () => {
        filterStatus.value = 'all';
    },
});

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No sections yet',
        description: 'Get started by adding your first section.',
        actionLabel: sectionPage.createLabel || 'Add section',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: sectionPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

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

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="sectionPage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="sectionPage.searchButtonLabel"
                :search-placeholder="sectionPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="filterStatus === 'all' ? null : filterStatus"
                            clearable
                            placeholder="All statuses"
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="sections">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="sectionPage.emptyText"
                        :item-label="sectionPage.itemLabel"
                        :page-index="0"
                        :page-size="filters.perPage"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="0"
                        :table="table"
                        :total="0"
                        :loading="true"
                    />
                </template>

                <DataTable
                    v-if="sections"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="sectionPage.emptyText"
                    :item-label="sectionPage.itemLabel"
                    :page-index="sections.meta.current_page - 1"
                    :page-size="sections.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="sections.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
