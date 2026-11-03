<script lang="ts" setup>
import {Deferred, router, usePage} from '@inertiajs/vue3';
import {computed, ref, watch} from 'vue';
import {
    Activity,
    CalendarDays,
    CheckCircle,
    Clock3,
    Coffee,
    Database,
    FileUp,
    RefreshCw,
    RotateCcw,
    SearchX,
    Timer,
    Users,
} from '@lucide/vue';
import DateRangePicker from '@/components/common/DateRangePicker.vue';
import ListingActions, {type ActionItem,} from '@/components/common/ListingActions.vue';
import ListingFilters from '@/components/common/ListingFilters.vue';
import ListingStats, {type ListingStatItem,} from '@/components/common/ListingStats.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import DataTable from '@/components/data-table/DataTable.vue';
import ListingLayout from '@/components/layouts/ListingLayout.vue';

import {Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,} from '@/components/ui/dialog';
import {Button} from '@/components/ui/button';

import {useCrudBreadcrumbs} from '@/composables/useCrudBreadcrumbs';
import {useListingEmptyState} from '@/composables/useListingEmptyState';
import {useListingTable} from '@/composables/useListingTable';
import {ACTION_SHORTCUTS} from '@/composables/useActionShortcuts';

import {attendancePage} from '@/config/attendances';

import {bulkDestroy, create, destroy, index as attendancesRoute, sync,} from '@/routes/attendances';
import {view as attendancesImportRoute,} from '@/routes/attendances/import';

import {createAttendanceColumns} from '@/table/attendances';
import {getAttendanceRowHighlight} from '@/lib/row-highlights';

import type {
    Attendance,
    AttendanceFilters,
    AttendanceResponse,
    AttendanceSort,
    AttendanceStatistics,
} from '@/types/attendance';

import type {NumericSelectValue} from '@/utils/selectUtils';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SyncType = 'in_out' | 'punches';
type SyncMessageType = 'success' | 'error' | null;
type EmployeeFilter = number[] | 'all';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    attendances?: AttendanceResponse;
    hasAnyRecords: boolean;
    filters: AttendanceFilters;
    statistics?: AttendanceStatistics;
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

const userRole = computed(
    () => page.props.auth.user?.role,
);

const canManageAttendance = computed(() =>
    ['super_admin', 'supervisor'].includes(
        (userRole.value as string | undefined) ?? '',
    ),
);

const isSuperAdmin = computed(
    () => userRole.value === 'super_admin',
);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getTodayString = (): string => {
    const date = new Date();

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
};

const normalizeEmployeeFilter = (
    value: AttendanceFilters['user_id'],
): EmployeeFilter => {
    if (value === null || value === undefined) {
        return 'all';
    }

    const values = Array.isArray(value)
        ? value
        : [value];

    const employeeIds = values
        .map(Number)
        .filter(Number.isFinite);

    return employeeIds.length
        ? employeeIds
        : 'all';
};

/*
|--------------------------------------------------------------------------
| Listing Filters
|--------------------------------------------------------------------------
*/

const filterEmployee = ref<EmployeeFilter>(
    normalizeEmployeeFilter(
        props.filters.user_id,
    ),
);

const filterStartDate = ref(
    props.filters.start_date ?? '',
);

const filterEndDate = ref(
    props.filters.end_date ?? '',
);

watch(
    () => props.filters,
    (filters) => {
        filterEmployee.value =
            normalizeEmployeeFilter(
                filters.user_id,
            );

        filterStartDate.value =
            filters.start_date ?? '';

        filterEndDate.value =
            filters.end_date ?? '';
    },
    {
        deep: true,
    },
);

const handleEmployeeChange = (
    value: NumericSelectValue,
): void => {
    if (Array.isArray(value)) {
        filterEmployee.value = value
            .map(Number)
            .filter(Number.isFinite);

        return;
    }

    if (typeof value === 'number') {
        filterEmployee.value = [value];

        return;
    }

    filterEmployee.value = 'all';
};

