<script lang="ts" setup>
import {Link, router, usePage} from '@inertiajs/vue3';
import {ChevronRight, Menu} from '@lucide/vue';
import {computed, onBeforeUnmount, onMounted, reactive, ref, watch,} from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import {Avatar, AvatarFallback, AvatarImage} from '@/components/ui/avatar';
import {Button} from '@/components/ui/button';
import {Collapsible, CollapsibleContent, CollapsibleTrigger,} from '@/components/ui/collapsible';
import {DropdownMenu, DropdownMenuContent, DropdownMenuTrigger,} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger,} from '@/components/ui/sheet';
import {Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,} from '@/components/ui/tooltip';

import UserMenuContent from '@/components/UserMenuContent.vue';

import {useCurrentUrl} from '@/composables/useCurrentUrl';
import {useListingPreferenceNavigation} from '@/composables/useListingPreferenceNavigation';
import {getInitials} from '@/composables/useInitials';
import {getMainNavItems, rightNavItems} from '@/config/navigation';
import {toUrl} from '@/lib/utils';

import type {BreadcrumbItem, NavItem} from '@/types';
import {dashboard} from '@/routes';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const mainNavItems = computed(() =>
    getMainNavItems(page.props.auth?.user?.role),
);

const { isCurrentOrParentUrl } = useCurrentUrl();
const { getNavigationHref } = useListingPreferenceNavigation();

const activeItemStyles = 'bg-accent text-accent-foreground';

const hasChildren = (item: NavItem): boolean => Boolean(item.items?.length);

const isItemActive = (item: NavItem): boolean => {
    if (item.href && isCurrentOrParentUrl(item.href)) {
        return true;
    }

    return item.items?.some(isItemActive) ?? false;
};

/*
|--------------------------------------------------------------------------
| Navigation Shortcuts
|--------------------------------------------------------------------------
|
| Alt + Shift + 1-9:
|   Navigate to a direct item or open a navigation group.
|
| After opening a group with a shortcut:
|   Press 1-9 within two seconds to navigate to a child item.
|
| Arrow keys, Enter and Escape remain available to the native menu controls.
|
*/

const MAX_NAVIGATION_SHORTCUTS = 9;
const SUBMENU_SHORTCUT_TIMEOUT = 2000;

const pendingSubmenuIndex = ref<number | null>(null);
const pendingSubmenuTitle = ref<string | null>(null);
let submenuShortcutTimer: ReturnType<typeof setTimeout> | undefined;

const getNavigationShortcut = (index: number): string | null =>
    index >= 0 && index < MAX_NAVIGATION_SHORTCUTS
        ? String(index + 1)
        : null;

const getNavigationShortcutLabel = (index: number): string | null => {
    const number = getNavigationShortcut(index);

    return number ? `Alt + Shift + ${number}` : null;
};

const clearPendingSubmenuShortcut = (): void => {
    if (submenuShortcutTimer !== undefined) {
        clearTimeout(submenuShortcutTimer);
        submenuShortcutTimer = undefined;
    }

    pendingSubmenuIndex.value = null;
    pendingSubmenuTitle.value = null;
};

const startPendingSubmenuShortcut = (
    index: number,
    item: NavItem,
): void => {
    clearPendingSubmenuShortcut();

    pendingSubmenuIndex.value = index;
    pendingSubmenuTitle.value = item.title;

    submenuShortcutTimer = setTimeout(() => {
        clearPendingSubmenuShortcut();
    }, SUBMENU_SHORTCUT_TIMEOUT);
};

const navigateToSubmenu = (
    mainItem: NavItem,
    submenuIndex: number,
): boolean => {
    const submenuItem = mainItem.items?.[submenuIndex];

    if (!submenuItem?.href) {
        return false;
    }

    const desktop = window.matchMedia('(min-width: 1024px)').matches;

    if (desktop && pendingSubmenuIndex.value !== null) {
        const trigger = getDesktopNavigationTrigger(
            pendingSubmenuIndex.value,
        );

        if (trigger?.getAttribute('aria-expanded') === 'true') {
            trigger.click();
        }

        // Remove focus styling from the navigation trigger.
        trigger?.blur();
    }

    clearPendingSubmenuShortcut();
    mobileMenuState.value = false;

    router.visit(getNavigationHref(submenuItem.href));

    return true;
};

