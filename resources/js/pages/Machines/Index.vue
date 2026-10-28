<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';

import {machinePage} from '@/config/machines';

import {bulkDestroy, create, destroy, index as machinesRoute,} from '@/routes/machines';

import {view as machinesImportRoute} from '@/routes/machines/import';

import {createMachineColumns} from '@/table/machines';

import type {Machine, MachineFilters, MachineSort, MachinesResponse,} from '@/types/machine';

import type {StatusFilter} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    machines?: MachinesResponse;
    hasAnyRecords: boolean;
    filters: MachineFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: machinePage.title,
            resourceUrl: machinesRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterStatus = ref<StatusFilter>(props.filters.status);

const handleStatusChange = (value: string | null) => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
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

const columns = createMachineColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
} = useListingTable<Machine, MachineSort>({
    data: () => props.machines,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: machinesRoute.url(),
    additionalFilters: () => ({
        status:
            filterStatus.value !== 'all'
                ? filterStatus.value
                : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const hasActiveFilters = computed(
    () =>
        Boolean(searchQuery.value.trim()) ||
        filterStatus.value !== 'all',
);

const emptyState = computed(() => {
    // No records exist in the underlying listing.
    // Encourage the user to create the first machine.
    if (!props.hasAnyRecords) {
        return {
            title: 'No machines yet',
            description: 'Get started by adding your first machine.',
            actionLabel: machinePage.createLabel || 'Add machine',
            icon: Database,
            onAction: () => router.visit(create().url),
        };
    }

    // Records exist, but the current filters/search have no matches.
    if (hasActiveFilters.value) {
        return {
            title: 'No results found',
            description: 'Try adjusting your search or filters.',
            actionLabel: machinePage.clearButtonLabel || 'Clear filters',
            icon: SearchX,
            onAction: handleClearFilters,
        };
    }

    // Retain the DataTable's generic empty message for other cases.
    return undefined;
});

/*
|--------------------------------------------------------------------------
| Row Highlight
|--------------------------------------------------------------------------
*/

const getMachineRowHighlight = (_machine: Machine) => undefined;

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
        href: machinesImportRoute().url,
    },
    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        separatorBefore: true,
    },
]);

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :description="machinePage.description"
        :title="machinePage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="machinePage.createLabel"
                :create-url="create().url"
                :resource-label="machinePage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="machinePage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() && filterStatus === 'all'
                "
                :search-button-label="machinePage.searchButtonLabel"
                :search-placeholder="machinePage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="
                                filterStatus === 'all'
                                    ? null
                                    : filterStatus
                            "
                            :placeholder="
                                machinePage.statusPlaceholder ?? 'All statuses'
                            "
                            clearable
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="machines">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="machinePage.emptyText"
                        :item-label="machinePage.itemLabel"
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
                    v-if="machines"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="machinePage.emptyText"
                    :item-label="machinePage.itemLabel"
                    :page-index="machines.meta.current_page - 1"
                    :page-size="machines.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="machines.meta.total"
                    :row-highlight="getMachineRowHighlight"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
