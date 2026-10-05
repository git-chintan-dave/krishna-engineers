<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppSalaryStatusSelect from '@/components/selects/AppSalaryStatusSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { salaryPage } from '@/config/salaries';
import {
    bulkDestroy,
    create,
    generateLastMonth,
    destroy,
    index as salariesRoute,
} from '@/routes/salaries';
import { createSalaryColumns } from '@/table/salaries';
import type {
    Salary,
    SalaryFilters,
    SalaryResponse,
    SalarySort,
} from '@/types/salary';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    salaries: SalaryResponse;
    filters: SalaryFilters;
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

const filterEmployee = ref<number | 'all'>(
    props.filters.user_id,
);

const filterStatus = ref<SalaryFilters['status']>(
    props.filters.status,
);

const filterStartDate = ref(
    props.filters.start_date,
);

const filterEndDate = computed(() => {
    if (!filterStartDate.value) {
        return '';
    }

    const date = new Date(
        `${filterStartDate.value}T00:00:00`,
    );

    date.setMonth(date.getMonth() + 1);
    date.setDate(0);

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
});

const handleEmployeeChange = (
    value: number | null,
): void => {
    filterEmployee.value = value ?? 'all';
};

const handleStatusChange = (
    value: string | null,
): void => {
    filterStatus.value = value ?? 'all';
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
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleClearFilters = (): void => {
    filterEmployee.value = 'all';
    filterStatus.value = 'all';
    filterStartDate.value = '';

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
</script>

<template>
    <ListingLayout
        :title="salaryPage.title"
        :description="salaryPage.description"
    >
        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="salaryPage.createLabel"
                :generate-url="generateLastMonth()"
                generate-label="Generate Last Month's Salary"
                :selected-count="selectedCount"
                :resource-label="salaryPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <template #filters>
            <ListingFilters
                :searchable="false"
                :clear-button-label="salaryPage.clearButtonLabel"
                :clear-disabled="
                    filterEmployee === 'all' &&
                    filterStatus === 'all' &&
                    !filterStartDate &&
                    !filterEndDate
                "
                @search="search"
                @clear="handleClearFilters"
            >
                <template #filters>
                    <div class="w-full shrink-0 sm:w-48">
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

                    <div class="w-full shrink-0 sm:w-36">
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

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterStartDate"
                            type="date"
                            placeholder="Start date"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterEndDate"
                            type="date"
                            placeholder="End date"
                            readonly
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <template #table>
            <DataTable
                :table="table"
                :columns-count="columns.length"
                :total="salaries.meta.total"
                :page-index="
                    salaries.meta.current_page - 1
                "
                :page-size="salaries.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="salaryPage.emptyText"
                :item-label="salaryPage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
