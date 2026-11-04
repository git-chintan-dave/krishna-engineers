<script lang="ts" setup>
import AppPageHeader from '@/components/common/AppPageHeader.vue';
import ScrollToTop from '@/components/common/ScrollToTop.vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
    }>(),
    {
        description: '',
    },
);
</script>

<template>
    <main class="w-full min-w-0 space-y-5 overflow-x-hidden p-4 pt-0 md:px-6 md:pb-5">
        <AppPageHeader
            :description="description"
            :title="title"
        >
            <template v-if="$slots.actions" #actions>
                <slot name="actions" />
            </template>
        </AppPageHeader>

        <section
            aria-label="Form"
            class="w-full min-w-0"
        >
            <slot />
        </section>
    </main>

    <ScrollToTop />
</template>
