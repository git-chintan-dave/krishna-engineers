
import {computed, ref, watch} from 'vue';
import type {SortingState} from '@tanstack/vue-table';

import {
    getListingPreferenceFeatures,
} from '@/config/listingPreferences';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

export type ListingSortDirection = 'asc' | 'desc';

export type ListingPreferences = {
    perPage: number;
    sorting: SortingState;
    sort?: string;
    direction?: ListingSortDirection;
};

export type UseListingPreferencesOptions = {
    defaultPerPage?: number;
    defaultSorting?: SortingState;
    defaultSort?: string;
    defaultDirection?: ListingSortDirection;
    allowedPageSizes?: number[];
};

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const STORAGE_PREFIX = 'listing-preferences';
const DEFAULT_PAGE_SIZES = [10, 25, 50, 100];

/*
|--------------------------------------------------------------------------
| Composable
|--------------------------------------------------------------------------
*/

export function useListingPreferences(
    module: string,
    options: UseListingPreferencesOptions = {},
) {
    const {
        defaultPerPage = 10,
        defaultSorting = [],
        defaultSort,
        defaultDirection = 'desc',
        allowedPageSizes = DEFAULT_PAGE_SIZES,
    } = options;

    const storageKey = `${STORAGE_PREFIX}:${module}`;
    const features = getListingPreferenceFeatures(module);

    /*
    |--------------------------------------------------------------------------
    | Defaults & Validation
    |--------------------------------------------------------------------------
    */

    const getDefaults = (): ListingPreferences => ({
        perPage: defaultPerPage,
        sorting: [...defaultSorting],
        sort: defaultSort,
        direction: defaultDirection,
    });

    const resolvePageSize = (value: unknown): number => {
        return (
            typeof value === 'number' &&
            allowedPageSizes.includes(value)
        )
            ? value
            : defaultPerPage;
    };

    const resolveSorting = (value: unknown): SortingState => {
        if (!Array.isArray(value)) {
            return [...defaultSorting];
        }

        return value.filter(
            (item): item is SortingState[number] =>
                typeof item === 'object' &&
                item !== null &&
                typeof item.id === 'string' &&
                typeof item.desc === 'boolean',
        );
    };

    const resolveDirection = (
        value: unknown,
    ): ListingSortDirection | undefined => {
        return value === 'asc' || value === 'desc'
            ? value
            : undefined;
    };

    const parseStoredPreferences = (): Partial<ListingPreferences> => {
        if (typeof window === 'undefined') {
            return {};
        }

        try {
            const stored = window.localStorage.getItem(storageKey);

            if (!stored) {
                return {};
            }

            const parsed: unknown = JSON.parse(stored);

            if (
                typeof parsed !== 'object' ||
                parsed === null ||
                Array.isArray(parsed)
            ) {
                return {};
            }

            return parsed as Partial<ListingPreferences>;
        } catch {
            return {};
        }
    };

    /*
    |--------------------------------------------------------------------------
    | Restore Preferences
    |--------------------------------------------------------------------------
    */

    const readPreferences = (): ListingPreferences => {
        const defaults = getDefaults();
        const stored = parseStoredPreferences();

        return {
            perPage: features.perPage
                ? resolvePageSize(stored.perPage)
                : defaultPerPage,

            sorting: features.sorting
                ? resolveSorting(stored.sorting)
                : [...defaultSorting],

            sort:
                features.sorting &&
                typeof stored.sort === 'string' &&
                stored.sort.length > 0
                    ? stored.sort
                    : defaults.sort,

            direction: features.sorting
                ? resolveDirection(stored.direction) ?? defaults.direction
                : defaults.direction,
        };
    };

    /*
    |--------------------------------------------------------------------------
    | Reactive State
    |--------------------------------------------------------------------------
    */

    const initialPreferences = readPreferences();

    const perPage = ref(initialPreferences.perPage);
    const sorting = ref<SortingState>(initialPreferences.sorting);
    const sort = ref<string | undefined>(initialPreferences.sort);
    const direction = ref<ListingSortDirection | undefined>(
        initialPreferences.direction,
    );

    const preferences = computed<ListingPreferences>(() => ({
        perPage: perPage.value,
        sorting: sorting.value,
        sort: sort.value,
        direction: direction.value,
    }));

    /*
    |--------------------------------------------------------------------------
    | Persist Enabled Features Only
    |--------------------------------------------------------------------------
    */

    watch(
        preferences,
        (value) => {
            if (
                typeof window === 'undefined' ||
                (!features.perPage && !features.sorting)
            ) {
                return;
            }

            try {
                const stored = parseStoredPreferences();

                const next: Partial<ListingPreferences> = {
                    ...stored,
                };

                if (features.perPage) {
                    next.perPage = value.perPage;
                }

                if (features.sorting) {
                    next.sorting = value.sorting;
                    next.sort = value.sort;
                    next.direction = value.direction;
                }

                window.localStorage.setItem(
                    storageKey,
                    JSON.stringify(next),
                );
            } catch {
                // Storage may be unavailable or full.
            }
        },
        {
            deep: true,
            flush: 'sync',
            immediate: true,
        },
    );

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    const setPerPage = (value: number): void => {
        if (
            !features.perPage ||
            !allowedPageSizes.includes(value)
        ) {
            return;
        }

        perPage.value = value;
    };

    const setSorting = (value: SortingState): void => {
        if (!features.sorting) {
            return;
        }

        sorting.value = value.map((item) => ({...item}));
    };

    /**
     * Updates the active sort state used by table navigation.
     * The watcher persists it only when sorting preferences are enabled.
     */
    const setServerSorting = (
        nextSort: string | undefined,
        nextDirection: ListingSortDirection | undefined,
    ): void => {
        sort.value = nextSort;
        direction.value = nextDirection;
    };

    const reset = (): void => {
        if (features.perPage) {
            perPage.value = defaultPerPage;
        }

        if (features.sorting) {
            sorting.value = [...defaultSorting];
            sort.value = defaultSort;
            direction.value = defaultDirection;
        }

        if (typeof window !== 'undefined') {
            window.localStorage.removeItem(
                `${STORAGE_PREFIX}:${module}`,
            );
        }
    };

    return {
        perPage,
        sorting,
        sort,
        direction,
        preferences,
        allowedPageSizes,
        features,
        setPerPage,
        setSorting,
        setServerSorting,
        reset,
    };
}
