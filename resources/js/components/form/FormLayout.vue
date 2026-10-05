<script setup lang="ts">
import AppPageHeader from '@/components/common/AppPageHeader.vue';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
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
    <div class="w-full min-w-0 space-y-6 overflow-x-hidden p-4 md:p-6">
        <!--
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        -->

        <AppPageHeader
            :title="title"
            :description="description"
        />

        <!--
        |--------------------------------------------------------------------------
        | Form Content
        |--------------------------------------------------------------------------
        -->

        <section
            :aria-label="`${title} form`"
            class="min-w-0"
        >
            <slot />
        </section>
    </div>
</template>
