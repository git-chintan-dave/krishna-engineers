<script lang="ts" setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    Database,
    LoaderCircle,
    LockKeyhole,
    Palette,
    UserRound,
} from '@lucide/vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { download as downloadDatabase } from '@/routes/settings/database';
import type { NavItem } from '@/types';

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: UserRound,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: LockKeyhole,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
    },
];

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();

const isSuperAdmin = computed(
    () => page.props.auth.user?.role === 'super_admin',
);

/*
|--------------------------------------------------------------------------
| Database Backup
|--------------------------------------------------------------------------
*/

const isDownloading = ref(false);

const downloadBackup = (): void => {
    if (isDownloading.value) {
        return;
    }

    isDownloading.value = true;

    const iframe = document.createElement('iframe');

    iframe.style.display = 'none';
    iframe.src = downloadDatabase().url;

    document.body.appendChild(iframe);

    // Reset the visual state after initiating the download.
    window.setTimeout(() => {
        iframe.remove();
        isDownloading.value = false;
    }, 5000);
};
</script>

<template>
    <div class="mx-auto w-full max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <Heading
            description="Manage your profile, security, appearance, and preferences."
            title="Settings"
        />

        <div
            class="grid grid-cols-1 gap-8 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-12"
        >
            <!-- Sidebar -->
            <aside class="space-y-5">
                <div class="space-y-2">
                    <p
                        class="px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Preferences
                    </p>

                    <nav aria-label="Settings navigation" class="space-y-1">
                        <Button
                            v-for="(item, index) in sidebarNavItems"
                            :key="toUrl(item.href)"
                            :class="[
                                'h-10 w-full justify-start gap-3 px-3',
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-muted font-semibold text-foreground'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            as-child
                            variant="ghost"
                        >
                            <Link :href="item.href">
                                <component
                                    :is="item.icon"
                                    aria-hidden="true"
                                    class="size-4 shrink-0"
                                />

                                <span class="flex-1 text-left">
                                    {{ item.title }}
                                </span>

                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    #{{ index + 1 }}
                                </span>
                            </Link>
                        </Button>
                    </nav>
                </div>

                <!-- Admin Tools -->
                <template v-if="isSuperAdmin">
                    <Separator />

                    <div class="space-y-3">
                        <p
                            class="px-3 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Administration
                        </p>

                        <div class="space-y-4 rounded-lg border p-4">
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted"
                                >
                                    <Database
                                        aria-hidden="true"
                                        class="size-4"
                                    />
                                </div>

                                <div class="min-w-0 space-y-1">
                                    <p class="text-sm font-medium">
                                        Database backup
                                    </p>

                                    <p
                                        class="text-xs leading-relaxed text-muted-foreground"
                                    >
                                        Export a SQL snapshot for safekeeping or
                                        recovery.
                                    </p>
                                </div>
                            </div>

                            <Button
                                class="w-full"
                                :disabled="isDownloading"
                                size="sm"
                                variant="outline"
                                @click="downloadBackup"
                            >
                                <LoaderCircle
                                    v-if="isDownloading"
                                    aria-hidden="true"
                                    class="mr-2 size-4 animate-spin"
                                />

                                <Database
                                    v-else
                                    aria-hidden="true"
                                    class="mr-2 size-4"
                                />

                                {{
                                    isDownloading
                                        ? 'Preparing backup...'
                                        : 'Download SQL Backup'
                                }}
                            </Button>

                            <p
                                class="text-xs leading-relaxed text-muted-foreground"
                            >
                                Only super administrators can access this
                                operation.
                            </p>
                        </div>
                    </div>
                </template>
            </aside>

            <!-- Content -->
            <main class="min-w-0">
                <section class="w-full max-w-3xl space-y-8">
                    <slot />
                </section>
            </main>
        </div>
    </div>
</template>
