<script lang="ts" setup>
import { router, usePage } from '@inertiajs/vue3';
import { FileUp, RefreshCw, RotateCcw } from '@lucide/vue';
import { computed, ref } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import ListingActions, {
    type ActionItem,
} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCrudBreadcrumbs } from '@/composables/useCrudBreadcrumbs';
import { useListingTable } from '@/composables/useListingTable';
import { attendancePage } from '@/config/attendances';
import {
    bulkDestroy,
    create,
    destroy,
    index as attendancesRoute,
    sync,
} from '@/routes/attendances';
import { view as attendancesImportRoute } from '@/routes/attendances/import';
import { createAttendanceColumns } from '@/table/attendances';
import type {
    Attendance,
    AttendanceFilters,
    AttendanceResponse,
    AttendanceSort,
} from '@/types/attendance';
import {getAttendanceRowHighlight} from "@/lib/row-highlights";

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SyncType = 'in_out' | 'punches';
type SyncMessageType = 'success' | 'error' | null;

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
const userRole = computed(() => auth.value.user?.role);

const canManageAttendance = computed(() =>
    ['super_admin', 'supervisor'].includes(userRole.value ?? ''),
);

const isSuperAdmin = computed(() => userRole.value === 'super_admin');

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
| Attendance Sync
|--------------------------------------------------------------------------
*/

const syncOpen = ref(false);
const syncType = ref<SyncType>('punches');
const syncEmployee = ref<number | null>(null);
const syncStartDate = ref('');
const syncEndDate = ref('');
const syncProcessing = ref(false);
const syncMessage = ref('');
const syncMessageType = ref<SyncMessageType>(null);

const syncDateError = computed(() => {
    if (!syncStartDate.value || !syncEndDate.value) {
        return 'Please select both start and end dates.';
    }

    if (syncStartDate.value > syncEndDate.value) {
        return 'The start date must be before or equal to the end date.';
    }

    return '';
});

const syncDescription = computed(() =>
    syncType.value === 'in_out'
        ? 'Imports daily IN/OUT summary records from eTimeOffice. Existing attendance records are skipped, so this option does not update existing attendance or breaks.'
        : 'Imports individual punch timestamps from eTimeOffice, identifies break intervals, and creates or updates attendance with the detailed punch data. The API does not provide today’s raw punches, so use a completed date range.',
);

const syncButtonLabel = computed(() =>
    syncType.value === 'in_out'
        ? 'Sync IN/OUT Data'
        : 'Sync Detailed Punch Data',
);

const syncDisabled = computed(
    () => syncProcessing.value || Boolean(syncDateError.value),
);

const openSyncDialog = () => {
    syncOpen.value = true;

    const today = new Date();
    const date = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, '0'),
        String(today.getDate()).padStart(2, '0'),
    ].join('-');

    syncStartDate.value ||= date;
    syncEndDate.value ||= date;
    syncMessage.value = '';
    syncMessageType.value = null;
};

const handleSyncDialogOpenChange = (open: boolean) => {
    syncOpen.value = open;
};

const handleSync = () => {
    if (syncDisabled.value) {
        return;
    }

    syncProcessing.value = true;
    syncMessage.value = '';
    syncMessageType.value = null;

    router.post(
        sync().url,
        {
            sync_type: syncType.value,
            user_id: syncEmployee.value ?? undefined,
            from: syncStartDate.value,
            to: syncEndDate.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                const flash = (
                    page.props as typeof page.props & {
                        flash?: {
                            success?: string;
                            error?: string;
                        };
                    }
                ).flash;

                if (flash?.error) {
                    syncMessage.value = flash.error;
                    syncMessageType.value = 'error';
                } else {
                    syncMessage.value =
                        flash?.success ?? 'Attendance sync completed.';
                    syncMessageType.value = 'success';
                }

                router.reload({
                    only: ['attendances'],
                    preserveScroll: true,
                });
            },

            onError: (errors) => {
                syncMessage.value =
                    errors.sync_type ??
                    errors.from ??
                    errors.to ??
                    errors.user_id ??
                    'Unable to sync attendance. Please check your input and try again.';

                syncMessageType.value = 'error';
            },

            onFinish: () => {
                syncProcessing.value = false;
            },
        },
    );
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
    canManageAttendance.value,
    canManageAttendance.value,
    canManageAttendance.value,
);

const {
    table,
    selectedCount,
    search,
    clearFilters,
    deleteSelected,
    resetPreferences,
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
            filterEmployee.value !== 'all' ? filterEmployee.value : undefined,
        start_date: filterStartDate.value || undefined,
        end_date: filterEndDate.value || undefined,
    }),
});

/*
|--------------------------------------------------------------------------
| Listing Actions
|--------------------------------------------------------------------------
*/

