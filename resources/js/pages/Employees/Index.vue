<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppStatusSelect from '@/components/selects/AppStatusSelect.vue';
import AppUserRoleSelect from '@/components/selects/AppUserRoleSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { employeePage } from '@/config/employees';
import {
    bulkDestroy,
    create,
    destroy,
    index as employeesRoute,
} from '@/routes/employees';
import { createEmployeeColumns } from '@/table/employees';
import type {
    Employee,
    EmployeeFilters,
    EmployeeRole,
    EmployeeShift,
    EmployeeSort,
    EmployeesResponse,
} from '@/types/employee';
import type { StatusFilter } from '@/types/listing';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';

// Props
const props = defineProps<{
    employees: EmployeesResponse;
    filters: EmployeeFilters;
}>();

// Page
defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: employeePage.title,
            resourceUrl: employeesRoute().url,
        }),
    },
});

// Filters
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

// Table
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

// Filter Actions
const handleClearFilters = () => {
    filterRole.value = 'all';
    filterStatus.value = 'all';

    clearFilters();
};

// Bulk Actions
const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="employeePage.title"
        :description="employeePage.description"
    >
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="employeePage.createLabel"
                :selected-count="selectedCount"
                :resource-label="employeePage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="employeePage.searchPlaceholder"
                :search-button-label="employeePage.searchButtonLabel"
                :clear-button-label="employeePage.clearButtonLabel"
                :clear-disabled="
                    !searchQuery.trim() &&
                    filterRole === 'all' &&
                    filterShift === 'all' &&
                    filterStatus === 'all'
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-40">
                        <AppUserRoleSelect
                            :model-value="
                                filterRole === 'all' ? null : filterRole
                            "
                            :placeholder="employeePage.rolePlaceholder"
                            clearable
                            @update:model-value="handleRoleChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-36">
                        <AppUserShiftSelect
                            :model-value="
                                filterShift === 'all' ? null : filterShift
                            "
                            :placeholder="employeePage.shiftPlaceholder"
                            clearable
                            @update:model-value="handleShiftChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-48">
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
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="employees.meta.total"
                :page-index="employees.meta.current_page - 1"
                :page-size="employees.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="employeePage.emptyText"
                :item-label="employeePage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
