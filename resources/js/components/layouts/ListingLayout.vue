<script lang="ts" setup>
import AppPageHeader from '@/components/common/AppPageHeader.vue';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        showResetPreferences?: boolean;
    }>(),
    {
        description: '',
        showResetPreferences: true,
    },
);

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    resetPreferences: [];
}>();
</script>

<template>
    <main class="w-full min-w-0 space-y-6 overflow-x-hidden p-4 md:p-6">
        <!-- Page Header -->
        <AppPageHeader
            :description="description"
            :show-reset-preferences="showResetPreferences"
            :title="title"
            @reset-preferences="emit('resetPreferences')"
        >
            <template v-if="$slots.actions" #actions>
                <slot name="actions" />
            </template>
        </AppPageHeader>

        <!-- Filters -->
        <section v-if="$slots.filters" aria-label="Filters" class="min-w-0">
            <slot name="filters" />
        </section>

        <!-- Table -->
        <section v-if="$slots.table" aria-label="Data table" class="min-w-0">
            <slot name="table" />
        </section>

        <!-- Pagination -->
        <section
            v-if="$slots.pagination"
            aria-label="Pagination"
            class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <slot name="pagination" />
        </section>
    </main>
</template>
