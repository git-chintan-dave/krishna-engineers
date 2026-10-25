<script lang="ts" setup>
import {router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {ActionItem} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppUserRoleSelect from '@/components/selects/AppUserRoleSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {employeePage} from '@/config/employees';
import {bulkDestroy, create, destroy, index as employeesRoute,} from '@/routes/employees';
import {view as employeesImportRoute} from '@/routes/employees/import';
import {createEmployeeColumns} from '@/table/employees';
import type {
    Employee,
    EmployeeFilters,
    EmployeeRole,
    EmployeeShift,
    EmployeeSort,
    EmployeesResponse,
} from '@/types/employee';
import type {StatusFilter} from '@/types/listing';
import {FileUp, RotateCcw} from "@lucide/vue";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    employees: EmployeesResponse;
    filters: EmployeeFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: employeePage.title,
            resourceUrl: employeesRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterRole = ref<EmployeeRole | 'all'>(props.filters.role);
const filterShift = ref<EmployeeShift | 'all'>(props.filters.shift);
const filterStatus = ref<StatusFilter>(props.filters.status);

const handleRoleChange = (value: string | null) => {
    filterRole.value = (value as EmployeeRole | null) ?? 'all';
};

const handleShiftChange = (value: string | null) => {
    filterShift.value = (value as EmployeeShift | null) ?? 'all';
};

const handleStatusChange = (value: string | null) => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = () => {
    filterRole.value = 'all';
    filterShift.value = 'all';
    filterStatus.value = 'all';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createEmployeeColumns((uuid) => {
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
} = useListingTable<Employee, EmployeeSort>({
    data: () => props.employees,
    columns,
    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: employeesRoute.url(),
    enableRowSelection: (row) => row.role !== 'super_admin',
    additionalFilters: () => ({
        role: filterRole.value !== 'all' ? filterRole.value : undefined,
        shift: filterShift.value !== 'all' ? filterShift.value : undefined,
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
        href: employeesImportRoute().url,
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
        :description="employeePage.description"
        :title="employeePage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="employeePage.createLabel"
                :create-url="create().url"
                :resource-label="employeePage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :clear-button-label="employeePage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterRole === 'all' &&
                    filterShift === 'all' &&
                    filterStatus === 'all'
                "
                :search-button-label="employeePage.searchButtonLabel"
                :search-placeholder="employeePage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppUserRoleSelect
                            :model-value="filterRole === 'all' ? null : filterRole"
                            :placeholder="employeePage.rolePlaceholder"
                            clearable
                            @update:model-value="handleRoleChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppUserShiftSelect
                            :model-value="filterShift === 'all' ? null : filterShift"
                            :placeholder="employeePage.shiftPlaceholder"
                            clearable
                            @update:model-value="handleShiftChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="filterStatus === 'all' ? null : filterStatus"
                            :placeholder="employeePage.statusPlaceholder"
                            clearable
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :columns-count="columns.length"
                :empty-text="employeePage.emptyText"
                :item-label="employeePage.itemLabel"
                :page-index="employees.meta.current_page - 1"
                :page-size="employees.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="employees.meta.total"
            />
        </template>
    </ListingLayout>
</template>