const getDesktopNavigationTrigger = (
    index: number,
): HTMLElement | null =>
    document.querySelector<HTMLElement>(
        `[data-navigation-shortcut-index="${index}"]`,
    );

const handleNavigationShortcut = (event: KeyboardEvent): void => {
    if (
        event.defaultPrevented ||
        event.repeat ||
        event.isComposing
    ) {
        return;
    }

    /*
     * Second step:
     * After Alt + Shift + N opens a group, pressing a number without
     * modifiers navigates to the corresponding child item.
     *
     * Non-number keys are left alone so native arrow-key navigation,
     * Enter and Escape continue working.
     */
    if (
        pendingSubmenuIndex.value !== null &&
        !event.altKey &&
        !event.shiftKey &&
        !event.ctrlKey &&
        !event.metaKey
    ) {
        const numberMatch = /^Digit([1-9])$/.exec(event.code);

        if (numberMatch) {
            const mainItem =
                mainNavItems.value[pendingSubmenuIndex.value];

            if (mainItem && hasChildren(mainItem)) {
                const submenuIndex = Number(numberMatch[1]) - 1;

                if (navigateToSubmenu(mainItem, submenuIndex)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }

            clearPendingSubmenuShortcut();
            return;
        }

        if (event.key === 'Escape') {
            clearPendingSubmenuShortcut();
            return;
        }

        // Leave arrows, Enter, Tab and other keys to the menu controls.
        clearPendingSubmenuShortcut();
    }

    /*
     * First step:
     * Main navigation shortcuts use physical number keys because
     * event.key can be a symbol (for example, @) on some layouts.
     */
    if (
        !event.altKey ||
        !event.shiftKey ||
        event.ctrlKey ||
        event.metaKey
    ) {
        return;
    }

    const numberMatch = /^Digit([1-9])$/.exec(event.code);

    if (!numberMatch) {
        return;
    }

    const index = Number(numberMatch[1]) - 1;
    const item = mainNavItems.value[index];

    if (!item) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (!hasChildren(item)) {
        clearPendingSubmenuShortcut();

        if (item.href) {
            mobileMenuState.value = false;
            router.visit(getNavigationHref(item.href));
        }

        return;
    }

    startPendingSubmenuShortcut(index, item);

    if (window.matchMedia('(min-width: 1024px)').matches) {
        const trigger = getDesktopNavigationTrigger(index);

        if (trigger) {
            trigger.focus();

            if (trigger.getAttribute('aria-expanded') !== 'true') {
                trigger.click();
            }
        }

        return;
    }

    mobileMenuState.value = true;
    mobileOpenGroups[item.title] = true;
};

onMounted(() => {
    window.addEventListener('keydown', handleNavigationShortcut);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleNavigationShortcut);
    clearPendingSubmenuShortcut();
});

/*
|--------------------------------------------------------------------------
| Mobile Navigation Groups
|--------------------------------------------------------------------------
*/

const mobileOpenGroups = reactive<Record<string, boolean>>({});

const isMobileGroupOpen = (item: NavItem): boolean =>
    mobileOpenGroups[item.title] ?? false;

