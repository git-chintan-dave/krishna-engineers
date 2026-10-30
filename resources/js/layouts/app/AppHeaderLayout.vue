<script lang="ts" setup>
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell
        variant="header"
        class="[--app-header-height:4rem] [scrollbar-gutter:stable]"
    >
        <AppHeader :breadcrumbs="breadcrumbs" />

        <AppContent variant="header">
            <slot />
        </AppContent>

        <Toaster />
    </AppShell>
</template>
