<script lang="ts" setup>
import {router, usePage} from '@inertiajs/vue3';
import {computed, ref} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingTable} from '@/composables/useListingTable';
import {attendancePage} from '@/config/attendances';
import {bulkDestroy, create, destroy, index as attendancesRoute,} from '@/routes/attendances';
import {view as attendancesImportRoute} from '@/routes/attendances/import';
import {createAttendanceColumns} from '@/table/attendances';
import type {Attendance, AttendanceFilters, AttendanceResponse, AttendanceSort,} from '@/types/attendance';

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

const page = usePage();

const auth = computed(() => page.props.auth);

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filterEmployee = ref<number | string | 'all'>(props.filters.userId);

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

const columns = createAttendanceColumns(
    (uuid) => {
        router.delete(destroy(uuid).url);
    },
    ['super_admin', 'supervisor'].includes(auth.value.user?.role),
    ['super_admin', 'supervisor'].includes(auth.value.user?.role),
    ['super_admin', 'supervisor'].includes(auth.value.user?.role),
);

const {table, selectedCount, search, clearFilters, deleteSelected} =
    useListingTable<Attendance, AttendanceSort>({
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

const handleSearch = () => {
    search();
};

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
        :description="attendancePage.description"
        :title="attendancePage.title"
    >
        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <template #actions>
            <ListingActions
                :create-label="attendancePage.createLabel"
                :create-url="
                    ['super_admin', 'supervisor'].includes(auth.user?.role)
                        ? create().url
                        : undefined
                "
                :import-url="
                    auth.user?.role === 'super_admin'
                        ? attendancesImportRoute().url
                        : undefined
                "
                :resource-label="attendancePage.resourceLabel"
                :selected-count="selectedCount"
                import-label="Import"
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
                :clear-button-label="attendancePage.clearButtonLabel"
                :clear-disabled="
                    filterEmployee === 'all' &&
                    !filterStartDate &&
                    !filterEndDate
                "
                :search-button-label="attendancePage.searchButtonLabel"
                :searchable="false"
                @clear="handleClearFilters"
                @search="handleSearch"
            >
                <template #filters>
                    <div
                        v-if="
                            ['super_admin', 'supervisor'].includes(
                                auth.user?.role,
                            )
                        "
                        class="w-full shrink-0 sm:w-48"
                    >
                        <AppEmployeeSelect
                            :model-value="
                                filterEmployee === 'all' ? null : filterEmployee
                            "
                            :placeholder="attendancePage.employeePlaceholder"
                            clearable
                            searchable
                            @update:model-value="handleEmployeeChange"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterStartDate"
                            aria-label="Start date"
                            placeholder="Start date"
                            type="date"
                        />
                    </div>

                    <div class="w-full shrink-0 sm:w-40">
                        <AppInput
                            v-model="filterEndDate"
                            aria-label="End date"
                            placeholder="End date"
                            type="date"
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
                :columns-count="columns.length"
                :empty-text="attendancePage.emptyText"
                :item-label="attendancePage.itemLabel"
                :page-index="attendances.meta.current_page - 1"
                :page-size="attendances.meta.per_page"
                :page-size-options="[10, 25, 50, 100]"
                :selected-count="selectedCount"
                :table="table"
                :total="attendances.meta.total"
            />
        </template>
    </ListingLayout>
</template>
