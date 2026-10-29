<script lang="ts" setup>
import AppPageHeader from '@/components/common/AppPageHeader.vue';

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
    <div class="w-full min-w-0 space-y-5 overflow-x-hidden p-4 md:px-6 md:py-5">
        <AppPageHeader :description="description" :title="title">
            <template v-if="$slots.actions" #actions>
                <slot name="actions" />
            </template>
        </AppPageHeader>

        <section :aria-label="`${title} form`" class="min-w-0">
            <slot />
        </section>
    </div>
</template>
