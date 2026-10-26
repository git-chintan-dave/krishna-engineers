/*
|--------------------------------------------------------------------------
| Listing Preference Configuration
|--------------------------------------------------------------------------
|
| Global settings can be overridden using .env variables.
| Route exclusions below are matched against URL paths.
|
*/

const envBoolean = (
    value: string | undefined,
    defaultValue: boolean,
): boolean => {
    if (value === undefined || value.trim() === '') {
        return defaultValue;
    }

    return value.toLowerCase() === 'true';
};

export const listingPreferencesConfig = {
    /**
     * Master switch for listing preference persistence/restoration.
     */
    enabled: envBoolean(import.meta.env.VITE_LISTING_PREFERENCES_ENABLED, true),

    /**
     * Remember the selected page size.
     */
    perPage: envBoolean(
        import.meta.env.VITE_LISTING_PREFERENCES_PER_PAGE,
        true,
    ),

    /**
     * Remember the selected sort column and direction.
     */
    sorting: envBoolean(import.meta.env.VITE_LISTING_PREFERENCES_SORTING, true),

    /**
     * Disable all listing preferences for these paths.
     *
     * Example:
     * ['/people/attendances']
     */
    disabledRoutes: [] as string[],

    /**
     * Disable only page-size persistence for these paths.
     */
    perPageDisabledRoutes: [] as string[],

    /**
     * Disable only sorting persistence for these paths.
     */
    sortingDisabledRoutes: [] as string[],
};

export type ListingPreferenceFeatures = {
    enabled: boolean;
    perPage: boolean;
    sorting: boolean;
};

const normalizePath = (route: string): string => {
    try {
        const pathname = new URL(route, 'http://listing-preferences.local')
            .pathname;

        return pathname.replace(/\/+$/, '') || '/';
    } catch {
        return route.split('?')[0].replace(/\/+$/, '') || '/';
    }
};

const matchesRoute = (route: string, configuredRoutes: string[]): boolean => {
    const normalizedRoute = normalizePath(route);

    return configuredRoutes.some(
        (configuredRoute) => normalizePath(configuredRoute) === normalizedRoute,
    );
};

export const getListingPreferenceFeatures = (
    route: string,
): ListingPreferenceFeatures => {
    const globallyEnabled = listingPreferencesConfig.enabled;

    const routeDisabled = matchesRoute(
        route,
        listingPreferencesConfig.disabledRoutes,
    );

    const enabled = globallyEnabled && !routeDisabled;

    return {
        enabled,

        perPage:
            enabled &&
            listingPreferencesConfig.perPage &&
            !matchesRoute(
                route,
                listingPreferencesConfig.perPageDisabledRoutes,
            ),

        sorting:
            enabled &&
            listingPreferencesConfig.sorting &&
            !matchesRoute(
                route,
                listingPreferencesConfig.sortingDisabledRoutes,
            ),
    };
};
