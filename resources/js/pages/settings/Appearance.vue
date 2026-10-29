<script lang="ts" setup>
import {Head} from '@inertiajs/vue3';
import {Monitor} from '@lucide/vue';

import {dashboard} from '@/routes';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard().url,
            },
            {
                title: 'Appearance settings',
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance settings"/>

    <h1 class="sr-only">Appearance settings</h1>

    <div class="space-y-8">
        <Heading
            description="Personalize how Krishna Engineers looks on your device."
            title="Appearance"
            variant="small"
        />

        <section
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <div
                class="flex items-start gap-3 border-b border-border px-5 py-4 sm:px-6"
            >
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <Monitor class="size-5"/>
                </div>

                <div class="min-w-0">
                    <h2 class="font-semibold text-foreground">
                        Display preferences
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Choose a theme that works best for you.
                    </p>
                </div>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                <AppearanceTabs/>

                <p class="text-sm text-muted-foreground">
                    Your preference is applied automatically and saved for
                    future visits.
                </p>
            </div>
        </section>
    </div>
</template>
