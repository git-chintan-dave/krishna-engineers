<script lang="ts" setup>
/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: '',
        description: '',
    },
);
</script>

<template>
    <section
        class="w-full min-w-0 overflow-hidden rounded-lg border bg-background"
    >
        <!--
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        -->

        <div v-if="title || description" class="border-b px-4 py-4 md:px-6">
            <h2 v-if="title" class="text-sm font-semibold text-foreground">
                {{ title }}
            </h2>

            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        -->

        <div class="w-full min-w-0 p-4 md:p-6">
            <slot/>
        </div>
    </section>
</template>
