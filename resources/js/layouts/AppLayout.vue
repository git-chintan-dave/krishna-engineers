<script lang="ts" setup>
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type {BreadcrumbItem} from '@/types';
import GlobalConfirmDialog from '@/components/GlobalConfirmDialog.vue';

const {breadcrumbs = []} = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot/>
        <GlobalConfirmDialog/>
    </AppLayout>
</template>
