<script lang="ts" setup>
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
    <header
        class="flex w-full min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <!--
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        -->

        <div class="min-w-0 flex-1">
            <h1
                class="text-2xl leading-tight font-semibold tracking-tight wrap-break-word text-foreground"
            >
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="mt-1 max-w-3xl text-sm leading-5 wrap-break-word text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <!--
        |--------------------------------------------------------------------------
        | Actions
        |--------------------------------------------------------------------------
        -->

        <div
            v-if="$slots.actions"
            class="flex w-full shrink-0 items-center gap-2 sm:w-auto sm:max-w-[50%] sm:flex-wrap sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>
