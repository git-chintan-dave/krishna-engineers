<script lang="ts" setup>
import {onBeforeUnmount, onMounted, ref} from 'vue';

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
    const header = pageHeaderRef.value;
    const container = listingContentRef.value;

    if (!header || !container) return;

    container.style.setProperty(
        '--app-page-header-height',
        `${header.getBoundingClientRect().height}px`,
    );
};

onMounted(() => {
    updatePageHeaderHeight();

    const header = pageHeaderRef.value;

    if (!header) return;

    if (typeof ResizeObserver !== 'undefined') {
        pageHeaderResizeObserver = new ResizeObserver(
            updatePageHeaderHeight,
        );

        pageHeaderResizeObserver.observe(header);
    }

    window.addEventListener('resize', updatePageHeaderHeight);
});

onBeforeUnmount(() => {
    pageHeaderResizeObserver?.disconnect();

    window.removeEventListener('resize', updatePageHeaderHeight);
});
</script>

<template>
    <main
        ref="listingContentRef"
        class="w-full min-w-0 max-w-full divide-y divide-border p-4 pt-0 md:px-6 md:pb-5"
    >
        <!-- Sticky page header -->
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

        <!-- Statistics -->
        <section
            v-if="$slots.stats"
            aria-label="Statistics"
            class="w-full min-w-0 py-5"
        >
            <slot name="stats" />
        </section>

        <!-- Filters -->
        <section
            v-if="$slots.filters"
            aria-label="Filters"
            class="w-full min-w-0 py-5"
        >
            <slot name="filters" />
        </section>

        <!-- Table -->
        <section
            v-if="$slots.table"
            aria-label="Data table"
            class="w-full min-w-0 py-5"
        >
            <slot name="table" />
        </section>

        <!-- Pagination -->
        <section
            v-if="$slots.pagination"
            aria-label="Pagination"
            class="flex w-full min-w-0 flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <slot name="pagination" />
        </section>

        <!-- Additional content -->
        <slot />
    </main>

    <ScrollToTop />
</template>
