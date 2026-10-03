<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    props.filters.userId,
);

const filterStatus = ref<'all' | string>(
    props.filters.status,
);

const filterStartDate = ref(props.filters.startDate);
const filterEndDate = ref(props.filters.endDate);

const handleEmployeeChange = (value: number | null) => {
    filterEmployee.value = value ?? 'all';
};

const handleStatusChange = (value: string | null) => {
    filterStatus.value = value ?? 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createSalaryColumns((id) => {
    router.delete(destroy(id).url);
});

const {
    table,
    searchQuery,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Salary, SalarySort>({
    data: () => props.salaries,
    columns,

    filters: {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
    },

    route: salariesRoute.url(),

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

const handleClearFilters = () => {
    filterEmployee.value = 'all';
    filterStatus.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = () => {
    deleteSelected(bulkDestroy().url);
};
</script>

<template>
    <ListingLayout
        :title="salaryPage.title"
        :description="salaryPage.description"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="salaryPage.createLabel"
                :selected-count="selectedCount"
                :resource-label="salaryPage.resourceLabel"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        -->

        <template #filters>
            <ListingFilters
                v-model:search="searchQuery"
                :search-placeholder="
                    salaryPage.searchPlaceholder
                "
                :search-button-label="
                    salaryPage.searchButtonLabel
                "
                :clear-button-label="
                    salaryPage.clearButtonLabel
                "
                :clear-disabled="
                    !searchQuery.trim() &&
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
                            :placeholder="
                                salaryPage.employeePlaceholder
                            "
                            clearable
                            searchable
                            @update:model-value="
                                handleEmployeeChange
                            "
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-36">
                        <AppSalaryStatusSelect
                            :model-value="
                                filterStatus === 'all'
                                    ? null
                                    : filterStatus
                            "
                            :placeholder="
                                salaryPage.statusPlaceholder
                            "
                            clearable
                            @update:model-value="
                                handleStatusChange
                            "
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
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!--
        |--------------------------------------------------------------------------
        | Table
        |--------------------------------------------------------------------------
        -->

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
