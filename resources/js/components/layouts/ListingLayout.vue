<script lang="ts" setup>
import {nextTick, onBeforeUnmount, onMounted, ref,} from 'vue';

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

defineEmits<{
    resetPreferences: [];
}>();

const listingContentRef = ref<HTMLElement | null>(null);
const pageHeaderRef = ref<HTMLElement | null>(null);

let pageHeaderResizeObserver: ResizeObserver | undefined;

const updatePageHeaderHeight = (): void => {
    const element = pageHeaderRef.value;
    const container = listingContentRef.value;

    if (!element || !container) {
        return;
    }

    container.style.setProperty(
        '--app-page-header-height',
        `${element.getBoundingClientRect().height}px`,
    );
};

onMounted(async () => {
    await nextTick();

    const element = pageHeaderRef.value;

    if (!element) {
        return;
    }

    updatePageHeaderHeight();

    if (typeof ResizeObserver !== 'undefined') {
        pageHeaderResizeObserver = new ResizeObserver(
            updatePageHeaderHeight,
        );

        pageHeaderResizeObserver.observe(element);
    }

    window.addEventListener(
        'resize',
        updatePageHeaderHeight,
    );
});

onBeforeUnmount(() => {
    pageHeaderResizeObserver?.disconnect();

    window.removeEventListener(
        'resize',
        updatePageHeaderHeight,
    );
});
</script>

<template>
    <main
        ref="listingContentRef"
        class="w-full min-w-0 max-w-full divide-y divide-border p-4 md:px-6 md:pb-5 pt-0"
    >
        <div
            ref="pageHeaderRef"
            class="sticky top-(--app-header-height,0px) z-30 flex min-w-0 border-b border-border bg-background/95 backdrop-blur-md"
        >
            <AppPageHeader
                :description="description"
                :title="title"
            >
                <template v-if="$slots.actions" #actions>
                    <slot name="actions" />
                </template>
            </AppPageHeader>
        </div>

        <section
            v-if="$slots.stats"
            aria-label="Statistics"
            class="w-full min-w-0 py-5"
        >
            <slot name="stats" />
        </section>

        <section
            v-if="$slots.filters"
            aria-label="Filters"
            class="w-full min-w-0 py-5"
        >
            <slot name="filters" />
        </section>

        <section
            v-if="$slots.table"
            aria-label="Data table"
            class="w-full min-w-0 py-5"
        >
            <slot name="table" />
        </section>

        <section
            v-if="$slots.pagination"
            aria-label="Pagination"
            class="flex w-full min-w-0 flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <slot name="pagination" />
        </section>
    </main>

    <ScrollToTop />
</template>