const handleDateRangeChange = (value: {
    startDate: string;
    endDate: string;
}): void => {
    filterStartDate.value = value.startDate;
    filterEndDate.value = value.endDate;
};

/*
|--------------------------------------------------------------------------
| Date Filter State
|--------------------------------------------------------------------------
*/

const isTodayFilterActive = computed(() => {
    const today = getTodayString();

    return (
        filterStartDate.value === today &&
        filterEndDate.value === today
    );
});

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
const syncMessageType =
    ref<SyncMessageType>(null);

const syncDateError = computed(() => {
    if (
        !syncStartDate.value ||
        !syncEndDate.value
    ) {
        return 'Please select both start and end dates.';
    }

    if (
        syncStartDate.value >
        syncEndDate.value
    ) {
        return 'The start date must be before or equal to the end date.';
    }

    return '';
});

const syncDescription = computed(() =>
    syncType.value === 'in_out'
        ? 'Imports daily IN/OUT summary records from eTimeOffice. Existing attendance records are skipped, so this option does not update existing attendance or breaks.'
        : 'Imports individual punch timestamps from eTimeOffice, identifies break intervals, and creates or updates attendance with detailed punch data. The API does not provide today’s raw punches, so use a completed date range.',
);

const syncButtonLabel = computed(() =>
    syncType.value === 'in_out'
        ? 'Sync IN/OUT Data'
        : 'Sync Detailed Punch Data',
);

const syncDisabled = computed(
    () =>
        syncProcessing.value ||
        Boolean(syncDateError.value),
);

const openSyncDialog = (): void => {
    syncOpen.value = true;

    const today = getTodayString();

    syncStartDate.value ||= today;
    syncEndDate.value ||= today;

    syncMessage.value = '';
    syncMessageType.value = null;
};

const handleSyncDialogOpenChange = (
    open: boolean,
): void => {
    syncOpen.value = open;
};

const handleSyncDateRangeChange = (value: {
    startDate: string;
    endDate: string;
}): void => {
    syncStartDate.value = value.startDate;
    syncEndDate.value = value.endDate;
};

const handleSync = (): void => {
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
            user_id:
                syncEmployee.value ?? undefined,
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
                    syncMessage.value =
                        flash.error;
                    syncMessageType.value =
                        'error';
                } else {
                    syncMessage.value =
                        flash?.success ??
                        'Attendance sync completed.';
                    syncMessageType.value =
                        'success';
                }

                router.reload({
                    only: [
                        'attendances',
                        'hasAnyRecords',
                        'statistics',
                    ],
                });
            },

            onError: (errors) => {
                syncMessage.value =
                    errors.sync_type ??
                    errors.from ??
                    errors.to ??
                    errors.user_id ??
                    'Unable to sync attendance. Please check your input and try again.';

                syncMessageType.value =
                    'error';
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
        router.delete(
            destroy(uuid).url,
        );
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
    hasActiveFilters,
    refresh,
    refreshing,
} = useListingTable<
    Attendance,
    AttendanceSort
>({
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

        start_date:
            filterStartDate.value || undefined,

        end_date:
            filterEndDate.value || undefined,
    }),

    refreshScope: 'tableAndStats',

    refreshProps: {
        table: [
            'attendances',
            'hasAnyRecords',
        ],

        tableAndStats: [
            'attendances',
            'hasAnyRecords',
            'statistics',
        ],
    },

    onClearFilters: () => {
        filterEmployee.value = 'all';
        filterStartDate.value = '';
        filterEndDate.value = '';
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
        (
            canManageAttendance.value &&
            filterEmployee.value !== 'all'
        ) ||
        Boolean(filterStartDate.value) ||
        Boolean(filterEndDate.value),
);

