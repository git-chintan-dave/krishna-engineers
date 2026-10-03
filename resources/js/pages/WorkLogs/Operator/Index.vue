<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppMachineSelect from '@/components/selects/AppMachineSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { operatorWorkLogPage } from '@/config/operatorWorkLogs';
import {
    bulkDestroy,
    create,
    destroy,
    index as operatorWorkLogsRoute,
} from '@/routes/operator';
import { createOperatorWorkLogColumns } from '@/table/operatorWorkLogs';
import type {
    OperatorWorkLog,
    OperatorWorkLogFilters,
    OperatorWorkLogSort,
    OperatorWorkLogsResponse,
} from '@/types/operatorWorkLog';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    operatorWorkLogs: OperatorWorkLogsResponse;
    filters: OperatorWorkLogFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: operatorWorkLogPage.title,
            resourceUrl: operatorWorkLogsRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterWorkDate = ref(props.filters.workDate);
const filterShift = ref(props.filters.shift ?? 'all');
const filterUserId = ref(props.filters.userId ?? 'all');
const filterMachineId = ref(props.filters.machineId ?? 'all');
const filterPartId = ref(props.filters.partId ?? 'all');
const filterSetupId = ref(props.filters.setupId ?? 'all');

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createOperatorWorkLogColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<OperatorWorkLog, OperatorWorkLogSort>({
    data: () => props.operatorWorkLogs,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: operatorWorkLogsRoute.url(),

    additionalFilters: () => ({
        work_date: filterWorkDate.value || undefined,

        shift:
            filterShift.value !== 'all'
                ? filterShift.value
                : undefined,

        user_id:
            filterUserId.value !== 'all'
                ? filterUserId.value
                : undefined,

        machine_id:
            filterMachineId.value !== 'all'
                ? filterMachineId.value
                : undefined,

        part_id:
            filterPartId.value !== 'all'
                ? filterPartId.value
                : undefined,

        setup_id:
            filterSetupId.value !== 'all'
                ? filterSetupId.value
                : undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleShiftChange = (value: string | null) => {
    filterShift.value = value ?? 'all';
};

const handleUserChange = (value: number | null) => {
    filterUserId.value = value ?? 'all';
};

const handleMachineChange = (value: number | null) => {
    filterMachineId.value = value ?? 'all';
};

const handlePartChange = (value: number | null) => {
    filterPartId.value = value ?? 'all';
};

const handleSetupChange = (value: number | null) => {
    filterSetupId.value = value ?? 'all';
};

const handleClearFilters = () => {
    filterWorkDate.value = '';
    filterShift.value = 'all';
    filterUserId.value = 'all';
    filterMachineId.value = 'all';
    filterPartId.value = 'all';
    filterSetupId.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="operatorWorkLogPage.title"
        :description="operatorWorkLogPage.description"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="operatorWorkLogPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="operatorWorkLogPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        -->

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="
                    operatorWorkLogPage.searchPlaceholder
                "
                :search-button-label="
                    operatorWorkLogPage.searchButtonLabel
                "
                :clear-button-label="
                    operatorWorkLogPage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
                    !filterWorkDate &&
                    filterShift === 'all' &&
                    filterUserId === 'all' &&
                    filterMachineId === 'all' &&
                    filterPartId === 'all' &&
                    filterSetupId === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-36">
                        <AppUserShiftSelect
                            :model-value="
                                filterShift === 'all'
                                    ? null
                                    : filterShift
                            "
                            :placeholder="
                                operatorWorkLogPage.shiftPlaceholder
                            "
                            clearable
                            @update:model-value="handleShiftChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-44">
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all'
                                    ? null
                                    : filterUserId
                            "
                            :placeholder="
                                operatorWorkLogPage.operatorPlaceholder
                            "
                            searchable
                            clearable
                            role="operator"
                            @update:model-value="handleUserChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppMachineSelect
                            :model-value="
                                filterMachineId === 'all'
                                    ? null
                                    : filterMachineId
                            "
                            :placeholder="
                                operatorWorkLogPage.machinePlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="handleMachineChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all'
                                    ? null
                                    : filterPartId
                            "
                            :placeholder="
                                operatorWorkLogPage.partPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
                        <AppSetupSelect
                            :model-value="
                                filterSetupId === 'all'
                                    ? null
                                    : filterSetupId
                            "
                            :placeholder="
                                operatorWorkLogPage.setupPlaceholder
                            "
                            searchable
                            clearable
                            @update:model-value="handleSetupChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        -->

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="operatorWorkLogs.meta.total"
                :page-index="
                    operatorWorkLogs.meta.current_page - 1
                "
                :page-size="operatorWorkLogs.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="operatorWorkLogPage.emptyText"
                :item-label="operatorWorkLogPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
