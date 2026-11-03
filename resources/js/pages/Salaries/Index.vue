<script lang="ts" setup>
import {Deferred, router} from '@inertiajs/vue3';
import {computed, ref, watch} from 'vue';
import {
    CheckCircle,
    Database,
    Download,
    FileClock,
    Gift,
    HandCoins,
    RotateCcw,
    SearchX,
    SlidersHorizontal,
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

import {bulkDestroy, create, destroy, exportZip, generateLastMonth, index as salariesRoute} from '@/routes/salaries';

import {createSalaryColumns} from '@/table/salaries';

import type {Salary, SalaryFilters, SalaryResponse, SalarySort, SalaryStatistics, SalaryStatus,} from '@/types/salary';

import type {NumericSelectValue} from '@/utils/selectUtils';

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
| Filter State
|--------------------------------------------------------------------------
|
| Salary records are monthly. The user selects a month, while the
| backend continues receiving start_date and end_date.
|
*/

const filterEmployee = ref<
    number | number[] | 'all'
>(props.filters.user_id ?? 'all');

const filterStatus = ref<SalaryFilters['status']>(
    props.filters.status ?? 'all',
);

const filterMonth = ref(
    props.filters.start_date?.slice(0, 7) ?? '',
);

/*
|--------------------------------------------------------------------------
| Month Date Helpers
|--------------------------------------------------------------------------
*/

const getMonthStartDate = (month: string): string => {
    if (!/^\d{4}-\d{2}$/.test(month)) {
        return '';
    }

    const [year, monthNumber] = month
        .split('-')
        .map(Number);

    if (
        !year ||
        !monthNumber ||
        monthNumber < 1 ||
        monthNumber > 12
    ) {
        return '';
    }

    return `${month}-01`;
};

const getMonthEndDate = (month: string): string => {
    if (!/^\d{4}-\d{2}$/.test(month)) {
        return '';
    }

    const [year, monthNumber] = month
        .split('-')
        .map(Number);

    if (
        !year ||
        !monthNumber ||
        monthNumber < 1 ||
        monthNumber > 12
    ) {
        return '';
    }

    const lastDay = new Date(
        year,
        monthNumber,
        0,
    ).getDate();

    return `${month}-${String(lastDay).padStart(2, '0')}`;
};

const filterStartDate = computed(() =>
    getMonthStartDate(filterMonth.value),
);

const filterEndDate = computed(() =>
    getMonthEndDate(filterMonth.value),
);

/*
|--------------------------------------------------------------------------
| Synchronize Server Filters
|--------------------------------------------------------------------------
*/

watch(
    () => props.filters,
    (filters) => {
        filterEmployee.value =
            filters.user_id ?? 'all';

        filterStatus.value =
            filters.status ?? 'all';

        filterMonth.value =
            filters.start_date?.slice(0, 7) ?? '';
    },
    { deep: true },
);

/*
|--------------------------------------------------------------------------
| Filter Handlers
|--------------------------------------------------------------------------
*/

const handleEmployeeChange = (
    value: NumericSelectValue,
): void => {
    if (Array.isArray(value)) {
        filterEmployee.value = value
            .map(Number)
            .filter(Number.isFinite);

        if (!filterEmployee.value.length) {
            filterEmployee.value = 'all';
        }

        return;
    }

    if (typeof value === 'number') {
        filterEmployee.value = value;
        return;
    }

    filterEmployee.value = 'all';
};

const handleStatusChange = (
    value: string | null,
): void => {
    filterStatus.value =
        value === 'draft' || value === 'paid'
            ? value as SalaryStatus
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
    refresh,
    refreshing,
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

        start_date:
            filterStartDate.value || undefined,

        end_date:
            filterEndDate.value || undefined,
    }),

    refreshScope: 'tableAndStats',

    refreshProps: {
        table: [
            'salaries',
            'hasAnyRecords',
        ],

        tableAndStats: [
            'salaries',
            'hasAnyRecords',
            'statistics',
            'can_generate_last_month',
        ],
    },
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
        Boolean(filterMonth.value),
);

const activeFilterCount = computed(
    () =>
        Number(filterEmployee.value !== 'all') +
        Number(filterStatus.value !== 'all') +
        Number(Boolean(filterMonth.value)),
);