const activeFilterCount = computed(
    () =>
        Number(
            canManageAttendance.value &&
            filterEmployee.value !== 'all',
        ) +
        Number(Boolean(filterStartDate.value)) +
        Number(Boolean(filterEndDate.value)),
);

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
                shortcut:
                ACTION_SHORTCUTS.import.label,
            },
            {
                key: 'sync',
                label: 'Sync attendance',
                icon: RefreshCw,
                action: openSyncDialog,
                shortcut:
                ACTION_SHORTCUTS.sync.label,
            },
        ]
        : []),

    {
        key: 'reset-preferences',
        label: 'Reset preferences',
        icon: RotateCcw,
        action: resetPreferences,
        shortcut:
        ACTION_SHORTCUTS.resetPreferences.label,
        separatorBefore: true,
    },
]);

/*
|--------------------------------------------------------------------------
| Filter Actions
|--------------------------------------------------------------------------
*/

const handleClearFilters = (): void => {
    filterEmployee.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';

    clearFilters();
};

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

const formatDuration = (
    seconds: number,
): string => {
    const safeSeconds = Math.max(
        0,
        Math.floor(seconds),
    );

    const hours = Math.floor(
        safeSeconds / 3600,
    );

    const minutes = Math.floor(
        (safeSeconds % 3600) / 60,
    );

    if (hours >= 100) {
        return `${hours.toLocaleString()}h`;
    }

    return `${hours}h ${String(minutes).padStart(2, '0')}m`;
};


const statistics = computed<ListingStatItem[]>(() => [
    {
        label: "Today's Attendance",
        value: props.statistics?.total ?? 0,
        icon: CalendarDays,
        variant: 'primary',
        description: 'Total attendance records for today',
        clickable: false,
    },
    {
        label: 'Currently Working',
        value: props.statistics?.currently_working ?? 0,
        icon: Activity,
        variant: 'success',
        description: 'Employees currently working today',
        clickable: false,
    },
    {
        label: 'Completed',
        value: props.statistics?.completed ?? 0,
        icon: CheckCircle,
        variant: 'default',
        description: 'Attendance records with an end time',
        clickable: false,
    },
    {
        label: 'Employees Recorded',
        value: props.statistics?.unique_employees ?? 0,
        icon: Users,
        variant: 'info',
        description: 'Distinct employees recorded today',
        clickable: false,
    },
    {
        label: 'Effective Hours',
        value: formatDuration(
            props.statistics?.total_effective_working_seconds ?? 0,
        ),
        icon: Clock3,
        variant: 'success',
        description: 'Total effective working time today',
        clickable: false,
    },
    {
        label: 'Break Time',
        value: formatDuration(
            props.statistics?.total_break_seconds ?? 0,
        ),
        icon: Coffee,
        variant: 'warning',
        description: 'Total recorded break time today',
        clickable: false,
    },
    {
        label: 'Late By',
        value: formatDuration(
            props.statistics?.total_undertime_seconds ?? 0,
        ),
        icon: Timer,
        variant: 'destructive',
        description: 'Total recorded late by today',
        clickable: false,
    },
]);

const handleStatClick = (
    item: ListingStatItem,
): void => {
    if (
        item.label !== "Today's Attendance"
    ) {
        return;
    }

    if (isTodayFilterActive.value) {
        filterStartDate.value = '';
        filterEndDate.value = '';
        return;
    }

    const today = getTodayString();

    filterStartDate.value = today;
    filterEndDate.value = today;
};

/*
|--------------------------------------------------------------------------
| Bulk Actions
|--------------------------------------------------------------------------
*/

const handleDeleteSelected = (): void => {
    deleteSelected(
        bulkDestroy().url,
    );
};

/*
|--------------------------------------------------------------------------
| Empty State
|--------------------------------------------------------------------------
*/

const emptyState = useListingEmptyState({
    hasAnyRecords: () =>
        props.hasAnyRecords,

    hasActiveFilters: () =>
        hasAnyActiveFilters.value,

    noRecords: {
        title: 'No attendance records yet',
        description:
            'Create an attendance record or sync attendance from eTimeOffice.',
        actionLabel:
            canManageAttendance.value
                ? attendancePage.createLabel ||
                'Add attendance'
                : undefined,
        icon: Database,
        onAction:
            canManageAttendance.value
                ? () =>
                    router.visit(
                        create().url,
                    )
                : undefined,
    },

    noResults: {
        title: 'No results found',
        description:
            'Try adjusting your employee or date filters.',
        actionLabel:
            attendancePage.clearButtonLabel ||
            'Clear filters',
        icon: SearchX,
        onAction:
        handleClearFilters,
    },
});
</script>

