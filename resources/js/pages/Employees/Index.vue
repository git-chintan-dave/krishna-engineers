<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref} from 'vue';
import {
    Clock3,
    Cog,
    Database,
    FileUp,
    Moon,
    Package,
    RotateCcw,
    Scissors,
    SearchX,
    Shield,
    ShieldCheck,
    Sun,
    UserCheck,
    Users,
    UserX,
    Wrench,
} from '@lucide/vue';

import DataTable from '@/components/data-table/DataTable.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import ListingStats, {type ListingStatItem,} from '@/components/common/ListingStats.vue';
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
    EmployeeStatistics,
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
    statistics?: EmployeeStatistics;
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

const handleRoleChange = (value: string | null): void => {
    filterRole.value = (value as EmployeeRole | null) ?? 'all';
};

const handleShiftChange = (value: string | null): void => {
    filterShift.value = (value as EmployeeShift | null) ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value = (value as StatusFilter | null) ?? 'all';
};

const handleClearFilters = (): void => {
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
| Active Filters
|--------------------------------------------------------------------------
*/

const hasAnyActiveFilters = computed(
    () =>
        hasActiveFilters.value ||
        filterRole.value !== 'all' ||
        filterShift.value !== 'all' ||
        filterStatus.value !== 'all',
);

const activeFilterCount = computed(
    () =>
        Number(filterRole.value !== 'all') +
        Number(filterShift.value !== 'all') +
        Number(filterStatus.value !== 'all'),
);

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
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

const handleDeleteSelected = (): void => {
    deleteSelected(bulkDestroy().url);
};

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const statistics = computed<ListingStatItem[]>(() => [
    {
        label: 'Total Employees',
        value: props.statistics?.total ?? 0,
        icon: Users,
        variant: 'primary',
        description: 'All employees',
        clickable: true,
        active: false,
    },
    {
        label: 'Active',
        value: props.statistics?.active ?? 0,
        icon: UserCheck,
        variant: 'success',
        description: 'Active employees',
        clickable: true,
        active: filterStatus.value === 'active',
    },
    {
        label: 'Inactive',
        value: props.statistics?.inactive ?? 0,
        icon: UserX,
        variant: 'destructive',
        description: 'Inactive employees',
        clickable: true,
        active: filterStatus.value === 'inactive',
    },
    {
        label: 'Super Admin',
        value: props.statistics?.super_admin ?? 0,
        icon: ShieldCheck,
        variant: 'default',
        description: 'Super administrators',
        clickable: true,
        active: filterRole.value === 'super_admin',
    },
    {
        label: 'Supervisor',
        value: props.statistics?.supervisor ?? 0,
        icon: Shield,
        variant: 'info',
        description: 'Supervisors',
        clickable: true,
        active: filterRole.value === 'supervisor',
    },
    {
        label: 'Operator',
        value: props.statistics?.operator ?? 0,
        icon: Cog,
        variant: 'primary',
        description: 'Machine operators',
        clickable: true,
        active: filterRole.value === 'operator',
    },
    {
        label: 'Bandsaw',
        value: props.statistics?.bandsaw ?? 0,
        icon: Scissors,
        variant: 'warning',
        description: 'Bandsaw operators',
        clickable: true,
        active: filterRole.value === 'bandsaw',
    },
    {
        label: 'Deburring & Packaging',
        value: props.statistics?.deburring_packaging ?? 0,
        icon: Package,
        variant: 'default',
        description: 'Deburring and packaging employees',
        clickable: true,
        active: filterRole.value === 'deburring_packaging',
    },
    {
        label: 'Day Shift',
        value: props.statistics?.day ?? 0,
        icon: Sun,
        variant: 'warning',
        description: 'Day shift employees',
        clickable: true,
        active: filterShift.value === 'day',
    },
    {
        label: 'Night Shift',
        value: props.statistics?.night ?? 0,
        icon: Moon,
        variant: 'info',
        description: 'Night shift employees',
        clickable: true,
        active: filterShift.value === 'night',
    },
    {
        label: '8-Hour Shift',
        value: props.statistics?.['8_hr'] ?? 0,
        icon: Clock3,
        variant: 'success',
        description: '8-hour shift employees',
        clickable: true,
        active: filterShift.value === '8_hr',
    },
    {
        label: '8-Hour Total',
        value: props.statistics?.['8_hr_total'] ?? 0,
        icon: Wrench,
        variant: 'default',
        description: '8-hour total shift employees',
        clickable: true,
        active: filterShift.value === '8_hr_total',
    },
]);

const handleStatClick = (item: ListingStatItem): void => {
    switch (item.label) {
        case 'Active':
            filterStatus.value =
                filterStatus.value === 'active' ? 'all' : 'active';
            break;

        case 'Inactive':
            filterStatus.value =
                filterStatus.value === 'inactive' ? 'all' : 'inactive';
            break;

        case 'Super Admin':
            filterRole.value =
                filterRole.value === 'super_admin' ? 'all' : 'super_admin';
            break;

        case 'Supervisor':
            filterRole.value =
                filterRole.value === 'supervisor' ? 'all' : 'supervisor';
            break;

        case 'Operator':
            filterRole.value =
                filterRole.value === 'operator' ? 'all' : 'operator';
            break;

        case 'Bandsaw':
            filterRole.value =
                filterRole.value === 'bandsaw' ? 'all' : 'bandsaw';
            break;

        case 'Deburring & Packaging':
            filterRole.value =
                filterRole.value === 'deburring_packaging'
                    ? 'all'
                    : 'deburring_packaging';
            break;

        case 'Day Shift':
            filterShift.value =
                filterShift.value === 'day' ? 'all' : 'day';
            break;

        case 'Night Shift':
            filterShift.value =
                filterShift.value === 'night' ? 'all' : 'night';
            break;

        case '8-Hour Shift':
            filterShift.value =
                filterShift.value === '8_hr' ? 'all' : '8_hr';
            break;

        case '8-Hour Total':
            filterShift.value =
                filterShift.value === '8_hr_total' ? 'all' : '8_hr_total';
            break;

        default:
            // Total Employees is informational and does not add a filter.
            return;
    }
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

        <template #stats>
            <Deferred data="statistics">
                <template #fallback>
                    <ListingStats
                        :columns="4"
                        :loading="true"
                        :skeleton-count="12"
                    />
                </template>

                <ListingStats
                    :items="statistics"
                    :columns="4"
                    @stat-click="handleStatClick"
                />
            </Deferred>
        </template>

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :active-filter-count="activeFilterCount"
                :clear-button-label="employeePage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :search-button-label="employeePage.searchButtonLabel"
                :search-placeholder="employeePage.searchPlaceholder"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppUserRoleSelect
                            multiple
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
                            multiple
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
                            clearable
                            placeholder="All statuses"
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
