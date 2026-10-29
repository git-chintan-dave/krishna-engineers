<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';
import {computed, ref} from 'vue';

import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {sectionSizePage} from '@/config/sectionSizes';

import {bulkDestroy, create, destroy, index as sectionSizesRoute} from '@/routes/section-sizes';
import {view as sectionSizesImportRoute} from '@/routes/section-sizes/import';

import {createSectionSizeColumns} from '@/table/sectionSizes';

import {getQuantityRowHighlight} from '@/lib/row-highlights';

import type {SectionSize, SectionSizeFilters, SectionSizeSort, SectionSizesResponse,} from '@/types/sectionSize';

const props = defineProps<{
    sectionSizes?: SectionSizesResponse;
    hasAnyRecords: boolean;
    filters: SectionSizeFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: sectionSizePage.title,
            resourceUrl: sectionSizesRoute().url,
        }),
    },
});

const filterSectionId = ref<number | 'all'>(props.filters.sectionId);

const handleSectionChange = (value: number | null): void => {
    filterSectionId.value = value ?? 'all';
};

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
    hasActiveFilters,
} = useListingTable<SectionSize, SectionSizeSort>({
    data: () => props.sectionSizes,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: sectionSizesRoute.url(),
    additionalFilters: () => ({
        section_id: filterSectionId.value !== 'all'
            ? filterSectionId.value
            : undefined,
    }),
    onClearFilters: () => {
        filterSectionId.value = 'all';
    },
});

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No section sizes yet',
        description: 'Get started by adding your first section size.',
        actionLabel: sectionSizePage.createLabel || 'Add section size',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: sectionSizePage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: sectionSizesImportRoute().url,
        shortcut: ACTION_SHORTCUTS.import.label,
    },
    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        shortcut: ACTION_SHORTCUTS.resetPreferences.label,
        separatorBefore: true,
    },
]);

useActionShortcuts(
    computed(() => ({
        import: {
            handler: () => router.visit(sectionSizesImportRoute().url),
        },
        resetPreferences: {
            handler: resetPreferences,
        },
    })),
);

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

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="sectionSizePage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="sectionSizePage.searchButtonLabel"
                :search-placeholder="sectionSizePage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppSectionSelect
                            :model-value="filterSectionId === 'all' ? null : filterSectionId"
                            :placeholder="sectionSizePage.sectionPlaceholder"
                            clearable
                            @update:model-value="handleSectionChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="sectionSizes">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="sectionSizePage.emptyText"
                        :item-label="sectionSizePage.itemLabel"
                        :loading="true"
                        :page-index="0"
                        :page-size="filters.perPage"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="0"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="sectionSizes"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="sectionSizePage.emptyText"
                    :item-label="sectionSizePage.itemLabel"
                    :page-index="sectionSizes.meta.current_page - 1"
                    :page-size="sectionSizes.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :row-highlight="getQuantityRowHighlight"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="sectionSizes.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
