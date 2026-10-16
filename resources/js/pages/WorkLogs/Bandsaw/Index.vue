<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
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

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createBandsawWorkLogColumns((id) => {
    router.delete(destroy(id).url);
}, props.canSelectBandsaw);

const { table, selectedCount, search, clearFilters, deleteSelected } =
    useListingTable<BandsawWorkLog, BandsawWorkLogSort>({
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

            user_id:
                filterUserId.value !== 'all' ? filterUserId.value : undefined,

            part_id:
                filterPartId.value !== 'all' ? filterPartId.value : undefined,

            section_id:
                filterSectionId.value !== 'all'
                    ? filterSectionId.value
                    : undefined,

            section_size_id:
                filterSectionSizeId.value !== 'all'
                    ? filterSectionSizeId.value
                    : undefined,
        }),
    });

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
        :title="bandsawWorkLogPage.title"
        :description="bandsawWorkLogPage.description"
    >
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="bandsawWorkLogPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="bandsawWorkLogPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                :searchable="false"
                :clear-button-label="bandsawWorkLogPage.clearButtonLabel"
                :clear-disabled="
                    !filterWorkDate &&
                    filterUserId === 'all' &&
                    filterPartId === 'all' &&
                    filterSectionId === 'all' &&
                    filterSectionSizeId === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput v-model="filterWorkDate" type="date" />
                    </div>

                    <div
                        v-if="canSelectBandsaw"
                        class="w-full shrink-0 sm:w-44"
                    >
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all' ? null : filterUserId
                            "
                            :placeholder="bandsawWorkLogPage.bandsawPlaceholder"
                            searchable
                            clearable
                            role="bandsaw"
                            @update:model-value="handleUserChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="bandsawWorkLogPage.partPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppSectionSelect
                            :model-value="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="bandsawWorkLogPage.sectionPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleSectionChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-44">
                        <AppSectionSizeSelect
                            :model-value="
                                filterSectionSizeId === 'all'
                                    ? null
                                    : filterSectionSizeId
                            "
                            :section-id="
                                filterSectionId === 'all'
                                    ? null
                                    : filterSectionId
                            "
                            :placeholder="
                                bandsawWorkLogPage.sectionSizePlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="handleSectionSizeChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="bandsawWorkLogs.meta.total"
                :page-index="bandsawWorkLogs.meta.current_page - 1"
                :page-size="bandsawWorkLogs.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="bandsawWorkLogPage.emptyText"
                :item-label="bandsawWorkLogPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
