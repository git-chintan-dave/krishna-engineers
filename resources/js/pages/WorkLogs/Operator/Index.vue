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
| Types
|--------------------------------------------------------------------------
*/

type FilterId = number | 'all';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    operatorWorkLogs: OperatorWorkLogsResponse;
    filters: OperatorWorkLogFilters;
    canSelectOperator: boolean;
}>();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const normalizeFilterId = (
    value: number | string | null | undefined,
): FilterId => {
    if (
        value === null ||
        value === undefined ||
        value === '' ||
        value === 'all'
    ) {
        return 'all';
    }

    return Number(value);
};

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

const filterWorkDate = ref<string>(props.filters.workDate ?? '');

const filterShift = ref<string>(props.filters.shift ?? 'all');

const filterUserId = ref<FilterId>(normalizeFilterId(props.filters.userId));

const filterMachineId = ref<FilterId>(
    normalizeFilterId(props.filters.machineId),
);

const filterPartId = ref<FilterId>(normalizeFilterId(props.filters.partId));

const filterSetupId = ref<FilterId>(normalizeFilterId(props.filters.setupId));

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createOperatorWorkLogColumns((uuid) => {
    router.delete(destroy(uuid).url);
}, props.canSelectOperator);

const { table, selectedCount, search, clearFilters, deleteSelected } =
    useListingTable<OperatorWorkLog, OperatorWorkLogSort>({
        data: () => props.operatorWorkLogs,

        columns,

        filters: {
            sort: props.filters.sort,
            direction: props.filters.direction,
        },

        route: operatorWorkLogsRoute.url(),

        searchable: false,

        additionalFilters: () => ({
            work_date: filterWorkDate.value || undefined,

            shift: filterShift.value !== 'all' ? filterShift.value : undefined,

            user_id:
                filterUserId.value !== 'all' ? filterUserId.value : undefined,

            machine_id:
                filterMachineId.value !== 'all'
                    ? filterMachineId.value
                    : undefined,

            part_id:
                filterPartId.value !== 'all' ? filterPartId.value : undefined,

            setup_id:
                filterSetupId.value !== 'all' ? filterSetupId.value : undefined,
        }),
    });

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleWorkDateChange = (value: string | number | null): void => {
    filterWorkDate.value = value === null ? '' : String(value);
};

const handleShiftChange = (value: string | null): void => {
    filterShift.value = value ?? 'all';
};

const handleUserChange = (value: number | null): void => {
    filterUserId.value = value ?? 'all';
};

const handleMachineChange = (value: number | null): void => {
    filterMachineId.value = value ?? 'all';
};

const handlePartChange = (value: number | null): void => {
    filterPartId.value = value ?? 'all';

    /*
     * Setup belongs to the selected Part,
     * so changing Part resets Setup.
     */
    filterSetupId.value = 'all';
};

const handleSetupChange = (value: number | null): void => {
    filterSetupId.value = value ?? 'all';
};

const handleClearFilters = (): void => {
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

const handleDeleteSelected = (): void => {
    deleteSelected(bulkDestroy().url);
};

/*
|--------------------------------------------------------------------------
| Filter State
|--------------------------------------------------------------------------
*/

const hasFilters = (): boolean =>
    Boolean(filterWorkDate.value) ||
    filterShift.value !== 'all' ||
    filterUserId.value !== 'all' ||
    filterMachineId.value !== 'all' ||
    filterPartId.value !== 'all' ||
    filterSetupId.value !== 'all';
</script>

<template>
    <ListingLayout
        :title="operatorWorkLogPage.title"
        :description="operatorWorkLogPage.description"
    >
        <!-- Actions -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="operatorWorkLogPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="operatorWorkLogPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->

        <template #filters>
            <ListingFilters
                :searchable="false"
                :clear-button-label="operatorWorkLogPage.clearButtonLabel"
                :clear-disabled="!hasFilters()"
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <!-- Work Date -->

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            :model-value="filterWorkDate"
                            type="date"
                            :placeholder="
                                operatorWorkLogPage.workDatePlaceholder
                            "
                            @update:model-value="handleWorkDateChange"
                        />
                    </div>

                    <!-- Operator -->

                    <div
                        v-if="canSelectOperator"
                        class="w-full shrink-0 sm:w-44"
                    >
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all' ? null : filterUserId
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

                    <!-- Shift -->

                    <div class="w-full shrink-0 sm:w-36">
                        <AppUserShiftSelect
                            :model-value="
                                filterShift === 'all' ? null : filterShift
                            "
                            :placeholder="operatorWorkLogPage.shiftPlaceholder"
                            clearable
                            @update:model-value="handleShiftChange"
                        />
                    </div>

                    <!-- Machine -->

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

                    <!-- Part -->

                    <div class="w-full shrink-0 sm:w-40">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="operatorWorkLogPage.partPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <!-- Setup -->

                    <div class="w-full shrink-0 sm:w-48">
                        <AppSetupSelect
                            :model-value="
                                filterSetupId === 'all' ? null : filterSetupId
                            "
                            :part-id="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="operatorWorkLogPage.setupPlaceholder"
                            searchable
                            clearable
                            @update:model-value="handleSetupChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!-- Table -->

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="operatorWorkLogs.meta.total"
                :page-index="operatorWorkLogs.meta.current_page - 1"
                :page-size="operatorWorkLogs.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="operatorWorkLogPage.emptyText"
                :item-label="operatorWorkLogPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
