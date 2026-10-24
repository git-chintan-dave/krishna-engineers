
<script lang="ts" setup>
import {Link, usePage} from '@inertiajs/vue3';
import {ChevronRight, Menu} from '@lucide/vue';
import {computed, onMounted, reactive, ref, watch} from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {Avatar, AvatarFallback, AvatarImage} from '@/components/ui/avatar';
import {Button} from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import UserMenuContent from '@/components/UserMenuContent.vue';

import {useCurrentUrl} from '@/composables/useCurrentUrl';
import {getInitials} from '@/composables/useInitials';
import {
    getListingPreferenceFeatures,
} from '@/config/listingPreferences';
import {getMainNavItems, rightNavItems} from '@/config/navigation';
import {toUrl} from '@/lib/utils';

import type {BreadcrumbItem, NavItem} from '@/types';
import {dashboard} from '@/routes';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

type SavedListingPreferences = {
    perPage?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
};

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const LISTING_PREFERENCES_PREFIX = 'listing-preferences:';
const ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

/*
|--------------------------------------------------------------------------
| Page & Authentication
|--------------------------------------------------------------------------
*/

const page = usePage();

const auth = computed(() => page.props.auth);

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const mainNavItems = computed(() =>
    getMainNavItems(page.props.auth?.user?.role),
);

const {isCurrentOrParentUrl} = useCurrentUrl();

const activeItemStyles = 'bg-accent text-accent-foreground';

const hasChildren = (item: NavItem): boolean =>
    Boolean(item.items?.length);

const isItemActive = (item: NavItem): boolean => {
    if (item.href && isCurrentOrParentUrl(item.href)) {
        return true;
    }

    return item.items?.some(isItemActive) ?? false;
};

/*
|--------------------------------------------------------------------------
| Listing Preference Navigation
|--------------------------------------------------------------------------
*/

const isClientReady = ref(false);

onMounted(() => {
    isClientReady.value = true;
});

const resolveNavigationHref = (href: NavItem['href']): string => {
    if (typeof href === 'string') {
        return href;
    }

    if (href && typeof href === 'object') {
        if ('url' in href && typeof href.url === 'string') {
            return href.url;
        }

        if ('href' in href && typeof href.href === 'string') {
            return href.href;
        }
    }

    return '';
};

const getNavigationHref = (href: NavItem['href']): string => {
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

        // The listing has no active preference features.
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

        const preferences =
            parsed as SavedListingPreferences;

        let hasRestoredPreference = false;

        /*
         * Restore page size only when enabled for this listing.
         */
        if (
            features.perPage &&
            typeof preferences.perPage === 'number' &&
            ALLOWED_PAGE_SIZES.includes(preferences.perPage)
        ) {
            url.searchParams.set(
                'per_page',
                String(preferences.perPage),
            );

            hasRestoredPreference = true;
        }

        /*
         * Restore sorting only when enabled for this listing.
         */
        if (
            features.sorting &&
            typeof preferences.sort === 'string' &&
            preferences.sort.length > 0 &&
            (
                preferences.direction === 'asc' ||
                preferences.direction === 'desc'
            )
        ) {
            url.searchParams.set('sort', preferences.sort);
            url.searchParams.set(
                'direction',
                preferences.direction,
            );

            hasRestoredPreference = true;
        }

        if (!hasRestoredPreference) {
            return destination;
        }

        // Start at the first page when restoring preferences.
        url.searchParams.set('page', '1');

        return `${url.pathname}${url.search}${url.hash}`;
    } catch {
        // Fall back to ordinary navigation if storage is unavailable.
        return destination;
    }
};

/*
|--------------------------------------------------------------------------
| Mobile Navigation Groups
|--------------------------------------------------------------------------
*/

const mobileOpenGroups = reactive<Record<string, boolean>>({});

const isMobileGroupOpen = (item: NavItem): boolean =>
    mobileOpenGroups[item.title] ?? false;

const setMobileGroupOpen = (
    item: NavItem,
    open: boolean,
): void => {
    mobileOpenGroups[item.title] = open;
};

const syncMobileGroups = (): void => {
    mainNavItems.value.forEach((item) => {
        if (hasChildren(item) && isItemActive(item)) {
            mobileOpenGroups[item.title] = true;
        }
    });
};

/*
|--------------------------------------------------------------------------
| Mobile Menu
|--------------------------------------------------------------------------
*/

const mobileMenuState = ref(false);

/*
|--------------------------------------------------------------------------
| Navigation Watcher
|--------------------------------------------------------------------------
*/

