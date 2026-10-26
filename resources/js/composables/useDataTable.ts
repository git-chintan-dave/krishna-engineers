import type {
    RowData,
    RowSelectionState,
    SortingState,
} from '@tanstack/vue-table';
import {
    createPaginatedRowModel,
    createSortedRowModel,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
    sortFn_alphanumeric,
    tableFeatures,
    useTable,
} from '@tanstack/vue-table';
import { computed, ref } from 'vue';

import type { PaginationState } from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type DataTableOptions<TData extends RowData> = {
    manualPagination?: boolean;
    manualSorting?: boolean;
    pageCount?: () => number;
    initialPageSize?: number;
    initialSorting?: SortingState;

    enableRowSelection?: (row: TData) => boolean;

    onPaginationChange?: (pagination: PaginationState) => void;

    onSortingChange?: (sorting: SortingState) => void;
};

/*
|--------------------------------------------------------------------------
| Composable
|--------------------------------------------------------------------------
*/

export function useDataTable<TData extends RowData>(
    getData: () => TData[],
    columns: any[],
    options: DataTableOptions<TData> = {},
) {
    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    const rowSelection = ref<RowSelectionState>({});

    const sorting = ref<SortingState>(options.initialSorting ?? []);

    const pagination = ref<PaginationState>({
        pageIndex: 0,
        pageSize: options.initialPageSize ?? 10,
    });

    /*
    |--------------------------------------------------------------------------
    | Table Features
    |--------------------------------------------------------------------------
    */

    const features = tableFeatures({
        rowPaginationFeature,
        rowSelectionFeature,
        rowSortingFeature,

        paginatedRowModel: createPaginatedRowModel(),
        sortedRowModel: createSortedRowModel(),

        sortFns: {
            alphanumeric: sortFn_alphanumeric,
        },
    });

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    const table = useTable({
        features,

        get data() {
            return getData();
        },

        columns,

        state: {
            get rowSelection() {
                return rowSelection.value;
            },

            get sorting() {
                return sorting.value;
            },

            get pagination() {
                return pagination.value;
            },
        },

        manualPagination: options.manualPagination ?? false,

        get pageCount() {
            if (!options.manualPagination) {
                return undefined;
            }

            return options.pageCount?.() ?? -1;
        },

        manualSorting: options.manualSorting ?? false,

        /*
        |--------------------------------------------------------------------------
        | Row Selection
        |--------------------------------------------------------------------------
        */

        enableRowSelection: options.enableRowSelection
            ? (row) => options.enableRowSelection?.(row.original) ?? true
            : undefined,

        onRowSelectionChange: (updater) => {
            rowSelection.value =
                typeof updater === 'function'
                    ? updater(rowSelection.value)
                    : updater;
        },

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        onSortingChange: (updater) => {
            sorting.value =
                typeof updater === 'function'
                    ? updater(sorting.value)
                    : updater;

            options.onSortingChange?.(sorting.value);
        },

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        onPaginationChange: (updater) => {
            pagination.value =
                typeof updater === 'function'
                    ? updater(pagination.value)
                    : updater;

            options.onPaginationChange?.(pagination.value);
        },
    });

    /*
    |--------------------------------------------------------------------------
    | Selection
    |--------------------------------------------------------------------------
    */

    const selectedRows = computed(() =>
        table.getSelectedRowModel().rows.map((row) => row.original),
    );

    const selectedCount = computed(() => selectedRows.value.length);

    const clearSelection = () => {
        rowSelection.value = {};
    };

    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    const resetSorting = () => {
        sorting.value = [];
    };

    const resetPagination = () => {
        table.setPageIndex(0);
    };

    const resetTable = () => {
        clearSelection();
        resetSorting();
        resetPagination();
    };

    return {
        table,

        pagination,
        sorting,
        rowSelection,

        selectedRows,
        selectedCount,

        clearSelection,
        resetSorting,
        resetPagination,
        resetTable,
    };
}
