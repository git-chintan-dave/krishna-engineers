import {router} from '@inertiajs/vue3';
import type {RequestPayload} from '@inertiajs/core';
import type {RowData, SortingState} from '@tanstack/vue-table';
import {computed, onMounted, ref, watch} from 'vue';

import {useDataTable} from '@/composables/useDataTable';
import {useListingPreferences} from '@/composables/useListingPreferences';
import type {PaginatedResponse, SortDirection} from '@/types/listing';

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
    reloadProps?: string[];
    searchable?: boolean;
    additionalFilters?: () => Record<string, unknown>;
    onClearFilters?: () => void;
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

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];
const DEFAULT_PAGE = 1;
const DEFAULT_PER_PAGE = 10;
const DEFAULT_SORT = 'id';
const DEFAULT_DIRECTION: SortDirection = 'desc';

export function useListingTable<
    TData extends RowData & Identifiable,
    TSort extends string,
>(options: ListingOptions<TData, TSort>) {
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

    const searchQuery = ref(
        options.searchable === false ? '' : (options.filters.search ?? ''),
    );

    const hasActiveFilters = computed(() => {
        const hasSearch =
            options.searchable !== false &&
            Boolean(searchQuery.value.trim());

        const additionalFilters = Object.values(
            options.additionalFilters?.() ?? {},
        );

        return (
            hasSearch ||
            additionalFilters.some((value) => {
                if (
                    value === undefined ||
                    value === null ||
                    value === '' ||
                    value === 'all'
                ) {
                    return false;
                }

                return !Array.isArray(value) || value.length > 0;
            })
        );
    });

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

    const hasExplicitPageSize = hasQueryParameter('per_page');
    const hasExplicitSort =
        hasQueryParameter('sort') || hasQueryParameter('direction');

    const listingPreferences = useListingPreferences(options.route, {
        defaultPerPage:
            getResponse()?.meta?.per_page ??
            options.filters.perPage ??
            DEFAULT_PER_PAGE,
        defaultSorting,
        defaultSort: options.filters.sort,
        defaultDirection: options.filters.direction,
        allowedPageSizes: PAGE_SIZE_OPTIONS,
    });

    const { features } = listingPreferences;

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

    const {
        table,
        sorting,
        selectedRows,
        selectedCount,
        clearSelection,
    } = useDataTable<TData>(
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
                        page: DEFAULT_PAGE,
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
                    page: DEFAULT_PAGE,
                    sort: nextSort,
                    direction: nextDirection,
                });
            },
        },
    );

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

    const initialCurrentSorting = sorting.value[0];

    listingPreferences.setServerSorting(
        initialCurrentSorting
            ? getSortKey(initialCurrentSorting.id)
            : options.filters.sort,
        initialCurrentSorting
            ? initialCurrentSorting.desc
                ? 'desc'
                : 'asc'
            : options.filters.direction,
    );

    function visit({
                       page = DEFAULT_PAGE,
                       perPage = listingPreferences.perPage.value,
                       sort,
                       direction,
                       clearSorting = false,
                       useServerDefaults = false,
                   }: VisitOptions<TSort> = {}) {
        const additionalFilters = options.additionalFilters?.() ?? {};
        const only = options.reloadProps;

        if (useServerDefaults) {
            router.get(
                options.route,
                additionalFilters as RequestPayload,
                {
                    ...(only?.length ? { only } : {}),
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );

            return;
        }

        const currentSorting = getCurrentSorting();

        const effectiveSort = clearSorting
            ? undefined
            : (sort ?? currentSorting.sort);

        const effectiveDirection = clearSorting
            ? undefined
            : (direction ?? currentSorting.direction);

        const params: Record<string, unknown> = {
            ...additionalFilters,
        };

        if (page !== DEFAULT_PAGE) {
            params.page = page;
        }

        if (perPage !== DEFAULT_PER_PAGE) {
            params.per_page = perPage;
        }

        if (
            effectiveSort !== DEFAULT_SORT ||
            effectiveDirection !== DEFAULT_DIRECTION
        ) {
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
            ...(only?.length ? { only } : {}),
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    let preferencesInitialized = false;

    const restorePreferencesIfNecessary = () => {
        const response = getResponse();

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

    watch(
        () => options.data(),
        (response) => {
            if (response) {
                restorePreferencesIfNecessary();
            }
        },
    );

    const search = () => visit({ page: DEFAULT_PAGE });

    const clearFilters = () => {
        options.onClearFilters?.();
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

    const resetPreferences = (): void => {
        options.onClearFilters?.();
        searchQuery.value = '';
        listingPreferences.reset();

        visit({ useServerDefaults: true });
    };

    return {
        table,
        sorting,
        searchQuery,
        selectedRows,
        selectedCount,
        clearSelection,
        hasActiveFilters,
        visit,
        search,
        clearFilters,
        deleteSelected,
        resetPreferences,
    };
}
