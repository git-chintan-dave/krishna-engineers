
import {router} from '@inertiajs/vue3';
import type {RequestPayload} from '@inertiajs/core';
import type {RowData, SortingState} from '@tanstack/vue-table';
import {onMounted, ref} from 'vue';

import {useDataTable} from '@/composables/useDataTable';
import {useListingPreferences} from '@/composables/useListingPreferences';
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
    enableRowSelection?: (row: TData) => boolean;
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
| Constants
|--------------------------------------------------------------------------
*/

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];

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
    | Initial State
    |--------------------------------------------------------------------------
    */

    const searchQuery = ref(
        options.searchable === false
            ? ''
            : (options.filters.search ?? ''),
    );

    const defaultSorting: SortingState = [
        {
            id: options.filters.sort,
            desc: options.filters.direction === 'desc',
        },
    ];

    const hasQueryParameter = (name: string): boolean => {
        if (typeof window === 'undefined') {
            return false;
        }

        return new URLSearchParams(window.location.search).has(name);
    };

    const hasExplicitSorting = (): boolean =>
        hasQueryParameter('sort') ||
        hasQueryParameter('direction');

    /*
    |--------------------------------------------------------------------------
    | Preferences
    |--------------------------------------------------------------------------
    */

    const listingPreferences = useListingPreferences(
        options.route,
        {
            defaultPerPage: options.data().meta.per_page,
            defaultSorting,
            defaultSort: options.filters.sort,
            defaultDirection: options.filters.direction,
            allowedPageSizes: PAGE_SIZE_OPTIONS,
        },
    );

    const {features} = listingPreferences;

    const hasExplicitPageSize = hasQueryParameter('per_page');
    const hasExplicitSort = hasExplicitSorting();

    /*
    |--------------------------------------------------------------------------
    | URL Parameters Take Priority
    |--------------------------------------------------------------------------
    */

    if (hasExplicitPageSize) {
        listingPreferences.setPerPage(options.data().meta.per_page);
    }

    if (hasExplicitSort) {
        listingPreferences.setSorting(defaultSorting);

        listingPreferences.setServerSorting(
            options.filters.sort,
            options.filters.direction,
        );
    }

    const initialPageSize =
        hasExplicitPageSize || !features.perPage
            ? options.data().meta.per_page
            : listingPreferences.perPage.value;

    const initialSorting =
        hasExplicitSort || !features.sorting
            ? defaultSorting
            : listingPreferences.sorting.value.length > 0
                ? listingPreferences.sorting.value
                : defaultSorting;

    /*
    |--------------------------------------------------------------------------
    | Data Table
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
            pageCount: () => options.data().meta.last_page,
            initialPageSize,
            initialSorting,
            enableRowSelection: options.enableRowSelection,

            onPaginationChange: ({pageIndex, pageSize}) => {
                const meta = options.data().meta;
                const nextPage = pageIndex + 1;

                listingPreferences.setPerPage(pageSize);

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
                listingPreferences.setSorting(nextSorting);

                const currentSorting = nextSorting[0];

                if (!currentSorting) {
                    listingPreferences.setServerSorting(
                        options.filters.sort,
                        options.filters.direction,
                    );

                    visit({
                        page: 1,
                        clearSorting: true,
                    });

                    return;
                }

                const nextSort = getSortKey(currentSorting.id);
                const nextDirection: SortDirection =
                    currentSorting.desc ? 'desc' : 'asc';

                listingPreferences.setServerSorting(
                    nextSort,
                    nextDirection,
                );

                visit({
                    page: 1,
                    sort: nextSort,
                    direction: nextDirection,
                });
            },
        },
    );

    /*
    |--------------------------------------------------------------------------
    | Sorting Helpers
    |--------------------------------------------------------------------------
    */

    const getSortKey = (columnId: string): TSort => {
        const column = table.getColumn(columnId);

        return (
            (
                column?.columnDef.meta as
                    | {sortKey?: TSort}
                    | undefined
            )?.sortKey ?? columnId
        ) as TSort;
    };

    const getCurrentSorting = () => {
        const currentSorting = sorting.value[0];

        if (!currentSorting) {
            return {
                sort: undefined,
                direction: undefined,
            };
        }

        return {
            sort: getSortKey(currentSorting.id),
            direction: currentSorting.desc ? 'desc' : 'asc',
        };
    };

    /*
    |--------------------------------------------------------------------------
    | Initialize Current Server Sorting
    |--------------------------------------------------------------------------
    */

    const initialCurrentSorting = sorting.value[0];

    if (initialCurrentSorting) {
        listingPreferences.setServerSorting(
            getSortKey(initialCurrentSorting.id),
            initialCurrentSorting.desc ? 'desc' : 'asc',
        );
    } else {
        listingPreferences.setServerSorting(
            options.filters.sort,
            options.filters.direction,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Visit
    |--------------------------------------------------------------------------
    */

    function visit({
                       page = 1,
                       perPage = listingPreferences.perPage.value,
                       sort,
                       direction,
                       clearSorting = false,
                   }: VisitOptions<TSort> = {}) {
        const currentSorting = getCurrentSorting();

        const sortingParams = clearSorting
            ? {
                sort: undefined,
                direction: undefined,
            }
            : {
                sort: sort ?? currentSorting.sort,
                direction: direction ?? currentSorting.direction,
            };

        const params: RequestPayload = {
            per_page: perPage,
            page,
            ...sortingParams,
            ...options.additionalFilters?.(),
        };

        if (options.searchable !== false) {
            const search = searchQuery.value.trim();

            if (search) {
                params.search = search;
            }
        }

        router.get(options.route, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Preferences When Necessary
    |--------------------------------------------------------------------------
    */

    onMounted(() => {
        const meta = options.data().meta;

        const restorePageSize =
            features.perPage &&
            !hasExplicitPageSize &&
            listingPreferences.perPage.value !== meta.per_page;

        const savedSort = listingPreferences.sort.value;
        const savedDirection = listingPreferences.direction.value;

        const restoreSorting =
            features.sorting &&
            !hasExplicitSort &&
            Boolean(savedSort) &&
            Boolean(savedDirection) &&
            (
                savedSort !== options.filters.sort ||
                savedDirection !== options.filters.direction
            );

        if (!restorePageSize && !restoreSorting) {
            return;
        }

        visit({
            page: 1,
            perPage: restorePageSize
                ? listingPreferences.perPage.value
                : meta.per_page,
            ...(restoreSorting
                ? {
                    sort: savedSort as TSort,
                    direction: savedDirection as SortDirection,
                }
                : {}),
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    const search = () => visit({page: 1});

    const clearFilters = () => {
        searchQuery.value = '';
        visit({page: 1});
    };

    const deleteSelected = (
        bulkDeleteUrl: string,
        onSuccess?: () => void,
    ) => {
        const ids = selectedRows.value.map((row) => row.id);

        if (!ids.length) {
            return;
        }

        router.delete(bulkDeleteUrl, {
            data: {ids},
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
