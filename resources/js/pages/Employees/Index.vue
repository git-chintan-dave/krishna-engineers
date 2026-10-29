<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {Database, FileUp, RotateCcw, SearchX} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppUserRoleSelect from '@/components/selects/AppUserRoleSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
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

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    employees?: EmployeesResponse;
    hasAnyRecords: boolean;
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
    hasActiveFilters,
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
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasActiveFilters.value,
    noRecords: {
        title: 'No employees yet',
        description: 'Create an employee to get started.',
        actionLabel: employeePage.createLabel,
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No employees found',
        description: 'Try adjusting your search or filters.',
        actionLabel: employeePage.clearButtonLabel,
        icon: SearchX,
        onAction: handleClearFilters,
    },
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
                            :model-value="
                                filterRole === 'all' ? null : filterRole
                            "
                            :placeholder="employeePage.rolePlaceholder"
                            clearable
                            @update:model-value="handleRoleChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppUserShiftSelect
                            :model-value="
                                filterShift === 'all' ? null : filterShift
                            "
                            :placeholder="employeePage.shiftPlaceholder"
                            clearable
                            @update:model-value="handleShiftChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppStatusSelect
                            :model-value="
                                filterStatus === 'all' ? null : filterStatus
                            "
                            :placeholder="employeePage.statusPlaceholder"
                            clearable
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="employees">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :item-label="employeePage.itemLabel"
                        :loading="true"
                        :page-index="0"
                        :page-size="filters.perPage ?? 10"
                        :page-size-options="[10, 25, 50, 100]"
                        :selected-count="selectedCount"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="employees"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :item-label="employeePage.itemLabel"
                    :page-index="employees.meta.current_page - 1"
                    :page-size="employees.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="employees.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
