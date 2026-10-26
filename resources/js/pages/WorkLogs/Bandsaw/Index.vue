<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { RotateCcw } from '@lucide/vue';
import { computed, ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {
    type ActionItem,
} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { bandsawWorkLogPage } from '@/config/bandsawWorkLogs';
import {
    bulkDestroy,
    create,
    destroy,
    index as bandsawWorkLogsRoute,
} from '@/routes/bandsaw';
import { createBandsawWorkLogColumns } from '@/table/bandsawWorkLogs';
import type {
    BandsawWorkLog,
    BandsawWorkLogFilters,
    BandsawWorkLogSort,
    BandsawWorkLogsResponse,
} from '@/types/bandsawWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    bandsawWorkLogs: BandsawWorkLogsResponse;
    filters: BandsawWorkLogFilters;
    canSelectBandsaw: boolean;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: bandsawWorkLogPage.title,
            resourceUrl: bandsawWorkLogsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterWorkDate = ref(props.filters.workDate);
const filterUserId = ref<number | 'all'>(props.filters.userId ?? 'all');
const filterPartId = ref<number | 'all'>(props.filters.partId ?? 'all');
const filterSectionId = ref<number | 'all'>(props.filters.sectionId ?? 'all');
const filterSectionSizeId = ref<number | 'all'>(
    props.filters.sectionSizeId ?? 'all',
);

const hasFilters = computed(
    () =>
        Boolean(filterWorkDate.value) ||
        (props.canSelectBandsaw && filterUserId.value !== 'all') ||
        filterPartId.value !== 'all' ||
        filterSectionId.value !== 'all' ||
        filterSectionSizeId.value !== 'all',
);

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createBandsawWorkLogColumns((id) => {
    router.delete(destroy(id).url);
}, props.canSelectBandsaw);

const {
    table,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
} = useListingTable<BandsawWorkLog, BandsawWorkLogSort>({
    data: () => props.bandsawWorkLogs,
    columns,
    filters: {
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: bandsawWorkLogsRoute.url(),
    searchable: false,
    additionalFilters: () => ({
        work_date: filterWorkDate.value || undefined,
        user_id: filterUserId.value !== 'all' ? filterUserId.value : undefined,
        part_id: filterPartId.value !== 'all' ? filterPartId.value : undefined,
        section_id:
            filterSectionId.value !== 'all' ? filterSectionId.value : undefined,
        section_size_id:
            filterSectionSizeId.value !== 'all'
                ? filterSectionSizeId.value
                : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Listing Actions
|--------------------------------------------------------------------------
*/

const actions = computed<ActionItem[]>(() => [
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
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleUserChange = (value: number | null): void => {
    filterUserId.value = value ?? 'all';
};

const handlePartChange = (value: number | null): void => {
    filterPartId.value = value ?? 'all';
};

const handleSectionChange = (value: number | null): void => {
    filterSectionId.value = value ?? 'all';
    filterSectionSizeId.value = 'all';
};

const handleSectionSizeChange = (value: number | null): void => {
    filterSectionSizeId.value = value ?? 'all';
};

const handleClearFilters = (): void => {
    filterWorkDate.value = '';
    filterUserId.value = 'all';
    filterPartId.value = 'all';
    filterSectionId.value = 'all';
    filterSectionSizeId.value = 'all';

    clearFilters();
};

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
        :description="bandsawWorkLogPage.description"
        :title="bandsawWorkLogPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="bandsawWorkLogPage.createLabel"
                :create-url="create().url"
                :resource-label="bandsawWorkLogPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                :clear-button-label="bandsawWorkLogPage.clearButtonLabel"
                :clear-disabled="!hasFilters"
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppInput v-model="filterWorkDate" type="date" />
                    </div>

                    <div v-if="canSelectBandsaw" class="w-full min-w-0">
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all' ? null : filterUserId
                            "
                            :placeholder="bandsawWorkLogPage.bandsawPlaceholder"
                            clearable
                            role="bandsaw"
                            searchable
                            @update:model-value="handleUserChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="bandsawWorkLogPage.partPlaceholder"
                            clearable
                            searchable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="bandsawWorkLogPage.sectionPlaceholder"
                            clearable
                            searchable
                            @update:model-value="handleSectionChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSectionSizeSelect
                            :model-value="
                                filterSectionSizeId === 'all'
                                    ? null
                                    : filterSectionSizeId
                            "
                            :placeholder="
                                bandsawWorkLogPage.sectionSizePlaceholder
                            "
                            :section-id="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            clearable
                            searchable
                            @update:model-value="handleSectionSizeChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :columns-count="columns.length"
                :empty-text="bandsawWorkLogPage.emptyText"
                :item-label="bandsawWorkLogPage.itemLabel"
                :page-index="bandsawWorkLogs.meta.current_page - 1"
                :page-size="bandsawWorkLogs.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="bandsawWorkLogs.meta.total"
            />
        </template>
    </ListingLayout>
</template>
