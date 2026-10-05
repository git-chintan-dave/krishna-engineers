<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronRight,
    Menu,
} from '@lucide/vue';
import {
    computed,
    reactive,
    ref,
    watch,
} from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';

import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';

import { Button } from '@/components/ui/button';

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

import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';

import {
    mainNavItems,
    rightNavItems,
} from '@/config/navigation';

import { dashboard } from '@/routes';

import type {
    BreadcrumbItem,
    NavItem,
} from '@/types';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(
    defineProps<Props>(),
    {
        breadcrumbs: () => [],
    },
);

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

const page = usePage();

const auth = computed(() => page.props.auth);

const {
    isCurrentOrParentUrl,
} = useCurrentUrl();

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const activeItemStyles =
    'bg-accent text-accent-foreground';

const hasChildren = (item: NavItem): boolean => {
    return Boolean(item.items?.length);
};

const isItemActive = (item: NavItem): boolean => {
    if (
        item.href &&
        isCurrentOrParentUrl(item.href)
    ) {
        return true;
    }

    return (
        item.items?.some((subItem) =>
            isItemActive(subItem),
        ) ?? false
    );
};

/*
|--------------------------------------------------------------------------
| Mobile Navigation State
|--------------------------------------------------------------------------
|
| Each mobile group has its own open state.
|
| Active groups are automatically opened when the
| current Inertia URL changes.
|
*/

const mobileOpenGroups = reactive<
    Record<string, boolean>
>({});

const isMobileGroupOpen = (
    item: NavItem,
): boolean => {
    return mobileOpenGroups[item.title] ?? false;
};

const setMobileGroupOpen = (
    item: NavItem,
    open: boolean,
): void => {
    mobileOpenGroups[item.title] = open;
};

const syncMobileGroups = (): void => {
    mainNavItems.forEach((item) => {
        if (!hasChildren(item)) {
            return;
        }

        if (isItemActive(item)) {
            mobileOpenGroups[item.title] = true;
        }
    });
};

watch(
    () => page.url,
    () => {
        syncMobileGroups();
    },
    {
        immediate: true,
    },
);

/*
|--------------------------------------------------------------------------
| Mobile Menu State
|--------------------------------------------------------------------------
*/

const mobileMenuState = ref(false);

const mobileMenuOpen = computed({
    get: () => mobileMenuState.value,
    set: (value: boolean) => {
        mobileMenuState.value = value;
    },
});

/*
|--------------------------------------------------------------------------
| Close Mobile Menu After Navigation
|--------------------------------------------------------------------------
*/

watch(
    () => page.url,
    () => {
        mobileMenuState.value = false;
    },
);
</script>

