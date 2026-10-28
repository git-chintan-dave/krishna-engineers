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

import {setupPage} from '@/config/setups';

import {bulkDestroy, create, destroy, index as setupsRoute,} from '@/routes/setups';
import {view as setupsImportRoute} from '@/routes/setups/import';

import {createSetupColumns} from '@/table/setups';

import type {Setup, SetupFilters, SetupSort, SetupsResponse,} from '@/types/setup';
import type {StatusFilter} from '@/types/listing';

const props = defineProps<{
    setups?: SetupsResponse;
    hasAnyRecords: boolean;
    filters: SetupFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: setupPage.title,
            resourceUrl: setupsRoute().url,
        }),
    },
});

const filterStatus = ref<StatusFilter>(props.filters.status);

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const columns = createSetupColumns((id) => {
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
} = useListingTable<Setup, SetupSort>({
    data: () => props.setups,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: setupsRoute.url(),
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
        title: 'No setups yet',
        description: 'Get started by adding your first setup.',
        actionLabel: setupPage.createLabel || 'Add setup',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: setupPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: setupsImportRoute().url,
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
        :description="setupPage.description"
        :title="setupPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="setupPage.createLabel"
                :create-url="create().url"
                :resource-label="setupPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="setupPage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="setupPage.searchButtonLabel"
                :search-placeholder="setupPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="filterStatus === 'all' ? null : filterStatus"
                            :placeholder="setupPage.statusPlaceholder ?? 'All statuses'"
                            clearable
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="setups">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="setupPage.emptyText"
                        :item-label="setupPage.itemLabel"
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
                    v-if="setups"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="setupPage.emptyText"
                    :item-label="setupPage.itemLabel"
                    :page-index="setups.meta.current_page - 1"
                    :page-size="setups.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="setups.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
