<script setup lang="ts">
import { ref } from 'vue';

import AppImageViewer from '@/components/common/AppImageViewer.vue';

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

const viewerOpen = ref(false);

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
            class="cursor-pointer group relative block overflow-hidden rounded-md border bg-muted/20 transition hover:border-primary/50 hover:shadow-sm"
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
            />

            <span
                class="absolute inset-0 flex items-center justify-center bg-black/0 transition group-hover:bg-black/10"
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
    >
        —
    </span>
</template>
