import {router} from '@inertiajs/vue3';
import type {RequestPayload} from '@inertiajs/core';
import type {RowData, SortingState} from '@tanstack/vue-table';
import {onMounted, ref, watch} from 'vue';

import {useDataTable} from '@/composables/useDataTable';
import {useListingPreferences} from '@/composables/useListingPreferences';
import type {PaginatedResponse, SortDirection} from '@/types/listing';

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
    perPage?: number;
};

type ListingOptions<
    TData extends RowData & Identifiable,
    TSort extends string,
> = {
    data: () => PaginatedResponse<TData> | undefined;
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
    useServerDefaults?: boolean;
};

type PaginationMeta = {
    current_page: number;
    per_page: number;
    last_page: number;
};

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];
const DEFAULT_PAGE = 1;
const DEFAULT_PER_PAGE = 10;
const DEFAULT_SORT = 'id';
const DEFAULT_DIRECTION: SortDirection = 'desc';

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
    | Response Helpers
    |--------------------------------------------------------------------------
    */

    const getResponse = () => options.data();

    const getMeta = (): PaginationMeta => {
        const meta = getResponse()?.meta;

        return {
            current_page: meta?.current_page ?? DEFAULT_PAGE,
            per_page:
                meta?.per_page ??
                options.filters.perPage ??
                DEFAULT_PER_PAGE,
            last_page: meta?.last_page ?? DEFAULT_PAGE,
        };
    };

    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    const searchQuery = ref(
        options.searchable === false ? '' : (options.filters.search ?? ''),
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
        hasQueryParameter('sort') || hasQueryParameter('direction');

    /*
    |--------------------------------------------------------------------------
    | Preferences
    |--------------------------------------------------------------------------
    */

    const listingPreferences = useListingPreferences(options.route, {
        defaultPerPage:
            getResponse()?.meta.per_page ??
            options.filters.perPage ??
            DEFAULT_PER_PAGE,
        defaultSorting,
        defaultSort: options.filters.sort,
        defaultDirection: options.filters.direction,
        allowedPageSizes: PAGE_SIZE_OPTIONS,
    });

    const { features } = listingPreferences;

    const hasExplicitPageSize = hasQueryParameter('per_page');
    const hasExplicitSort = hasExplicitSorting();

    /*
    |--------------------------------------------------------------------------
    | URL Parameters Take Priority
    |--------------------------------------------------------------------------
    */

    if (hasExplicitPageSize) {
        listingPreferences.setPerPage(getMeta().per_page);
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
            ? getMeta().per_page
            : listingPreferences.perPage.value;

    const initialSorting: SortingState = (() => {
        if (hasExplicitSort || !features.sorting) {
            return defaultSorting;
        }

        const savedSorting = listingPreferences.sorting.value
            .map((item) => {
                const column = options.columns.find((column) => {
                    const meta = column.meta as
                        | { sortKey?: string }
                        | undefined;

                    return (
                        (column.id ?? column.accessorKey) === item.id ||
                        meta?.sortKey === item.id
                    );
                });

                if (!column) {
                    return null;
                }

                return {
                    id: column.id ?? column.accessorKey ?? item.id,
                    desc: item.desc,
                };
            })
            .filter(
                (item): item is { id: string; desc: boolean } =>
                    item !== null,
            );

        return savedSorting.length > 0 ? savedSorting : defaultSorting;
    })();

    /*
    |--------------------------------------------------------------------------
    | Data Table
    |--------------------------------------------------------------------------
    */

    const { table, sorting, selectedRows, selectedCount, clearSelection } =
        useDataTable<TData>(
            () => getResponse()?.data ?? [],
            options.columns,
            {
                manualPagination: true,
                manualSorting: true,
                pageCount: () => getMeta().last_page,
                initialPageSize,
                initialSorting,
                enableRowSelection: options.enableRowSelection,

                onPaginationChange: ({ pageIndex, pageSize }) => {
                    // Deferred data may not have arrived yet.
                    if (!getResponse()) {
                        return;
                    }

                    const meta = getMeta();
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
        const column = table.getAllColumns().find((item) => {
            const sortKey = (
                item.columnDef.meta as { sortKey?: string } | undefined
            )?.sortKey;

            return item.id === columnId || sortKey === columnId;
        });

        const sortKey = (
            column?.columnDef.meta as { sortKey?: TSort } | undefined
        )?.sortKey;

        return (sortKey ?? column?.id ?? columnId) as TSort;
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
                       page = DEFAULT_PAGE,
                       perPage = listingPreferences.perPage.value,
                       sort,
                       direction,
                       clearSorting = false,
                       useServerDefaults = false,
                   }: VisitOptions<TSort> = {}) {
        const additionalFilters = options.additionalFilters?.() ?? {};

        if (useServerDefaults) {
            router.get(options.route, additionalFilters as RequestPayload, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });

            return;
        }

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

        const params: Record<string, unknown> = {
            ...sortingParams,
            ...additionalFilters,
        };

        // Include only non-default pagination values.
        if (page !== DEFAULT_PAGE) {
            params.page = page;
        }

        if (perPage !== DEFAULT_PER_PAGE) {
            params.per_page = perPage;
        }

        // Include only non-default sorting values.
        const effectiveSort = sortingParams.sort;
        const effectiveDirection = sortingParams.direction;

        if (
            effectiveSort === DEFAULT_SORT &&
            effectiveDirection === DEFAULT_DIRECTION
        ) {
            delete params.sort;
            delete params.direction;
        } else {
            if (effectiveSort !== undefined) {
                params.sort = effectiveSort;
            }

            if (effectiveDirection !== undefined) {
                params.direction = effectiveDirection;
            }
        }

        if (options.searchable !== false) {
            const search = searchQuery.value.trim();

            if (search) {
                params.search = search;
            }
        }

        router.get(options.route, params as RequestPayload, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Preferences
    |--------------------------------------------------------------------------
    */

    let preferencesInitialized = false;

    const restorePreferencesIfNecessary = () => {
        const response = getResponse();

        // Wait until deferred data has arrived.
        if (!response || preferencesInitialized) {
            return;
        }

        preferencesInitialized = true;

        const meta = response.meta;

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
            (savedSort !== options.filters.sort ||
                savedDirection !== options.filters.direction);

        if (!restorePageSize && !restoreSorting) {
            return;
        }

        visit({
            page: DEFAULT_PAGE,
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
    };

    onMounted(restorePreferencesIfNecessary);

    // Handles deferred props arriving after the component has mounted.
    watch(
        () => options.data(),
        (response) => {
            if (response) {
                restorePreferencesIfNecessary();
            }
        },
    );

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    const search = () => visit({ page: DEFAULT_PAGE });

    const clearFilters = () => {
        searchQuery.value = '';
        visit({ page: DEFAULT_PAGE });
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
            data: { ids },
            onSuccess: () => {
                clearSelection();
                onSuccess?.();
            },
        });
    };

    const resetPreferences = () => {
        listingPreferences.reset();
        visit({ useServerDefaults: true });
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
        resetPreferences,
    };
}
