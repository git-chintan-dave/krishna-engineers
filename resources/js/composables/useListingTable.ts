import { router } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';
import type {
    RowData,
    SortingState,
} from '@tanstack/vue-table';
import { ref } from 'vue';

import { useDataTable } from '@/composables/useDataTable';
import type {
    PaginatedResponse,
    SortDirection,
} from '@/types/listing';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type Identifiable = {
    id: number;
};

type ListingFilters<TSort extends string> = {
    search?: string;
    sort: TSort;
    direction: SortDirection;
};

type ListingOptions<
    TData extends RowData & Identifiable,
    TSort extends string,
> = {
    data: () => PaginatedResponse<TData>;
    columns: any[];
    filters: ListingFilters<TSort>;
    route: string;

    searchable?: boolean;

    additionalFilters?: () => Record<string, unknown>;

    enableRowSelection?: (
        row: TData,
    ) => boolean;
};

type VisitOptions<TSort extends string> = {
    page?: number;
    perPage?: number;
    sort?: TSort;
    direction?: SortDirection;
    clearSorting?: boolean;
};

/*
|--------------------------------------------------------------------------
| Composable
|--------------------------------------------------------------------------
*/

export function useListingTable<
    TData extends RowData & Identifiable,
    TSort extends string,
>(options: ListingOptions<TData, TSort>) {
    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    const searchQuery = ref(
        options.searchable === false
            ? ''
            : options.filters.search ?? '',
    );

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    const getCurrentSorting = () => {
        const currentSorting = sorting.value[0];

        if (!currentSorting) {
            return {
                sort: undefined,
                direction: undefined,
            };
        }

        return {
            sort: currentSorting.id as TSort,
            direction: currentSorting.desc
                ? 'desc'
                : 'asc',
        };
    };

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    const visit = ({
                       page = 1,
                       perPage = options.data().meta.per_page,
                       sort,
                       direction,
                       clearSorting = false,
                   }: VisitOptions<TSort> = {}) => {
        const currentSorting = getCurrentSorting();

        const sortingParams = clearSorting
            ? {
                sort: undefined,
                direction: undefined,
            }
            : {
                sort: sort ?? currentSorting.sort,
                direction:
                    direction ??
                    currentSorting.direction,
            };

        const params: RequestPayload = {
            per_page: perPage,
            page,

            ...sortingParams,
            ...options.additionalFilters?.(),
        };

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (options.searchable !== false) {
            const search = searchQuery.value.trim();

            if (search) {
                params.search = search;
            }
        }

        router.get(
            options.route,
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    /*
    |--------------------------------------------------------------------------
    | Initial Sorting
    |--------------------------------------------------------------------------
    */

    const initialSorting: SortingState = [
        {
            id: options.filters.sort,
            desc:
                options.filters.direction === 'desc',
        },
    ];

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    const {
        table,
        sorting,
        selectedRows,
        selectedCount,
        clearSelection,
    } = useDataTable<TData>(
        () => options.data().data,
        options.columns,
        {
            manualPagination: true,
            manualSorting: true,

            pageCount: () =>
                options.data().meta.last_page,

            initialSorting,

            enableRowSelection:
                options.enableRowSelection,

            onPaginationChange: ({
                pageIndex,
                pageSize,
            }) => {
                const meta = options.data().meta;
                const nextPage = pageIndex + 1;

                if (
                    nextPage === meta.current_page &&
                    pageSize === meta.per_page
                ) {
                    return;
                }

                visit({
                    page: nextPage,
                    perPage: pageSize,
                });
            },

            onSortingChange: (nextSorting) => {
                const currentSorting =
                    nextSorting[0];

                /*
                |--------------------------------------------------------------------------
                | Clear Sorting
                |--------------------------------------------------------------------------
                |
                | An empty sorting state means:
                | use the backend's default ordering.
                |
                */

                if (!currentSorting) {
                    visit({
                        page: 1,
                        clearSorting: true,
                    });

                    return;
                }

                visit({
                    page: 1,
                    sort: currentSorting.id as TSort,
                    direction:
                        currentSorting.desc
                            ? 'desc'
                            : 'asc',
                });
            },
        },
    );

    /*
    |--------------------------------------------------------------------------
    | Search & Filters
    |--------------------------------------------------------------------------
    */

    const search = () => {
        visit({
            page: 1,
        });
    };

    const clearFilters = () => {
        searchQuery.value = '';

        visit({
            page: 1,
        });
    };

    /*
    |--------------------------------------------------------------------------
    | Selection
    |--------------------------------------------------------------------------
    */

    const deleteSelected = (
        bulkDeleteUrl: string,
        onSuccess?: () => void,
    ) => {
        const ids = selectedRows.value.map(
            (row) => row.id,
        );

        if (!ids.length) {
            return;
        }

        router.delete(bulkDeleteUrl, {
            data: {
                ids,
            },

            onSuccess: () => {
                clearSelection();
                onSuccess?.();
            },
        });
    };

    /*
    |--------------------------------------------------------------------------
    | Return
    |--------------------------------------------------------------------------
    */

    return {
        table,
        sorting,
        searchQuery,

        selectedRows,
        selectedCount,
        clearSelection,

        visit,
        search,
        clearFilters,
        deleteSelected,
    };
}
