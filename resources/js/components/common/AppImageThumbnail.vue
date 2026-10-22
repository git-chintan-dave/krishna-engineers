<script lang="ts" setup>
import { ref } from 'vue';

import AppImageViewer from '@/components/common/AppImageViewer.vue';

// Props
const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt?: string;
        size?: 'sm' | 'md';
    }>(),
    {
        src: null,
        alt: 'Image',
        size: 'sm',
    },
);

// State
const viewerOpen = ref(false);

// Actions
const openViewer = () => {
    if (!props.src) {
        return;
    }

    viewerOpen.value = true;
};
</script>

<template>
    <template v-if="src">
        <button
            :aria-label="`View ${alt}`"
            :class="size === 'sm' ? 'size-12' : 'size-16'"
            :title="`View ${alt}`"
            class="group relative block overflow-hidden rounded-md border bg-muted/20 transition hover:border-primary/40 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            type="button"
            @click="openViewer"
        >
            <img
                :alt="alt"
                :src="src"
                class="h-full w-full object-contain p-1 transition duration-200 group-hover:scale-105"
                decoding="async"
                loading="lazy"
            />

            <span
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 bg-black/0 transition group-hover:bg-black/10"
            />
        </button>

        <AppImageViewer v-model:open="viewerOpen" :alt="alt" :src="src" />
    </template>

    <span
        v-else
        aria-label="No image"
        class="inline-flex size-12 items-center justify-center rounded-md border border-dashed bg-muted/20 text-xs text-muted-foreground"
    >
        —
    </span>
</template>
