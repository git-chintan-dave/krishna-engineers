import {type Component, computed} from 'vue';

export type ListingEmptyState = {
    title: string;
    description?: string;
    actionLabel?: string;
    onAction?: () => void;
    icon?: Component;
};

type UseListingEmptyStateOptions = {
    hasAnyRecords?: () => boolean;
    hasActiveFilters: () => boolean;
    noRecords: ListingEmptyState;
    noResults: ListingEmptyState;
};

export function useListingEmptyState(
    options: UseListingEmptyStateOptions,
) {
    return computed<ListingEmptyState | undefined>(() => {
        if (
            options.hasAnyRecords &&
            !options.hasAnyRecords()
        ) {
            return options.noRecords;
        }

        if (options.hasActiveFilters()) {
            return options.noResults;
        }

        return undefined;
    });
}