watch(
    () => page.url,
    () => {
        syncMobileGroups();
        mobileMenuState.value = false;
    },
    {immediate: true},
);
</script>

<template>
    <div class="w-full min-w-0 [scrollbar-gutter:stable]">
        <!-- Main Header -->
        <header class="h-16 w-full border-b border-sidebar-border/80">
            <div
                class="mx-auto flex h-16 w-full min-w-0 items-center px-4 md:max-w-7xl"
            >
                <!-- Mobile / Tablet Menu -->
                <div class="shrink-0 lg:hidden">
                    <Sheet v-model:open="mobileMenuState">
                        <SheetTrigger as-child>
                            <Button
                                class="mr-2 size-9 cursor-pointer"
                                size="icon"
                                variant="ghost"
                            >
                                <Menu
                                    aria-hidden="true"
                                    class="size-5"
                                />
                                <span class="sr-only">
                                    Open navigation menu
                                </span>
                            </Button>
                        </SheetTrigger>

                        <SheetContent
                            class="flex w-[300px] flex-col p-2 sm:w-[340px]"
                            side="left"
                        >
                            <SheetTitle class="sr-only">
                                Navigation menu
                            </SheetTitle>

                            <!-- Mobile Logo -->
                            <SheetHeader
                                class="shrink-0 flex-row justify-start space-y-0 text-left"
                            >
                                <AppLogo />
                            </SheetHeader>

                            <!-- Navigation -->
                            <div class="flex min-h-0 flex-1 flex-col">
                                <nav
                                    aria-label="Mobile navigation"
                                    class="min-h-0 flex-1 overflow-y-auto py-6 pt-0 pr-2"
                                >
                                    <div class="space-y-1">
                                        <template
                                            v-for="item in mainNavItems"
                                            :key="item.title"
                                        >
                                            <!-- Direct Navigation Item -->
                                            <Link
                                                v-if="
                                                    !hasChildren(item) &&
                                                    item.href
                                                "
                                                :class="
                                                    isItemActive(item)
                                                        ? activeItemStyles
                                                        : ''
                                                "
                                                :href="getNavigationHref(item.href)"
                                                class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                            >
                                                <component
                                                    :is="item.icon"
                                                    v-if="item.icon"
                                                    aria-hidden="true"
                                                    class="size-5 shrink-0"
                                                />
                                                <span>{{ item.title }}</span>
                                            </Link>

                                            <!-- Navigation Group -->
                                            <Collapsible
                                                v-else
                                                :open="isMobileGroupOpen(item)"
                                                class="group/mobile-collapsible"
                                                @update:open="
                                                    (open) =>
                                                        setMobileGroupOpen(item, open)
                                                "
                                            >
                                                <CollapsibleTrigger as-child>
                                                    <button
                                                        :class="
                                                            isItemActive(item)
                                                                ? activeItemStyles
                                                                : ''
                                                        "
                                                        class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                                        type="button"
                                                    >
                                                        <component
                                                            :is="item.icon"
                                                            v-if="item.icon"
                                                            aria-hidden="true"
                                                            class="size-5 shrink-0"
                                                        />
                                                        <span>{{ item.title }}</span>
                                                        <ChevronRight
                                                            aria-hidden="true"
                                                            class="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/mobile-collapsible:rotate-90"
                                                        />
                                                    </button>
                                                </CollapsibleTrigger>

                                                <CollapsibleContent>
                                                    <div
                                                        class="ml-3 space-y-1 border-l pl-3"
                                                    >
                                                        <Link
                                                            v-for="subItem in item.items"
                                                            :key="subItem.title"
                                                            :class="
                                                                isItemActive(subItem)
                                                                    ? activeItemStyles
                                                                    : ''
                                                            "
                                                            :href="
                                                                subItem.href
                                                                    ? getNavigationHref(subItem.href)
                                                                    : '#'
                                                            "
                                                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                                        >
                                                            <component
                                                                :is="subItem.icon"
                                                                v-if="subItem.icon"
                                                                aria-hidden="true"
                                                                class="size-4 shrink-0"
                                                            />
                                                            <span>
                                                                {{ subItem.title }}
                                                            </span>
                                                        </Link>
                                                    </div>
                                                </CollapsibleContent>
                                            </Collapsible>
                                        </template>
                                    </div>
                                </nav>

                                <!-- External Links -->
                                <div
                                    v-if="rightNavItems.length"
                                    class="shrink-0 space-y-1 border-t pt-4"
                                >
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                        rel="noopener noreferrer"
                                        target="_blank"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            aria-hidden="true"
                                            class="size-5 shrink-0"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <!-- Logo -->
                <Link
                    :href="dashboard()"
                    class="flex shrink-0 items-center gap-x-2"
                >
                    <AppLogo />
                </Link>

                <!-- Desktop Navigation -->
                <div class="hidden h-full min-w-0 flex-1 lg:flex">
                    <NavigationMenu
                        class="ml-6 flex h-full items-stretch xl:ml-10"
                    >
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-1"
                        >
                            <NavigationMenuItem
                                v-for="item in mainNavItems"
                                :key="item.title"
                                class="relative flex h-full shrink-0 items-center"
                            >
                                <!-- Direct Navigation -->
                                <Link
                                    v-if="!hasChildren(item) && item.href"
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        isItemActive(item)
                                            ? activeItemStyles
                                            : '',
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="getNavigationHref(item.href)"
                                >
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        aria-hidden="true"
                                        class="mr-2 size-4"
                                    />
                                    {{ item.title }}

                                    <span
                                        v-if="isItemActive(item)"
                                        aria-hidden="true"
                                        class="absolute inset-x-0 bottom-0 h-0.5 bg-black dark:bg-white"
                                    />
                                </Link>

                                <!-- Navigation Group -->
                                <template v-else>
                                    <NavigationMenuTrigger
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            isItemActive(item)
                                                ? activeItemStyles
                                                : '',
                                            'h-9 cursor-pointer px-3',
                                        ]"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            aria-hidden="true"
                                            class="mr-2 size-4"
                                        />
                                        {{ item.title }}
                                    </NavigationMenuTrigger>

                                    <NavigationMenuContent>
                                        <ul class="grid min-w-[160px]">
                                            <li
                                                v-for="subItem in item.items"
                                                :key="subItem.title"
                                            >
                                                <NavigationMenuLink
                                                    :active="isItemActive(subItem)"
                                                    as-child
                                                >
                                                    <Link
                                                        :href="
                                                            subItem.href
                                                                ? getNavigationHref(subItem.href)
                                                                : '#'
                                                        "
                                                        class="flex flex-row flex-nowrap items-center gap-3 rounded-md px-3 py-2.5"
                                                    >
                                                        <component
                                                            :is="subItem.icon"
                                                            v-if="subItem.icon"
                                                            aria-hidden="true"
                                                            class="size-4 shrink-0"
                                                        />
                                                        <span
                                                            class="text-sm font-medium whitespace-nowrap"
                                                        >
                                                            {{ subItem.title }}
                                                        </span>
                                                    </Link>
                                                </NavigationMenuLink>
                                            </li>
                                        </ul>
                                    </NavigationMenuContent>

                                    <!-- Active Group Indicator -->
                                    <span
                                        v-if="isItemActive(item)"
                                        aria-hidden="true"
                                        class="absolute inset-x-0 bottom-0 h-0.5 bg-black dark:bg-white"
                                    />
                                </template>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- Right Side -->
                <div class="ml-auto flex shrink-0 items-center space-x-2">
                    <!-- External Links -->
                    <div
                        v-if="rightNavItems.length"
                        class="hidden items-center space-x-1 lg:flex"
                    >
                        <template
                            v-for="item in rightNavItems"
                            :key="item.title"
                        >
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            as-child
                                            class="group size-9 cursor-pointer"
                                            size="icon"
                                            variant="ghost"
                                        >
                                            <a
                                                :href="toUrl(item.href)"
                                                rel="noopener noreferrer"
                                                target="_blank"
                                            >
                                                <span class="sr-only">
                                                    {{ item.title }}
                                                </span>
                                                <component
                                                    :is="item.icon"
                                                    aria-hidden="true"
                                                    class="size-5 opacity-80 group-hover:opacity-100"
                                                />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>

                                    <TooltipContent>
                                        <p>{{ item.title }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                    </div>

                    <!-- User Menu -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                class="relative size-10 w-auto cursor-pointer rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                size="icon"
                                variant="ghost"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :alt="auth.user.name"
                                        :src="auth.user.avatar"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="sr-only">Open user menu</span>
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <!-- Breadcrumbs -->
        <div class="h-12 w-full border-b border-sidebar-border/70">
            <div
                class="mx-auto flex h-12 w-full min-w-0 items-center px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs
                    v-if="props.breadcrumbs.length > 1"
                    :breadcrumbs="props.breadcrumbs"
                />
            </div>
        </div>
    </div>
</template>
