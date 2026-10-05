<script setup lang="ts">
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
            type="button"
            class="group relative block overflow-hidden rounded-md border bg-muted/20 transition hover:border-primary/40 hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            :class="
                size === 'sm'
                    ? 'size-12'
                    : 'size-16'
            "
            :aria-label="`View ${alt}`"
            :title="`View ${alt}`"
            @click="openViewer"
        >
            <img
                :src="src"
                :alt="alt"
                class="h-full w-full object-contain p-1 transition duration-200 group-hover:scale-105"
                loading="lazy"
                decoding="async"
            />

            <span
                class="pointer-events-none absolute inset-0 bg-black/0 transition group-hover:bg-black/10"
                aria-hidden="true"
            />
        </button>

        <AppImageViewer
            v-model:open="viewerOpen"
            :src="src"
            :alt="alt"
        />
    </template>

    <span
        v-else
        class="inline-flex size-12 items-center justify-center rounded-md border border-dashed bg-muted/20 text-xs text-muted-foreground"
        aria-label="No image"
    >
        —
    </span>
</template>