const actions = computed<ActionItem[]>(() => [
    ...(isSuperAdmin.value
        ? [
              {
                  key: 'import',
                  label: 'Import',
                  icon: FileUp,
                  href: attendancesImportRoute().url,
              },
              {
                  key: 'sync',
                  label: 'Sync attendance',
                  icon: RefreshCw,
                  action: openSyncDialog,
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
        <!-- Header Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="attendancePage.createLabel"
                :create-url="canManageAttendance ? create().url : undefined"
                :resource-label="attendancePage.resourceLabel"
                :selected-count="selectedCount"
                @delete-selected="handleDeleteSelected"
            />
        </template>

        <!-- Filters -->
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
                    <div v-if="canManageAttendance" class="w-full min-w-0">
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

                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterStartDate"
                            aria-label="Start date"
                            class="w-full min-w-0"
                            type="date"
                        />
                    </div>

                    <div class="w-full min-w-0">
                        <AppInput
                            v-model="filterEndDate"
                            aria-label="End date"
                            class="w-full min-w-0"
                            type="date"
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!-- Table -->
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
                :row-highlight="getAttendanceRowHighlight"
            />
        </template>
    </ListingLayout>

    <!-- Sync Attendance Dialog -->
    <Dialog :open="syncOpen" @update:open="handleSyncDialogOpenChange">
        <DialogContent
            class="max-h-[90vh] w-[calc(100%-2rem)] overflow-y-auto sm:max-w-lg"
        >
            <DialogHeader>
                <DialogTitle>Sync attendance</DialogTitle>

                <DialogDescription>
                    Choose which eTimeOffice data to import.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Sync Type -->
                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium">
                        Synchronization type
                    </legend>

                    <label
                        for="sync-type-in-out"
                        class="flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors hover:bg-muted/50"
                        :class="
                            syncType === 'in_out'
                                ? 'border-primary bg-muted/30'
                                : 'border-border'
                        "
                    >
                        <input
                            id="sync-type-in-out"
                            v-model="syncType"
                            class="mt-1 size-4 shrink-0 accent-primary"
                            name="sync-type"
                            type="radio"
                            value="in_out"
                            :disabled="syncProcessing"
                        />

                        <span class="space-y-1">
                            <span class="block text-sm font-medium">
                                IN/OUT Summary
                            </span>

                            <span class="block text-xs text-muted-foreground">
                                Imports the daily IN and OUT times. Existing
                                attendance is skipped; breaks are not imported.
                            </span>
                        </span>
                    </label>

                    <label
                        for="sync-type-punches"
                        class="flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors hover:bg-muted/50"
                        :class="
                            syncType === 'punches'
                                ? 'border-primary bg-muted/30'
                                : 'border-border'
                        "
                    >
                        <input
                            id="sync-type-punches"
                            v-model="syncType"
                            class="mt-1 size-4 shrink-0 accent-primary"
                            name="sync-type"
                            type="radio"
                            value="punches"
                            :disabled="syncProcessing"
                        />

                        <span class="space-y-1">
                            <span class="block text-sm font-medium">
                                Detailed Punch Data
                            </span>

                            <span class="block text-xs text-muted-foreground">
                                Imports individual punch times and break
                                intervals. Creates missing attendance or updates
                                existing attendance with detailed data.
                            </span>
                        </span>
                    </label>
                </fieldset>

                <div
                    class="rounded-md bg-muted/50 p-3 text-xs text-muted-foreground"
                    aria-live="polite"
                >
                    {{ syncDescription }}
                </div>

                <!-- Employee -->
                <div class="space-y-2">
                    <label class="text-sm font-medium" for="sync-employee">
                        Employee
                    </label>

                    <AppEmployeeSelect
                        id="sync-employee"
                        v-model="syncEmployee"
                        clearable
                        placeholder="All employees"
                        searchable
                    />

                    <p class="text-xs text-muted-foreground">
                        Leave empty to sync all employees.
                    </p>
                </div>

                <!-- Start Date -->
                <div class="space-y-2">
                    <label class="text-sm font-medium" for="sync-start-date">
                        Start date
                    </label>

                    <AppInput
                        id="sync-start-date"
                        v-model="syncStartDate"
                        aria-label="Sync start date"
                        type="date"
                    />
                </div>

                <!-- End Date -->
                <div class="space-y-2">
                    <label class="text-sm font-medium" for="sync-end-date">
                        End date
                    </label>

                    <AppInput
                        id="sync-end-date"
                        v-model="syncEndDate"
                        aria-label="Sync end date"
                        type="date"
                    />
                </div>

                <p v-if="syncDateError" class="text-sm text-destructive">
                    {{ syncDateError }}
                </p>

                <div
                    v-if="syncMessage"
                    :class="
                        syncMessageType === 'success'
                            ? 'border-green-600/30 text-green-700 dark:text-green-400'
                            : 'border-destructive/30 text-destructive'
                    "
                    aria-live="polite"
                    class="max-h-40 overflow-y-auto rounded-md border p-3 text-sm whitespace-pre-wrap"
                    role="status"
                >
                    {{ syncMessage }}
                </div>

                <Button
                    :disabled="syncDisabled"
                    class="w-full cursor-pointer"
                    type="button"
                    @click="handleSync"
                >
                    <RefreshCw
                        :class="{ 'animate-spin': syncProcessing }"
                        aria-hidden="true"
                        class="mr-2 size-4"
                    />

                    {{ syncProcessing ? 'Syncing...' : syncButtonLabel }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
