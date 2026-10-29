<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {partPage} from '@/config/parts';

import {bulkDestroy, create, destroy, index as partsRoute} from '@/routes/parts';
import {view as partsImportRoute} from '@/routes/parts/import';

import {createPartColumns} from '@/table/parts';

import type {Part, PartFilters, PartSort, PartsResponse} from '@/types/part';
import type {StatusFilter} from '@/types/listing';

const props = defineProps<{
    parts?: PartsResponse;
    hasAnyRecords: boolean;
    filters: PartFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: partPage.title,
            resourceUrl: partsRoute().url,
        }),
    },
});

const filterSectionId = ref<number | 'all'>(props.filters.sectionId);
const filterStatus = ref<StatusFilter>(props.filters.status);

const handleSectionChange = (value: number | null): void => {
    filterSectionId.value = value ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

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
    resetPreferences,
    hasActiveFilters,
} = useListingTable<Part, PartSort>({
    data: () => props.parts,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: partsRoute.url(),
    additionalFilters: () => ({
        section_id: filterSectionId.value !== 'all'
            ? filterSectionId.value
            : undefined,
        status: filterStatus.value !== 'all'
            ? filterStatus.value
            : undefined,
    }),
    onClearFilters: () => {
        filterSectionId.value = 'all';
        filterStatus.value = 'all';
    },
});

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No parts yet',
        description: 'Get started by adding your first part.',
        actionLabel: partPage.createLabel || 'Add part',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: partPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: partsImportRoute().url,
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
            handler: () => router.visit(partsImportRoute().url),
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
        :description="partPage.description"
        :title="partPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="partPage.createLabel"
                :create-url="create().url"
                :resource-label="partPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="partPage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="partPage.searchButtonLabel"
                :search-placeholder="partPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppSectionSelect
                            :model-value="filterSectionId === 'all' ? null : filterSectionId"
                            :placeholder="partPage.sectionPlaceholder"
                            clearable
                            @update:model-value="handleSectionChange"
                        />
                    </div>

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
            <Deferred data="parts">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="partPage.emptyText"
                        :item-label="partPage.itemLabel"
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
                    v-if="parts"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="partPage.emptyText"
                    :item-label="partPage.itemLabel"
                    :page-index="parts.meta.current_page - 1"
                    :page-size="parts.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="parts.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
