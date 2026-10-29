<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref, watch} from 'vue';
import {Database, RotateCcw, SearchX, Wallet} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppSalaryStatusSelect from '@/components/selects/AppSalaryStatusSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';
import {salaryPage} from '@/config/salaries';
import {bulkDestroy, create, destroy, generateLastMonth, index as salariesRoute,} from '@/routes/salaries';
import {createSalaryColumns} from '@/table/salaries';
import type {Salary, SalaryFilters, SalaryResponse, SalarySort, SalaryStatus,} from '@/types/salary';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    salaries?: SalaryResponse;
    hasAnyRecords: boolean;
    filters: SalaryFilters;
    can_generate_last_month: boolean;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: salaryPage.title,
            resourceUrl: salariesRoute().url,
        }),
    },
});

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterEmployee = ref<number | 'all'>(props.filters.user_id);
const filterStatus = ref<SalaryFilters['status']>(props.filters.status);
const filterStartDate = ref(props.filters.start_date);

/**
 * Calculate the final calendar day of the selected month
 * without parsing the date string as UTC.
 */
const filterEndDate = computed(() => {
    if (!filterStartDate.value) {
        return '';
    }

    const [year, month, day] = filterStartDate.value.split('-').map(Number);

    if (!year || !month || !day) {
        return '';
    }

    const selectedDate = new Date(year, month - 1, day);

    if (
        selectedDate.getFullYear() !== year ||
        selectedDate.getMonth() !== month - 1 ||
        selectedDate.getDate() !== day
    ) {
        return '';
    }

    const lastDay = new Date(year, month, 0).getDate();

    return `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
});

/**
 * Synchronize local filter state with updated server-side filters.
 */
watch(
    () => props.filters,
    (filters) => {
        filterEmployee.value = filters.user_id;
        filterStatus.value = filters.status;
        filterStartDate.value = filters.start_date;
    },
    {deep: true},
);

const handleEmployeeChange = (value: number | null): void => {
    filterEmployee.value = value ?? 'all';
};

const handleStatusChange = (value: string | null): void => {
    filterStatus.value =
        value === 'draft' || value === 'paid'
            ? (value as SalaryStatus)
            : 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createSalaryColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
    hasActiveFilters,
} = useListingTable<Salary, SalarySort>({
    data: () => props.salaries,
    columns,
    filters: {
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: salariesRoute.url(),
    searchable: false,
    additionalFilters: () => ({
        user_id:
            filterEmployee.value !== 'all'
                ? filterEmployee.value
                : undefined,
        status:
            filterStatus.value !== 'all'
                ? filterStatus.value
                : undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
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
        filterEmployee.value !== 'all' ||
        filterStatus.value !== 'all' ||
        Boolean(filterStartDate.value),
);

const activeFilterCount = computed(
    () =>
        Number(filterEmployee.value !== 'all') +
        Number(filterStatus.value !== 'all') +
        Number(Boolean(filterStartDate.value)),
);

const handleClearFilters = (): void => {
    filterEmployee.value = 'all';
    filterStatus.value = 'all';
    filterStartDate.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,
    hasActiveFilters: () => hasAnyActiveFilters.value,
    noRecords: {
        title: 'No salary records yet',
        description: 'Generate or create salary records to get started.',
        actionLabel: salaryPage.createLabel,
        icon: Database,
        onAction: () => router.visit(create().url),
    },
    noResults: {
        title: 'No salaries found',
        description: 'Try adjusting the employee, status, or date filters.',
        actionLabel: salaryPage.clearButtonLabel,
        icon: SearchX,
        onAction: handleClearFilters,
    },
});

/*
|--------------------------------------------------------------------------
| Listing Actions
|--------------------------------------------------------------------------
*/

const generateButtonLabel = computed(() => {
    const now = new Date();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const monthName = lastMonth.toLocaleString(undefined, {
        month: 'long',
    });

    return `Generate ${monthName}'s Salary`;
});

const actions = computed<ActionItem[]>(() => [
    ...(props.can_generate_last_month
        ? [
            {
                key: 'generate-last-month',
                label: generateButtonLabel.value,
                icon: Wallet,
                href: generateLastMonth().url,
                method: 'post' as const,
            },
        ]
        : []),
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
        :description="salaryPage.description"
        :title="salaryPage.title"
        @reset-preferences="resetPreferences"
    >
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="salaryPage.createLabel"
                :create-url="create().url"
                :resource-label="salaryPage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                :active-filter-count="activeFilterCount"
                :clear-button-label="salaryPage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <div class="w-full min-w-0">
                        <AppEmployeeSelect
                            :model-value="
                                filterEmployee === 'all'
                                    ? null
                                    : filterEmployee
                            "
                            :placeholder="salaryPage.employeePlaceholder"
                            clearable
                            searchable
                            @update:model-value="handleEmployeeChange"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterStartDate"
                            placeholder="Start date"
                            type="date"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppInput
                            :model-value="filterEndDate"
                            placeholder="End date"
                            readonly
                            type="date"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppSalaryStatusSelect
                            :model-value="
                                filterStatus === 'all' ? null : filterStatus
                            "
                            :placeholder="salaryPage.statusPlaceholder"
                            clearable
                            searchable
                            @update:model-value="handleStatusChange"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <Deferred data="salaries">
                <template #fallback>
                    <DataTable
                        :columns-count="columns.length"
                        :item-label="salaryPage.itemLabel"
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
                    v-if="salaries"
                    :columns-count="columns.length"
                    :empty-state="emptyState"
                    :item-label="salaryPage.itemLabel"
                    :page-index="salaries.meta.current_page - 1"
                    :page-size="salaries.meta.per_page"
                    :page-size-options="[10, 25, 50, 100]"
                    :selected-count="selectedCount"
                    :table="table"
                    :total="salaries.meta.total"
                />
            </Deferred>
        </template>
    </ListingLayout>
</template>
