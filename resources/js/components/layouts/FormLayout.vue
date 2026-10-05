<script setup lang="ts">
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
    }>(),
    {
        description: '',
    },
);
</script>

<template>
    <main class="w-full min-w-0 space-y-6 overflow-x-hidden p-4 md:p-6">
        <!--
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        -->

        <AppPageHeader
            :title="title"
            :description="description"
        >
            <template
                v-if="$slots.actions"
                #actions
            >
                <slot name="actions" />
            </template>
        </AppPageHeader>

        <!--
        |--------------------------------------------------------------------------
        | Form Content
        |--------------------------------------------------------------------------
        -->

        <section
            aria-label="Form"
            class="min-w-0"
        >
            <slot />
        </section>
    </main>
</template>
