<script setup lang="ts">
import {Link} from '@inertiajs/vue3';
import {ArrowUpRight, LayoutGrid, Sparkles} from '@lucide/vue';
import {computed} from 'vue';

import {useListingPreferenceNavigation} from '@/composables/useListingPreferenceNavigation';
import {dashboardPage} from '@/config/dashboard';
import {getMainNavItems} from '@/config/navigation';
import {dashboard} from '@/routes';
import type {NavItem} from '@/types';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    role: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type QuickAction = {
    title: string;
    description: string;
    href: string;
    icon: NonNullable<NavItem['icon']>;
};

type QuickActionGroup = {
    title: string;
    description: string;
    icon: NonNullable<NavItem['icon']>;
    items: QuickAction[];
};

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const { getNavigationHref } =
    useListingPreferenceNavigation();

const navigation = computed(() =>
    getMainNavItems(props.role),
);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getHref = (href: unknown): string | undefined => {
    if (typeof href === 'string') {
        return href;
    }

    if (
        typeof href === 'object' &&
        href !== null &&
        'url' in href &&
        typeof href.url === 'string'
    ) {
        return href.url;
    }

    if (
        typeof href === 'object' &&
        href !== null &&
        'href' in href &&
        typeof href.href === 'string'
    ) {
        return href.href;
    }

    return undefined;
};

/*
|--------------------------------------------------------------------------
| Quick Actions
|--------------------------------------------------------------------------
*/

const quickActionGroups = computed<QuickActionGroup[]>(() => {
    const groups: QuickActionGroup[] = [];

    for (const item of navigation.value) {
        if (item.title === 'Dashboard') {
            continue;
        }

        const children = item.items ?? [item];

        const actions: QuickAction[] = children.flatMap((child) => {
            const href = getHref(child.href);

            if (!href) {
                return [];
            }

            return [
                {
                    title: child.title,
                    description: child.description ?? '',
                    href,
                    icon: child.icon ?? item.icon ?? LayoutGrid,
                },
            ];
        });

        if (actions.length === 0) {
            continue;
        }

        groups.push({
            title: item.items ? item.title : 'Your Workspace',
            description: item.items
                ? (item.description ?? '')
                : 'Access your daily work and assigned activities.',
            icon: item.icon ?? LayoutGrid,
            items: actions,
        });
    }

    return groups;
});

const totalActions = computed(() =>
    quickActionGroups.value.reduce(
        (total, group) => total + group.items.length,
        0,
    ),
);
</script>

<template>
    <div
        class="mx-auto w-full min-w-0 max-w-screen-2xl space-y-8 p-4 sm:p-6 lg:space-y-10 lg:p-8"
    >
        <!-- Welcome Header -->
        <section
            class="relative isolate overflow-hidden rounded-2xl border bg-card p-6 shadow-sm sm:p-8"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-24 -z-10 size-72 rounded-full bg-primary/10 blur-3xl"
            />

            <div
                class="pointer-events-none absolute -bottom-28 right-1/4 -z-10 size-56 rounded-full bg-primary/5 blur-3xl"
            />

            <div
                class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center"
            >
                <div class="min-w-0 space-y-3">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border bg-background/80 px-3 py-1.5 text-xs font-medium text-muted-foreground shadow-sm"
                    >
                        <Sparkles class="size-3.5 text-primary" />
                        Workshop workspace
                    </div>

                    <div class="space-y-2">
                        <h1
                            class="text-2xl font-semibold tracking-tight sm:text-3xl lg:text-4xl"
                        >
                            {{ dashboardPage.greeting }}
                        </h1>

                        <p
                            class="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                        >
                            {{ dashboardPage.description }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center gap-3 rounded-xl border bg-background/80 px-4 py-3 shadow-sm"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <LayoutGrid class="size-5" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-2xl font-semibold tabular-nums">
                            {{ totalActions }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Available modules
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="space-y-7">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <LayoutGrid class="size-4" />
                        </div>

                        <h2 class="text-xl font-semibold tracking-tight">
                            Quick Actions
                        </h2>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Everything you need to manage your workshop, all in one place.
                    </p>
                </div>

                <p class="text-xs font-medium text-muted-foreground">
                    {{ totalActions }} modules available
                </p>
            </div>

            <!-- Navigation Groups -->
            <div class="space-y-9">
                <section
                    v-for="group in quickActionGroups"
                    :key="group.title"
                    class="space-y-4"
                >
                    <!-- Group Header -->
                    <div class="flex items-center gap-3 border-b pb-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl border bg-muted/50"
                        >
                            <component
                                :is="group.icon"
                                class="size-[18px] text-foreground"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold tracking-wide">
                                {{ group.title }}
                            </h3>

                            <p
                                v-if="group.description"
                                class="mt-0.5 text-xs leading-5 text-muted-foreground"
                            >
                                {{ group.description }}
                            </p>
                        </div>

                        <span
                            class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                        >
                            {{ group.items.length }}
                        </span>
                    </div>

                    <!-- Action Cards -->
                    <div
                        class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                    >
                        <Link
                            v-for="action in group.items"
                            :key="action.title"
                            :href="getNavigationHref(action.href)"
                            class="group relative flex min-h-28 min-w-0 items-start gap-4 overflow-hidden rounded-xl border bg-card p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <!-- Module Icon -->
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-muted/70 text-muted-foreground transition-colors duration-200 group-hover:bg-primary/10 group-hover:text-primary"
                            >
                                <component
                                    :is="action.icon"
                                    class="size-5"
                                />
                            </div>

                            <!-- Module Details -->
                            <div class="min-w-0 flex-1 pt-0.5">
                                <h4
                                    class="truncate text-sm font-semibold transition-colors group-hover:text-primary"
                                >
                                    {{ action.title }}
                                </h4>

                                <p
                                    v-if="action.description"
                                    class="mt-1 line-clamp-2 text-xs leading-5 text-muted-foreground"
                                >
                                    {{ action.description }}
                                </p>

                                <p
                                    v-else
                                    class="mt-1 text-xs leading-5 text-muted-foreground"
                                >
                                    Open and manage {{ action.title.toLowerCase() }}.
                                </p>
                            </div>

                            <!-- Navigation Indicator -->
                            <div
                                class="mt-1 flex size-7 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-all duration-200 group-hover:bg-primary/10 group-hover:text-primary"
                            >
                                <ArrowUpRight
                                    class="size-4 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                />
                            </div>
                        </Link>
                    </div>
                </section>
            </div>

            <!-- Empty State -->
            <div
                v-if="quickActionGroups.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed px-6 py-16 text-center"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-muted"
                >
                    <LayoutGrid class="size-6 text-muted-foreground" />
                </div>

                <h3 class="mt-4 text-sm font-semibold">
                    No modules available
                </h3>

                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    There are no dashboard actions available for your current role.
                </p>
            </div>
        </section>
    </div>
</template>