<template>
    <div
        class="w-full min-w-0 [scrollbar-gutter:stable]"
    >
        <!--
        |--------------------------------------------------------------------------
        | Main Header
        |--------------------------------------------------------------------------
        -->

        <header
            class="h-16 w-full border-b border-sidebar-border/80"
        >
            <div
                class="mx-auto flex h-16 w-full min-w-0 items-center px-4 md:max-w-7xl"
            >
                <!--
                |--------------------------------------------------------------------------
                | Mobile / Tablet Menu
                |--------------------------------------------------------------------------
                -->

                <div class="shrink-0 lg:hidden">
                    <Sheet v-model:open="mobileMenuOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 size-9 cursor-pointer"
                            >
                                <Menu
                                    class="size-5"
                                    aria-hidden="true"
                                />

                                <span class="sr-only">
                                    Open navigation menu
                                </span>
                            </Button>
                        </SheetTrigger>

                        <SheetContent
                            side="left"
                            class="flex w-[300px] flex-col p-2 sm:w-[340px]"
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

                            <div
                                class="flex min-h-0 flex-1 flex-col"
                            >
                                <!-- Scrollable Navigation -->

                                <nav
                                    class="min-h-0 flex-1 overflow-y-auto py-6 pr-2 pt-0"
                                    aria-label="Mobile navigation"
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
                                                :href="item.href"
                                                class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                                :class="
                                                    isItemActive(item)
                                                        ? activeItemStyles
                                                        : ''
                                                "
                                            >
                                                <component
                                                    v-if="item.icon"
                                                    :is="item.icon"
                                                    class="size-5 shrink-0"
                                                    aria-hidden="true"
                                                />

                                                <span>
                                                    {{ item.title }}
                                                </span>
                                            </Link>

                                            <!-- Navigation Group -->

                                            <Collapsible
                                                v-else
                                                :open="
                                                    isMobileGroupOpen(item)
                                                "
                                                class="group/mobile-collapsible"
                                                @update:open="
                                                    (open) =>
                                                        setMobileGroupOpen(
                                                            item,
                                                            open,
                                                        )
                                                "
                                            >
                                                <!-- Group Trigger -->

                                                <CollapsibleTrigger
                                                    as-child
                                                >
                                                    <button
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                                        :class="
                                                            isItemActive(item)
                                                                ? activeItemStyles
                                                                : ''
                                                        "
                                                    >
                                                        <component
                                                            v-if="item.icon"
                                                            :is="item.icon"
                                                            class="size-5 shrink-0"
                                                            aria-hidden="true"
                                                        />

                                                        <span>
                                                            {{ item.title }}
                                                        </span>

                                                        <ChevronRight
                                                            class="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/mobile-collapsible:rotate-90"
                                                            aria-hidden="true"
                                                        />
                                                    </button>
                                                </CollapsibleTrigger>

                                                <!-- Group Children -->

                                                <CollapsibleContent>
                                                    <div
                                                        class="ml-3 space-y-1 border-l pl-3"
                                                    >
                                                        <Link
                                                            v-for="subItem in item.items"
                                                            :key="
                                                                subItem.title
                                                            "
                                                            :href="
                                                                subItem.href!
                                                            "
                                                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                                            :class="
                                                                isItemActive(
                                                                    subItem,
                                                                )
                                                                    ? activeItemStyles
                                                                    : ''
                                                            "
                                                        >
                                                            <component
                                                                v-if="
                                                                    subItem.icon
                                                                "
                                                                :is="
                                                                    subItem.icon
                                                                "
                                                                class="size-4 shrink-0"
                                                                aria-hidden="true"
                                                            />

                                                            <span>
                                                                {{
                                                                    subItem.title
                                                                }}
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
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors hover:bg-accent"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="size-5 shrink-0"
                                            aria-hidden="true"
                                        />

                                        <span>
                                            {{ item.title }}
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <!--
                |--------------------------------------------------------------------------
                | Logo
                |--------------------------------------------------------------------------
                -->

                <Link
                    :href="dashboard()"
                    class="flex shrink-0 items-center gap-x-2"
                >
                    <AppLogo />
                </Link>

                <!--
                |--------------------------------------------------------------------------
                | Desktop Navigation
                |--------------------------------------------------------------------------
                -->

                <div
                    class="hidden h-full min-w-0 flex-1 lg:flex"
                >
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
                                    v-if="
                                        !hasChildren(item) &&
                                        item.href
                                    "
                                    :href="item.href"
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        isItemActive(item)
                                            ? activeItemStyles
                                            : '',
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 size-4"
                                        aria-hidden="true"
                                    />

                                    {{ item.title }}

                                    <span
                                        v-if="isItemActive(item)"
                                        class="absolute inset-x-0 bottom-0 h-0.5 bg-black dark:bg-white"
                                        aria-hidden="true"
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
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="mr-2 size-4"
                                            aria-hidden="true"
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
                                                    as-child
                                                    :active="
                                                        isItemActive(
                                                            subItem,
                                                        )
                                                    "
                                                >
                                                    <Link
                                                        :href="
                                                            subItem.href!
                                                        "
                                                        class="flex flex-row flex-nowrap items-center gap-3 rounded-md px-3 py-2.5"
                                                    >
                                                        <component
                                                            v-if="
                                                                subItem.icon
                                                            "
                                                            :is="
                                                                subItem.icon
                                                            "
                                                            class="size-4 shrink-0"
                                                            aria-hidden="true"
                                                        />

                                                        <span
                                                            class="whitespace-nowrap text-sm font-medium"
                                                        >
                                                            {{
                                                                subItem.title
                                                            }}
                                                        </span>
                                                    </Link>
                                                </NavigationMenuLink>
                                            </li>
                                        </ul>
                                    </NavigationMenuContent>

                                    <!-- Active Group Indicator -->

                                    <span
                                        v-if="isItemActive(item)"
                                        class="absolute inset-x-0 bottom-0 h-0.5 bg-black dark:bg-white"
                                        aria-hidden="true"
                                    />
                                </template>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!--
                |--------------------------------------------------------------------------
                | Right Side
                |--------------------------------------------------------------------------
                -->

                <div
                    class="ml-auto flex shrink-0 items-center space-x-2"
                >
                    <!-- External Links -->

                    <div
                        v-if="rightNavItems.length"
                        class="hidden items-center space-x-1 lg:flex"
                    >
                        <template
                            v-for="item in rightNavItems"
                            :key="item.title"
                        >
                            <TooltipProvider
                                :delay-duration="0"
                            >
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            as-child
                                            class="group size-9 cursor-pointer"
                                        >
                                            <a
                                                :href="
                                                    toUrl(item.href)
                                                "
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <span
                                                    class="sr-only"
                                                >
                                                    {{ item.title }}
                                                </span>

                                                <component
                                                    :is="item.icon"
                                                    class="size-5 opacity-80 group-hover:opacity-100"
                                                    aria-hidden="true"
                                                />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>

                                    <TooltipContent>
                                        <p>
                                            {{ item.title }}
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                    </div>

                    <!-- User Menu -->

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto cursor-pointer rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />

                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{
                                            getInitials(
                                                auth.user?.name,
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>

                                <span class="sr-only">
                                    Open user menu
                                </span>
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            class="w-56"
                        >
                            <UserMenuContent
                                :user="auth.user"
                            />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <!--
        |--------------------------------------------------------------------------
        | Breadcrumbs
        |--------------------------------------------------------------------------
        |
        | Keep this row mounted at all times so navigation between
        | pages never changes the vertical position of the content.
        |
        -->

        <div
            class="h-12 w-full border-b border-sidebar-border/70"
        >
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
