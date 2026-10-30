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

defineEmits<{
    resetPreferences: [];
}>();
</script>

<template>
    <main class="w-full min-w-0 divide-y divide-border p-4 md:px-6 md:py-5">
        <div
            class="sticky top-(--app-header-height,0px) z-30 -mx-4 -mt-4 border-b border-border bg-background/95 px-4 backdrop-blur-md md:-mx-6 md:-mt-5 md:px-6"
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
            class="min-w-0 py-5"
        >
            <slot name="stats" />
        </section>

        <section
            v-if="$slots.filters"
            aria-label="Filters"
            class="min-w-0 py-5"
        >
            <slot name="filters" />
        </section>

        <section
            v-if="$slots.table"
            aria-label="Data table"
            class="min-w-0 py-5"
        >
            <slot name="table" />
        </section>

        <section
            v-if="$slots.pagination"
            aria-label="Pagination"
            class="flex min-w-0 flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <slot name="pagination" />
        </section>
    </main>

    <ScrollToTop />
</template>