const handleClearFilters = (): void => {
    filterEmployee.value = 'all';
    filterStatus.value = 'all';
    filterMonth.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () => props.hasAnyRecords,

    hasActiveFilters: () =>
        hasAnyActiveFilters.value,

    noRecords: {
        title: 'No salary records yet',
        description:
            'Generate or create salary records to get started.',
        actionLabel: salaryPage.createLabel,
        icon: Database,
        onAction: () => router.visit(create().url),
    },

    noResults: {
        title: 'No salaries found',
        description:
            'Try adjusting the employee, status, or month filters.',
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

    const lastMonth = new Date(
        now.getFullYear(),
        now.getMonth() - 1,
        1,
    );

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
        key: 'download-zip',
        label: 'Export salary reports (ZIP)',
        icon: Download,
        action: () => {
            const link = document.createElement('a');
            link.href = exportZip().url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            link.remove();
        },
        separatorBefore: true,
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
        label: 'Total Payable',
        value: props.statistics?.total_payable ?? 0,
        icon: Wallet,
        variant: 'primary',
        description: 'Sum of final payable across all salary records',
        clickable: false,
    },
    {
        label: 'Total Paid',
        value: props.statistics?.total_paid ?? 0,
        icon: CheckCircle,
        variant: 'success',
        description: 'Sum of final payable for paid salary records',
        clickable: true,
        active: filterStatus.value === 'paid',
    },
    {
        label: 'Total Draft',
        value: props.statistics?.total_draft ?? 0,
        icon: FileClock,
        variant: 'warning',
        description: 'Sum of final payable for draft salary records',
        clickable: true,
        active: filterStatus.value === 'draft',
    },
    {
        label: 'Total Incentive',
        value: props.statistics?.total_incentive ?? 0,
        icon: TrendingUp,
        variant: 'success',
        description: 'Total recorded incentives',
        clickable: false,
    },
    {
        label: 'Total Deduction',
        value: props.statistics?.total_deduction ?? 0,
        icon: TrendingDown,
        variant: 'destructive',
        description: 'Total recorded deductions',
        clickable: false,
    },
    {
        label: 'Total Bonus',
        value: props.statistics?.total_bonus ?? 0,
        icon: Gift,
        variant: 'success',
        description: 'Total recorded bonuses',
        clickable: false,
    },
    {
        label: 'Total Adjustment',
        value: props.statistics?.total_adjustments ?? 0,
        icon: SlidersHorizontal,
        variant: 'info',
        description: 'Net sum of recorded adjustments',
        clickable: false,
    },
    {
        label: 'Total UPAD',
        value: props.statistics?.total_upad ?? 0,
        icon: Wallet,
        variant: 'warning',
        description: 'Total recorded UPAD',
        clickable: false,
    },
    {
        label: 'Total Paid Loan',
        value: props.statistics?.total_paid_loan ?? 0,
        icon: HandCoins,
        variant: 'default',
        description: 'Total paid-loan amount recorded',
        clickable: false,
    },
]);

const handleStatClick = (
    item: ListingStatItem,
): void => {
    switch (item.label) {
        case 'Total Paid':
            filterStatus.value =
                filterStatus.value === 'paid'
                    ? 'all'
                    : 'paid';
            break;

        case 'Total Draft':
            filterStatus.value =
                filterStatus.value === 'draft'
                    ? 'all'
                    : 'draft';
            break;

        default:
            return;
    }
};

/*
|--------------------------------------------------------------------------
| Filter Submission
|--------------------------------------------------------------------------
*/

const handleSearch = (): void => {
    search();
};
</script>

<template>
    <ListingLayout
        :description="salaryPage.description"
        :title="salaryPage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="salaryPage.createLabel"
                :create-url="create().url"
                :resource-label="salaryPage.resourceLabel"
                :selected-count="selectedCount"
                :show-refresh="true"
                :refreshing="refreshing"
                @refresh="refresh"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Statistics -->
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

        <!-- Filters -->
        <template #filters>
            <ListingFilters
                :active-filter-count="activeFilterCount"
                :clear-button-label="salaryPage.clearButtonLabel"
                :clear-disabled="!hasAnyActiveFilters"
                :searchable="false"
                @clear="handleClearFilters"
                @search="handleSearch"
            >
                <template #filters>
                    <!-- Employee -->
                    <div class="w-full min-w-0">
                        <AppEmployeeSelect
                            multiple
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

                    <!-- Salary Month -->
                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterMonth"
                            type="month"
                            :max="new Date().toISOString().slice(0, 7)"
                            placeholder="Select salary month"
                            helper="Future salary months are unavailable."
                        />
                    </div>

                    <!-- Salary Status -->
                    <div class="w-full min-w-0">
                        <AppSalaryStatusSelect
                            :model-value="
                                filterStatus === 'all'
                                    ? null
                                    : filterStatus
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

        <!-- Table -->
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
                        :selected-count="0"
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
