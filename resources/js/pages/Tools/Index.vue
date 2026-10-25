<script lang="ts" setup>
import {router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppToolTypeSelect from '@/components/selects/AppToolTypeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {toolPage} from '@/config/tools';
import {bulkDestroy, create, destroy, index as toolsRoute,} from '@/routes/tools';
import {createToolColumns} from '@/table/tools';
import type {Tool, ToolFilters, ToolSort, ToolsResponse} from '@/types/tool';
import type {StatusFilter} from '@/types/listing';
import {FileUp, RotateCcw} from '@lucide/vue';
import {view as toolsImportRoute} from "@/routes/tools/import";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    tools: ToolsResponse;
    filters: ToolFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: toolPage.title,
            resourceUrl: toolsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterType = ref<string | 'all'>(props.filters.type);
const filterStatus = ref<StatusFilter>(props.filters.status);

const handleTypeChange = (value: string | null): void => {
    filterType.value = value ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = (): void => {
    filterType.value = 'all';
    filterStatus.value = 'all';
    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

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
} = useListingTable<Tool, ToolSort>({
    data: () => props.tools,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: toolsRoute.url(),

    additionalFilters: () => ({
        type: filterType.value !== 'all' ? filterType.value : undefined,
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
        href: toolsImportRoute().url,
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
        :description="toolPage.description"
        :title="toolPage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
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

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="toolPage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterType === 'all' &&
                    filterStatus === 'all'
                "
                :search-button-label="toolPage.searchButtonLabel"
                :search-placeholder="toolPage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <!-- Tool Type -->
                    <div class="w-full min-w-0">
                        <AppToolTypeSelect
                            :model-value="
                                filterType === 'all' ? null : filterType
                            "
                            clearable
                            placeholder="All types"
                            @update:model-value="handleTypeChange"
                        />
                    </div>

                    <!-- Status -->
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
                :empty-text="toolPage.emptyText"
                :item-label="toolPage.itemLabel"
                :page-index="tools.meta.current_page - 1"
                :page-size="tools.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="tools.meta.total"
            />
        </template>
    </ListingLayout>
</template>
