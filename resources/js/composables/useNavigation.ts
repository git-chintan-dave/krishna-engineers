import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { NavItem } from '@/types';

type NavigationHref = NavItem['href'];

type UrlMethodPair = {
    url: string;
    method?: string;
};

const getHrefUrl = (href?: NavigationHref): string | null => {
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

const getPath = (href?: NavigationHref): string | null => {
    const hrefUrl = getHrefUrl(href);

    if (!hrefUrl) {
        return null;
    }

    try {
        return (
            new URL(hrefUrl, window.location.origin).pathname.replace(
                /\/+$/,
                '',
            ) || '/'
        );
    } catch {
        return null;
    }
};

const normalizePath = (path: string): string => {
    try {
        return (
            new URL(path, window.location.origin).pathname.replace(
                /\/+$/,
                '',
            ) || '/'
        );
    } catch {
        return path.split('?')[0].replace(/\/+$/, '') || '/';
    }
};

export function useNavigation() {
    const page = usePage();

    const currentPath = computed(() => {
        return normalizePath(page.url);
    });

    const isPathActive = (href?: NavigationHref): boolean => {
        const itemPath = getPath(href);

        if (!itemPath) {
            return false;
        }

        const current = currentPath.value;

        return current === itemPath || current.startsWith(`${itemPath}/`);
    };

    const isItemActive = (item: NavItem): boolean => {
        if (isPathActive(item.href)) {
            return true;
        }

        return item.items?.some((subItem) => isItemActive(subItem)) ?? false;
    };

    return {
        currentPath,
        isPathActive,
        isItemActive,
    };
}
