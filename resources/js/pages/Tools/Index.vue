<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import ListingActions, {type ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppToolTypeSelect from '@/components/selects/AppToolTypeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {ACTION_SHORTCUTS, useActionShortcuts} from '@/composables/useActionShortcuts';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {toolPage} from '@/config/tools';

import {bulkDestroy, create, destroy, index as toolsRoute} from '@/routes/tools';
import {view as toolsImportRoute} from '@/routes/tools/import';

import {createToolColumns} from '@/table/tools';

import {getQuantityRowHighlight} from '@/lib/row-highlights';

import type {Tool, ToolFilters, ToolSort, ToolsResponse} from '@/types/tool';
import type {StatusFilter} from '@/types/listing';

const props = defineProps<{
    tools?: ToolsResponse;
    hasAnyRecords: boolean;
    filters: ToolFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: toolPage.title,
            resourceUrl: toolsRoute().url,
        }),
    },
});

const filterType = ref<string | 'all'>(props.filters.type);
const filterStatus = ref<StatusFilter>(props.filters.status);

const handleTypeChange = (value: string | null): void => {
    filterType.value = value ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const columns = createToolColumns((id) => {
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
} = useListingTable<Tool, ToolSort>({
    data: () => props.tools,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: toolsRoute.url(),
    additionalFilters: () => ({
        type: filterType.value !== 'all'
            ? filterType.value
            : undefined,
        status: filterStatus.value !== 'all'
            ? filterStatus.value
            : undefined,
    }),
    onClearFilters: () => {
        filterType.value = 'all';
        filterStatus.value = 'all';
    },
});

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No tools yet',
        description: 'Get started by adding your first tool.',
        actionLabel: toolPage.createLabel || 'Add tool',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: toolPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: toolsImportRoute().url,
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
            handler: () => router.visit(toolsImportRoute().url),
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
        :description="toolPage.description"
        :title="toolPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="toolPage.createLabel"
                :create-url="create().url"
                :resource-label="toolPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="toolPage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="toolPage.searchButtonLabel"
                :search-placeholder="toolPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppToolTypeSelect
                            :model-value="filterType === 'all' ? null : filterType"
                            clearable
                            placeholder="All types"
                            @update:model-value="handleTypeChange"
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
            <Deferred data="tools">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="toolPage.emptyText"
                        :item-label="toolPage.itemLabel"
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
                    v-if="tools"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="toolPage.emptyText"
                    :item-label="toolPage.itemLabel"
                    :page-index="tools.meta.current_page - 1"
                    :page-size="tools.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :row-highlight="getQuantityRowHighlight"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="tools.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
