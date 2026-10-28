<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';

import {partSetupPage} from '@/config/partSetups';

import {bulkDestroy, create, destroy, index as partSetupsRoute,} from '@/routes/part-setups';
import {view as partSetupsImportRoute} from '@/routes/part-setups/import';

import {createPartSetupColumns} from '@/table/partSetups';

import type {PartSetup, PartSetupFilters, PartSetupSort, PartSetupsResponse,} from '@/types/partSetup';

const props = defineProps<{
    partSetups?: PartSetupsResponse;
    hasAnyRecords: boolean;
    filters: PartSetupFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: partSetupPage.title,
            resourceUrl: partSetupsRoute().url,
        }),
    },
});

const filterPart = ref<number | null>(props.filters.partId);
const filterSetup = ref<number | null>(props.filters.setupId);

const columns = createPartSetupColumns((id) => {
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
} = useListingTable<PartSetup, PartSetupSort>({
    data: () => props.partSetups,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        perPage: props.filters.perPage,
    },
    route: partSetupsRoute.url(),
    additionalFilters: () => ({
        part_id: filterPart.value ?? undefined,
        setup_id: filterSetup.value ?? undefined,
    }),
    onClearFilters: () => {
        filterPart.value = null;
        filterSetup.value = null;
    },
});

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No part setups yet',
        description: 'Get started by assigning a setup to a part.',
        actionLabel: partSetupPage.createLabel || 'Add part setup',
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No results found',
        description: 'Try adjusting your search or filters.',
        actionLabel: partSetupPage.clearButtonLabel || 'Clear filters',
        icon: SearchX,
        onAction: clearFilters,
    },
});

const actions = computed<ActionItem[]>(() => [
    {
        key: 'import',
        label: 'Import',
        icon: FileUp,
        href: partSetupsImportRoute().url,
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
        :description="partSetupPage.description"
        :title="partSetupPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="partSetupPage.createLabel"
                :create-url="create().url"
                :resource-label="partSetupPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="partSetupPage.clearButtonLabel"
                :clear-disabled="!hasActiveFilters"
                :search-button-label="partSetupPage.searchButtonLabel"
                :search-placeholder="partSetupPage.searchPlaceholder"
                @clear="clearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppPartSelect
                            v-model="filterPart"
                            :placeholder="partSetupPage.partPlaceholder"
                            clearable
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSetupSelect
                            v-model="filterSetup"
                            :part-id="filterPart"
                            :placeholder="partSetupPage.setupPlaceholder"
                            clearable
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="partSetups">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :empty-text="partSetupPage.emptyText"
                        :item-label="partSetupPage.itemLabel"
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
                    v-if="partSetups"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :empty-text="partSetupPage.emptyText"
                    :item-label="partSetupPage.itemLabel"
                    :page-index="partSetups.meta.current_page - 1"
                    :page-size="partSetups.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="partSetups.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
