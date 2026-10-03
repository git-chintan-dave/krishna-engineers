import {usePage} from '@inertiajs/vue3';

import type {NavItem} from '@/types';

type NavigationHref = NavItem['href'];

type UrlMethodPair = {
    url: string;
    method?: string;
};

const getHrefUrl = (
    href?: NavigationHref,
): string | null => {
    if (!href) {
        return null;
    }

    if (typeof href === 'string') {
        return href;
    }

    if (
        typeof href === 'object' &&
        'url' in href &&
        typeof (href as UrlMethodPair).url === 'string'
    ) {
        return (href as UrlMethodPair).url;
    }

    return null;
};

const normalizePath = (path: string): string => {
    try {
        return (
            new URL(
                path,
                window.location.origin,
            ).pathname.replace(/\/+$/, '') || '/'
        );
    } catch {
        return (
            path.split('?')[0].replace(/\/+$/, '') || '/'
        );
    }
};

export function useCurrentUrl() {
    const page = usePage();

    const isCurrentUrl = (
        href?: NavigationHref,
    ): boolean => {
        const hrefUrl = getHrefUrl(href);

        if (!hrefUrl) {
            return false;
        }

        const currentPath = normalizePath(page.url);
        const itemPath = normalizePath(hrefUrl);

        return currentPath === itemPath;
    };

    const isCurrentOrParentUrl = (
        href?: NavigationHref,
    ): boolean => {
        const hrefUrl = getHrefUrl(href);

        if (!hrefUrl) {
            return false;
        }

        const currentPath = normalizePath(page.url);
        const itemPath = normalizePath(hrefUrl);

        return (
            currentPath === itemPath ||
            currentPath.startsWith(`${itemPath}/`)
        );
    };

    return {
        isCurrentUrl,
        isCurrentOrParentUrl,
    };
}