<template>
    <ListingLayout
        :description="attendancePage.description"
        :title="attendancePage.title"
        @reset-preferences="resetPreferences"
    >
        <!-- Actions -->
        <template #actions>
            <ListingActions
                :actions="actions"
                :create-label="attendancePage.createLabel"
                :create-url="
                    canManageAttendance
                        ? create().url
                        : undefined
                "
                :resource-label="attendancePage.resourceLabel"
                :selected-count="selectedCount"
                :show-refresh="true"
                :refreshing="refreshing"
                @refresh="refresh('tableAndStats')"
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
                        :skeleton-count="8"
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
                :active-filter-count="
                    activeFilterCount
                "
                :clear-button-label="
                    attendancePage.clearButtonLabel
                "
                :clear-disabled="
                    !hasAnyActiveFilters
                "
                :search-button-label="
                    attendancePage.searchButtonLabel
                "
                :searchable="false"
                @clear="handleClearFilters"
                @search="search"
            >
                <template #filters>
                    <!-- Employee -->
                    <div
                        v-if="canManageAttendance"
                        class="w-full min-w-0"
                    >
                        <AppEmployeeSelect
                            multiple
                            :model-value="
                                filterEmployee ===
                                'all'
                                    ? null
                                    : filterEmployee
                            "
                            :placeholder="
                                attendancePage.employeePlaceholder
                            "
                            clearable
                            searchable
                            @update:model-value="
                                handleEmployeeChange
                            "
                        />
                    </div>

                    <!-- Date Range -->
                    <div
                        class="w-full min-w-0"
                    >
                        <DateRangePicker
                            :start-date="
                                filterStartDate
                            "
                            :end-date="
                                filterEndDate
                            "
                            clearable
                            placeholder="Select date range"
                            @change="
                                handleDateRangeChange
                            "
                            disable-future
                        />
                    </div>
                </template>
            </ListingFilters>
        </template>

        <!-- Table -->
        <template #table>
            <Deferred data="attendances">
                <template #fallback>
                    <DataTable
                        :columns-count="
                            columns.length
                        "
                        :empty-text="
                            attendancePage.emptyText
                        "
                        :item-label="
                            attendancePage.itemLabel
                        "
                        :loading="true"
                        :page-index="0"
                        :page-size="
                            filters.per_page
                        "
                        :page-size-options="
                            [10, 25, 50, 100]
                        "
                        :selected-count="0"
                        :table="table"
                        :total="0"
                    />
                </template>

                <DataTable
                    v-if="attendances"
                    :columns-count="
                        columns.length
                    "
                    :empty-state="emptyState"
                    :empty-text="
                        attendancePage.emptyText
                    "
                    :item-label="
                        attendancePage.itemLabel
                    "
                    :page-index="
                        attendances.meta.current_page -
                        1
                    "
                    :page-size="
                        attendances.meta.per_page
                    "
                    :page-size-options="
                        [10, 25, 50, 100]
                    "
                    :row-highlight="
                        getAttendanceRowHighlight
                    "
                    :selected-count="
                        selectedCount
                    "
                    :table="table"
                    :total="
                        attendances.meta.total
                    "
                />
            </Deferred>
        </template>
    </ListingLayout>

    <!-- Attendance Sync -->
    <Dialog
        :open="syncOpen"
        @update:open="handleSyncDialogOpenChange"
    >
        <DialogContent
            class="flex max-h-[90dvh] w-[calc(100%-1.5rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-lg"
        >
            <!-- Header -->
            <DialogHeader class="shrink-0 border-b px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <RefreshCw class="size-5" aria-hidden="true" />
                    </div>

                    <div class="min-w-0 space-y-1">
                        <DialogTitle class="text-base font-semibold">
                            Sync attendance
                        </DialogTitle>

                        <DialogDescription class="text-sm">
                            Import employee punch records and break intervals
                            from eTimeOffice.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Scrollable Content -->
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <!-- Sync Type -->
                <section class="space-y-3">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold">
                            Synchronization type
                        </h3>

                        <p class="text-xs text-muted-foreground">
                            Select the attendance data to import.
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-primary/40 bg-primary/[0.04] p-3.5 dark:bg-primary/[0.08]"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                            >
                                <RefreshCw class="size-4" aria-hidden="true" />
                            </div>

                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium">
                                    Detailed Punch Data
                                </span>

                                    <span
                                        class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary"
                                    >
                                    Selected
                                </span>
                                </div>

                                <p class="text-xs leading-relaxed text-muted-foreground">
                                    Imports individual punch times and break
                                    intervals. Creates missing attendance records
                                    or updates existing records with detailed
                                    punch data.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Sync Description -->
                <div
                    aria-live="polite"
                    class="flex gap-2.5 rounded-lg border bg-muted/30 p-3"
                >
                    <Info
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        aria-hidden="true"
                    />

                    <p class="text-xs leading-relaxed text-muted-foreground">
                        {{ syncDescription }}
                    </p>
                </div>

                <!-- Employee -->
                <section class="space-y-2.5">
                    <div class="space-y-1">
                        <label
                            class="text-sm font-medium"
                            for="sync-employee"
                        >
                            Employee
                        </label>

                        <p class="text-xs text-muted-foreground">
                            Choose one employee or synchronize all employees.
                        </p>
                    </div>

                    <AppEmployeeSelect
                        id="sync-employee"
                        v-model="syncEmployee"
                        clearable
                        :disabled="syncProcessing"
                        placeholder="All employees"
                        searchable
                    />
                </section>

                <!-- Date Range -->
                <section class="space-y-2.5">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">
                            Date range
                        </label>

                        <p class="text-xs text-muted-foreground">
                            Select the period to synchronize from eTimeOffice.
                        </p>
                    </div>

                    <DateRangePicker
                        :start-date="syncStartDate"
                        :end-date="syncEndDate"
                        :disabled="syncProcessing"
                        clearable
                        placeholder="Select sync date range"
                        @change="handleSyncDateRangeChange"
                        disable-future
                    />

                    <p
                        v-if="syncDateError"
                        class="text-sm leading-relaxed text-destructive"
                        role="alert"
                    >
                        {{ syncDateError }}
                    </p>
                </section>

                <!-- Sync Message -->
                <div
                    v-if="syncMessage"
                    :class="
                    syncMessageType === 'success'
                        ? 'border-green-600/30 bg-green-500/5 text-green-700 dark:text-green-400'
                        : 'border-destructive/30 bg-destructive/5 text-destructive'
                "
                    aria-live="polite"
                    class="max-h-40 overflow-y-auto rounded-lg border p-3 text-sm leading-relaxed whitespace-pre-wrap"
                    role="status"
                >
                    {{ syncMessage }}
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex shrink-0 flex-col-reverse gap-2 border-t bg-background px-5 py-4 sm:flex-row sm:justify-end sm:px-6"
            >
                <Button
                    class="w-full cursor-pointer sm:w-auto"
                    type="button"
                    variant="outline"
                    :disabled="syncProcessing"
                    @click="handleSyncDialogOpenChange(false)"
                >
                    Cancel
                </Button>

                <Button
                    :disabled="syncDisabled"
                    class="w-full cursor-pointer gap-2 sm:w-auto"
                    type="button"
                    @click="handleSync"
                >
                    <RefreshCw
                        :class="{
                        'animate-spin': syncProcessing,
                    }"
                        aria-hidden="true"
                        class="size-4"
                    />

                    {{
                        syncProcessing
                            ? 'Syncing...'
                            : syncButtonLabel
                    }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
