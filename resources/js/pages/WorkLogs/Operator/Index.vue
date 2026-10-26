<script lang="ts" setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {
    type ActionItem,
} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppMachineSelect from '@/components/selects/AppMachineSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSetupSelect from '@/components/selects/AppSetupSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
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
import { RotateCcw } from '@lucide/vue';

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

const {
    table,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
} = useListingTable<OperatorWorkLog, OperatorWorkLogSort>({
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
        user_id: filterUserId.value !== 'all' ? filterUserId.value : undefined,
        machine_id:
            filterMachineId.value !== 'all' ? filterMachineId.value : undefined,
        part_id: filterPartId.value !== 'all' ? filterPartId.value : undefined,
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
| Actions
|--------------------------------------------------------------------------
*/

const hasFilters = computed(
    () =>
        Boolean(filterWorkDate.value) ||
        filterShift.value !== 'all' ||
        (props.canSelectOperator && filterUserId.value !== 'all') ||
        filterMachineId.value !== 'all' ||
        filterPartId.value !== 'all' ||
        filterSetupId.value !== 'all',
);

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
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = (): void => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :description="operatorWorkLogPage.description"
        :title="operatorWorkLogPage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="operatorWorkLogPage.createLabel"
                :create-url="create().url"
                :resource-label="operatorWorkLogPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                :clear-button-label="operatorWorkLogPage.clearButtonLabel"
                :clear-disabled="!hasFilters"
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <!-- Work Date -->
                    <div class="w-full min-w-0">
                        <AppInput
                            :model-value="filterWorkDate"
                            :placeholder="
                                operatorWorkLogPage.workDatePlaceholder
                            "
                            type="date"
                            @update:model-value="handleWorkDateChange"
                        />
                    </div>

                    <!-- Operator -->
                    <div v-if="canSelectOperator" class="w-full min-w-0">
                        <AppEmployeeSelect
                            :model-value="
                                filterUserId === 'all' ? null : filterUserId
                            "
                            :placeholder="
                                operatorWorkLogPage.operatorPlaceholder
                            "
                            clearable
                            role="operator"
                            searchable
                            @update:model-value="handleUserChange"
                        />
                    </div>

                    <!-- Shift -->
                    <div class="w-full min-w-0">
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
                    <div class="w-full min-w-0">
                        <AppMachineSelect
                            :model-value="
                                filterMachineId === 'all'
                                    ? null
                                    : filterMachineId
                            "
                            :placeholder="
                                operatorWorkLogPage.machinePlaceholder
                            "
                            clearable
                            searchable
                            @update:model-value="handleMachineChange"
                        />
                    </div>

                    <!-- Part -->
                    <div class="w-full min-w-0">
                        <AppPartSelect
                            :model-value="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="operatorWorkLogPage.partPlaceholder"
                            clearable
                            searchable
                            @update:model-value="handlePartChange"
                        />
                    </div>

                    <!-- Setup -->
                    <div class="w-full min-w-0">
                        <AppSetupSelect
                            :model-value="
                                filterSetupId === 'all' ? null : filterSetupId
                            "
                            :part-id="
                                filterPartId === 'all' ? null : filterPartId
                            "
                            :placeholder="operatorWorkLogPage.setupPlaceholder"
                            clearable
                            searchable
                            @update:model-value="handleSetupChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!-- Table -->
        <template #table>
            <DataTable
                :columns-count="columns.length"
                :empty-text="operatorWorkLogPage.emptyText"
                :item-label="operatorWorkLogPage.itemLabel"
                :page-index="operatorWorkLogs.meta.current_page - 1"
                :page-size="operatorWorkLogs.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="operatorWorkLogs.meta.total"
            />
        </template>
    </ListingLayout>
</template>
