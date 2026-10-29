<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref, watch} from 'vue';
import {
    CalendarDays,
    CheckCircle,
    Clock3,
    Database,
    FileClock,
    RotateCcw,
    SearchX,
    TrendingDown,
    TrendingUp,
    Wallet,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import ListingStats, {type ListingStatItem,} from '@/components/common/ListingStats.vue';
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

import type {Salary, SalaryFilters, SalaryResponse, SalarySort, SalaryStatistics, SalaryStatus,} from '@/types/salary';

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
    statistics?: SalaryStatistics;
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
 * Calculate the last calendar day of the selected month
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

/*
|--------------------------------------------------------------------------
| Synchronize Server Filters
|--------------------------------------------------------------------------
*/

watch(
    () => props.filters,
    (filters) => {
        filterEmployee.value = filters.user_id;
        filterStatus.value = filters.status;
        filterStartDate.value = filters.start_date;
    },
    { deep: true },
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

    return `Generate ${lastMonth.toLocaleString(undefined, {
        month: 'long',
    })}'s Salary`;
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

/*
|--------------------------------------------------------------------------
| Statistics Formatting
|--------------------------------------------------------------------------
*/

const formatCurrency = (amount: number): string =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(amount);

const formatDuration = (seconds: number): string => {
    const safeSeconds = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);

    if (hours >= 100) {
        return `${hours.toLocaleString()}h`;
    }

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const statistics = computed<ListingStatItem[]>(() => [
    {
        label: 'Total Records',
        value: props.statistics?.total ?? 0,
        icon: Database,
        variant: 'primary',
        description: 'All salary records',
        clickable: false,
    },
    {
        label: 'Total Payable',
        value: formatCurrency(props.statistics?.total_payable ?? 0),
        icon: Wallet,
        variant: 'primary',
        description: 'Sum of final payable across all records',
        clickable: false,
    },
    {
        label: 'Paid Amount',
        value: formatCurrency(props.statistics?.total_paid ?? 0),
        icon: CheckCircle,
        variant: 'success',
        description: 'Final payable for paid records',
        clickable: true,
        active: filterStatus.value === 'paid',
    },
    {
        label: 'Draft Records',
        value: props.statistics?.draft_count ?? 0,
        icon: FileClock,
        variant: 'warning',
        description: 'Salary records in draft status',
        clickable: true,
        active: filterStatus.value === 'draft',
    },
    {
        label: 'Base Salary',
        value: formatCurrency(props.statistics?.total_base_salary ?? 0),
        icon: Wallet,
        variant: 'default',
        description: 'Total recorded base salary',
        clickable: false,
    },
    {
        label: 'Incentives',
        value: formatCurrency(props.statistics?.total_incentive ?? 0),
        icon: TrendingUp,
        variant: 'success',
        description: 'Total recorded incentives',
        clickable: false,
    },
    {
        label: 'Deductions',
        value: formatCurrency(props.statistics?.total_deduction ?? 0),
        icon: TrendingDown,
        variant: 'destructive',
        description: 'Total recorded deductions',
        clickable: false,
    },
    {
        label: 'UPAD',
        value: formatCurrency(props.statistics?.total_upad ?? 0),
        icon: Wallet,
        variant: 'warning',
        description: 'Total recorded UPAD',
        clickable: false,
    },
    {
        label: 'Paid Loans',
        value: formatCurrency(props.statistics?.total_paid_loan ?? 0),
        icon: Wallet,
        variant: 'default',
        description: 'Total paid-loan amount recorded',
        clickable: false,
    },
    {
        label: 'Adjustments',
        value: formatCurrency(props.statistics?.total_adjustments ?? 0),
        icon: TrendingUp,
        variant: 'info',
        description: 'Net sum of recorded adjustments',
        clickable: false,
    },
    {
        label: 'Attendance Days',
        value: props.statistics?.total_attendance_days ?? 0,
        icon: CalendarDays,
        variant: 'primary',
        description: 'Total attendance days across salary records',
        clickable: false,
    },
    {
        label: 'Late Days',
        value: props.statistics?.total_late_days ?? 0,
        icon: Clock3,
        variant: 'warning',
        description: 'Total recorded late days',
        clickable: false,
    },
    {
        label: 'Undertime',
        value: formatDuration(
            props.statistics?.total_undertime_seconds ?? 0,
        ),
        icon: Clock3,
        variant: 'destructive',
        description: 'Total recorded undertime',
        clickable: false,
    },
]);

const handleStatClick = (item: ListingStatItem): void => {
    switch (item.label) {
        case 'Paid Amount':
            filterStatus.value =
                filterStatus.value === 'paid' ? 'all' : 'paid';
            break;

        case 'Draft Records':
            filterStatus.value =
                filterStatus.value === 'draft' ? 'all' : 'draft';
            break;

        default:
            return;
    }
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

        <template #stats>
            <Deferred data="statistics">
                <template #fallback>
                    <ListingStats
                        :columns="4"
                        :loading="true"
                        :skeleton-count="13"
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