const setMobileGroupOpen = (item: NavItem, open: boolean): void => {
    mobileOpenGroups[item.title] = open;

    if (!open && pendingSubmenuTitle.value === item.title) {
        clearPendingSubmenuShortcut();
    }
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

watch(
    () => page.url,
    () => {
        syncMobileGroups();
        mobileMenuState.value = false;
        clearPendingSubmenuShortcut();
    },
    { immediate: true },
);
</script>

<template>
    <!-- Main Header -->
    <header
        class="sticky top-0 z-40 h-16 w-full border-b border-border bg-background/95 backdrop-blur-sm supports-[backdrop-filter]:bg-background/80"
    >
        <div
            class="mx-auto flex h-16 w-full min-w-0 items-center px-4 md:px-6 md:max-w-7xl"
        >
            <!-- Mobile / Tablet Menu -->
            <div class="shrink-0 lg:hidden">
                <Sheet
                    v-model:open="mobileMenuState"
                    @update:open="
                            (open) => {
                                if (!open) clearPendingSubmenuShortcut();
                            }
                        "
                >
                    <SheetTrigger as-child>
                        <Button
                            class="mr-2 size-9 cursor-pointer"
                            size="icon"
                            variant="ghost"
                        >
                            <Menu aria-hidden="true" class="size-5" />
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

                        <SheetHeader
                            class="shrink-0 flex-row justify-start space-y-0 text-left"
                        >
                            <AppLogo />
                        </SheetHeader>

                        <div class="flex min-h-0 flex-1 flex-col">
                            <nav
                                aria-label="Mobile navigation"
                                class="min-h-0 flex-1 overflow-y-auto py-6 pt-0 pr-2"
                            >
                                <div
                                    v-if="pendingSubmenuTitle"
                                    class="mb-3 rounded-md border border-primary/20 bg-primary/5 px-3 py-2 text-xs text-muted-foreground"
                                    role="status"
                                >
                                        <span class="font-medium text-foreground">
                                            {{ pendingSubmenuTitle }}
                                        </span>
                                    <span>
                                            — press a submenu number (1–9)
                                            within 2 seconds, or use the arrow
                                            keys.
                                        </span>
                                </div>

                                <div class="space-y-1">
                                    <template
                                        v-for="(item, index) in mainNavItems"
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
                                            :href="
                                                    getNavigationHref(item.href)
                                                "
                                            :title="
                                                    getNavigationShortcutLabel(
                                                        index,
                                                    ) ?? undefined
                                                "
                                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                        >
                                            <component
                                                :is="item.icon"
                                                v-if="item.icon"
                                                aria-hidden="true"
                                                class="size-5 shrink-0"
                                            />
                                            <span class="min-w-0 flex-1">
                                                    {{ item.title }}
                                                </span>
                                            <kbd
                                                v-if="
                                                        getNavigationShortcut(
                                                            index,
                                                        )
                                                    "
                                                class="shrink-0 rounded border border-border px-1.5 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                            >
                                                {{
                                                    getNavigationShortcut(
                                                        index,
                                                    )
                                                }}
                                            </kbd>
                                        </Link>

                                        <!-- Navigation Group -->
                                        <Collapsible
                                            v-else
                                            :open="isMobileGroupOpen(item)"
                                            class="group/mobile-collapsible"
                                            @update:open="
                                                    (open) =>
                                                        setMobileGroupOpen(
                                                            item,
                                                            open,
                                                        )
                                                "
                                        >
                                            <CollapsibleTrigger as-child>
                                                <button
                                                    :class="
                                                            isItemActive(item)
                                                                ? activeItemStyles
                                                                : ''
                                                        "
                                                    :title="
                                                            getNavigationShortcutLabel(
                                                                index,
                                                            ) ?? undefined
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
                                                    <span class="min-w-0 flex-1 text-left">
                                                            {{ item.title }}
                                                        </span>
                                                    <kbd
                                                        v-if="
                                                                getNavigationShortcut(
                                                                    index,
                                                                )
                                                            "
                                                        class="shrink-0 rounded border border-border px-1.5 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                                    >
                                                        {{
                                                            getNavigationShortcut(
                                                                index,
                                                            )
                                                        }}
                                                    </kbd>
                                                    <ChevronRight
                                                        aria-hidden="true"
                                                        class="size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/mobile-collapsible:rotate-90"
                                                    />
                                                </button>
                                            </CollapsibleTrigger>

                                            <CollapsibleContent>
                                                <div
                                                    class="ml-3 space-y-1 border-l pl-3"
                                                >
                                                    <Link
                                                        v-for="(subItem, subIndex) in item.items"
                                                        :key="subItem.title"
                                                        :class="
                                                                isItemActive(
                                                                    subItem,
                                                                )
                                                                    ? activeItemStyles
                                                                    : ''
                                                            "
                                                        :href="
                                                                subItem.href
                                                                    ? getNavigationHref(
                                                                          subItem.href,
                                                                      )
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
                                                        <span class="min-w-0 flex-1">
                                                                {{ subItem.title }}
                                                            </span>
                                                        <kbd
                                                            v-if="pendingSubmenuIndex === index"
                                                            class="rounded border border-border px-1.5 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                                        >
                                                            {{ subIndex + 1 }}
                                                        </kbd>
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
                            v-for="(item, index) in mainNavItems"
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
                                        'h-9 cursor-pointer gap-2 px-3',
                                    ]"
                                :href="getNavigationHref(item.href)"
                                :title="
                                        getNavigationShortcutLabel(index) ??
                                        undefined
                                    "
                            >
                                <component
                                    :is="item.icon"
                                    v-if="item.icon"
                                    aria-hidden="true"
                                    class="size-4 shrink-0"
                                />
                                <span>{{ item.title }}</span>
                                <kbd
                                    v-if="getNavigationShortcut(index)"
                                    class="rounded border border-border/70 px-1 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                >
                                    {{ getNavigationShortcut(index) }}
                                </kbd>

                                <span
                                    v-if="isItemActive(item)"
                                    aria-hidden="true"
                                    class="absolute inset-x-0 bottom-0 h-0.5 bg-black dark:bg-white"
                                />
                            </Link>

                            <!-- Navigation Group -->
                            <template v-else>
                                <NavigationMenuTrigger
                                    :data-navigation-shortcut-index="index"
                                    :class="[
                                            navigationMenuTriggerStyle(),
                                            isItemActive(item)
                                                ? activeItemStyles
                                                : '',
                                            'h-9 cursor-pointer gap-2 px-3',
                                        ]"
                                    :title="
                                            getNavigationShortcutLabel(index) ??
                                            undefined
                                        "
                                >
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        aria-hidden="true"
                                        class="size-4 shrink-0"
                                    />
                                    <span>{{ item.title }}</span>
                                    <kbd
                                        v-if="getNavigationShortcut(index)"
                                        class="rounded border border-border/70 px-1 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                    >
                                        {{ getNavigationShortcut(index) }}
                                    </kbd>
                                </NavigationMenuTrigger>

                                <NavigationMenuContent>
                                    <ul class="grid min-w-[190px]">
                                        <li
                                            v-for="(subItem, subIndex) in item.items"
                                            :key="subItem.title"
                                        >
                                            <NavigationMenuLink
                                                :active="
                                                        isItemActive(subItem)
                                                    "
                                                as-child
                                            >
                                                <Link
                                                    :href="
                                                            subItem.href
                                                                ? getNavigationHref(
                                                                      subItem.href,
                                                                  )
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
                                                        class="min-w-0 flex-1 text-sm font-medium whitespace-nowrap"
                                                    >
                                                            {{ subItem.title }}
                                                        </span>
                                                    <kbd
                                                        v-if="pendingSubmenuIndex === index"
                                                        class="rounded border border-border px-1.5 py-0.5 font-mono text-[10px] leading-none text-muted-foreground"
                                                    >
                                                        {{ subIndex + 1 }}
                                                    </kbd>
                                                </Link>
                                            </NavigationMenuLink>
                                        </li>
                                    </ul>
                                </NavigationMenuContent>

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
                v-if="props.breadcrumbs.length > 0"
                :breadcrumbs="props.breadcrumbs"
            />
        </div>
    </div>
</template>
