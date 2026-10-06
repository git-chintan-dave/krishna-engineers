<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { attendancePage } from '@/config/attendances';
import {
    bulkDestroy,
    create,
    destroy,
    index as attendancesRoute,
} from '@/routes/attendances';
import { view as attendancesImportRoute } from '@/routes/attendances/import';
import { createAttendanceColumns } from '@/table/attendances';
import type {
    Attendance,
    AttendanceFilters,
    AttendanceResponse,
    AttendanceSort,
} from '@/types/attendance';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    attendances: AttendanceResponse;
    filters: AttendanceFilters;
}>();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

defineOptions({
    layout: {
        breadcrumbs: useCrudBreadcrumbs('listing', {
            resource: attendancePage.title,
            resourceUrl: attendancesRoute().url,
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

const filterStartDate = ref(props.filters.startDate);
const filterEndDate = ref(props.filters.endDate);

const handleEmployeeChange = (value: number | null) => {
    filterEmployee.value = value ?? 'all';
};

/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

const columns = createAttendanceColumns((uuid) => {
    router.delete(destroy(uuid).url);
});

const {
    table,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
} = useListingTable<Attendance, AttendanceSort>({
    data: () => props.attendances,
    columns,
    filters: {
        sort: props.filters.sort,
        direction: props.filters.direction,
    },
    route: attendancesRoute.url(),
    searchable: false,
    additionalFilters: () => ({
        user_id:
            filterEmployee.value !== 'all'
                ? filterEmployee.value
                : undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleClearFilters = () => {
    filterEmployee.value = 'all';
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
        :title="attendancePage.title"
        :description="attendancePage.description"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-url="create()"
                :create-label="attendancePage.createLabel"
                :import-url="attendancesImportRoute().url"
                import-label="Import"
                :selected-count="selectedCount"
                :resource-label="attendancePage.resourceLabel"
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
                :searchable="false"
                :clear-button-label="attendancePage.clearButtonLabel"
                :clear-disabled="
                    filterEmployee === 'all' &&
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
                                attendancePage.employeePlaceholder
                            "
                            clearable
                            searchable
                            @update:model-value="handleEmployeeChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterStartDate"
                            type="date"
                            placeholder="Start date"
                            aria-label="Start date"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterEndDate"
                            type="date"
                            placeholder="End date"
                            aria-label="End date"
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
                :total="attendances.meta.total"
                :page-index="
                    attendances.meta.current_page - 1
                "
                :page-size="attendances.meta.per_page"
                :selected-count="selectedCount"
                :page-size-options="[10, 25, 50, 100]"
                :empty-text="attendancePage.emptyText"
                :item-label="attendancePage.itemLabel"
            />
        </template>
    </ListingLayout>
</template>
