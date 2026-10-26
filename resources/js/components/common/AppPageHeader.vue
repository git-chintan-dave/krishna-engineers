<script lang="ts" setup>
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
        class="flex w-full min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
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

        <div
            v-if="$slots.actions"
            class="flex w-full min-w-0 flex-wrap items-center gap-2 sm:w-auto sm:max-w-full sm:shrink-0 sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>
