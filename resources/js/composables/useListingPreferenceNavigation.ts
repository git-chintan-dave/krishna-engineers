import { onMounted, ref } from 'vue';

import { getListingPreferenceFeatures } from '@/config/listingPreferences';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SavedListingPreferences = {
    perPage?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
};

type NavigationHref =
    | string
    | {
          url?: string;
          href?: string;
      }
    | undefined;

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const LISTING_PREFERENCES_PREFIX = 'listing-preferences:';

const DEFAULT_PAGE = 1;
const DEFAULT_PAGE_SIZE = 10;
const DEFAULT_SORT = 'id';
const DEFAULT_DIRECTION: SavedListingPreferences['direction'] = 'desc';

const ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

/*
|--------------------------------------------------------------------------
| Composable
|--------------------------------------------------------------------------
*/

export function useListingPreferenceNavigation() {
    const isClientReady = ref(false);

    onMounted(() => {
        isClientReady.value = true;
    });

    const resolveNavigationHref = (href: NavigationHref): string => {
        if (typeof href === 'string') {
            return href;
        }

        if (href && typeof href === 'object') {
            if (typeof href.url === 'string') {
                return href.url;
            }

            if (typeof href.href === 'string') {
                return href.href;
            }
        }

        return '';
    };

    const getNavigationHref = (href: NavigationHref): string => {
        const destination = resolveNavigationHref(href);

        if (
            !destination ||
            !isClientReady.value ||
            typeof window === 'undefined'
        ) {
            return destination;
        }

        try {
            const url = new URL(destination, window.location.origin);

            // Never modify external links.
            if (url.origin !== window.location.origin) {
                return destination;
            }

            const path = url.pathname.replace(/\/+$/, '') || '/';

            const features = getListingPreferenceFeatures(path);

            if (!features.perPage && !features.sorting) {
                return destination;
            }

            const rawPreferences = window.localStorage.getItem(
                `${LISTING_PREFERENCES_PREFIX}${path}`,
            );

            if (!rawPreferences) {
                return destination;
            }

            const parsed: unknown = JSON.parse(rawPreferences);

            if (
                typeof parsed !== 'object' ||
                parsed === null ||
                Array.isArray(parsed)
            ) {
                return destination;
            }

            const preferences = parsed as SavedListingPreferences;

            /*
             * Restore page size.
             */
            if (
                features.perPage &&
                typeof preferences.perPage === 'number' &&
                ALLOWED_PAGE_SIZES.includes(preferences.perPage)
            ) {
                if (preferences.perPage === DEFAULT_PAGE_SIZE) {
                    url.searchParams.delete('per_page');
                } else {
                    url.searchParams.set(
                        'per_page',
                        String(preferences.perPage),
                    );
                }
            }

            /*
             * Restore sorting.
             */
            if (
                features.sorting &&
                typeof preferences.sort === 'string' &&
                preferences.sort.length > 0 &&
                (preferences.direction === 'asc' ||
                    preferences.direction === 'desc')
            ) {
                const isDefaultSorting =
                    preferences.sort === DEFAULT_SORT &&
                    preferences.direction === DEFAULT_DIRECTION;

                if (isDefaultSorting) {
                    url.searchParams.delete('sort');
                    url.searchParams.delete('direction');
                } else {
                    url.searchParams.set('sort', preferences.sort);

                    url.searchParams.set('direction', preferences.direction);
                }
            }

            /*
             * Reset pagination when applying non-default
             * saved preferences.
             */
            const hasNonDefaultPageSize =
                features.perPage &&
                typeof preferences.perPage === 'number' &&
                ALLOWED_PAGE_SIZES.includes(preferences.perPage) &&
                preferences.perPage !== DEFAULT_PAGE_SIZE;

            const hasNonDefaultSorting =
                features.sorting &&
                typeof preferences.sort === 'string' &&
                preferences.sort.length > 0 &&
                (preferences.direction === 'asc' ||
                    preferences.direction === 'desc') &&
                !(
                    preferences.sort === DEFAULT_SORT &&
                    preferences.direction === DEFAULT_DIRECTION
                );

            if (hasNonDefaultPageSize || hasNonDefaultSorting) {
                url.searchParams.set('page', String(DEFAULT_PAGE));
            } else {
                url.searchParams.delete('page');
            }

            return `${url.pathname}${url.search}${url.hash}`;
        } catch {
            // Fall back to normal navigation if storage or URL
            // processing fails.
            return destination;
        }
    };

    return {
        getNavigationHref,
    };
}
