<script lang="ts" setup>
import type {Component} from 'vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
    }>(),
    {
        description: undefined,
        icon: undefined,
    },
);
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-12 text-center">
        <div
            v-if="icon"
            class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted"
        >
            <component
                :is="icon"
                class="size-6 text-muted-foreground"
                aria-hidden="true"
            />
        </div>

        <h3 class="font-medium">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mt-1 max-w-md text-sm text-muted-foreground"
        >
            {{ description }}
        </p>

        <div
            v-if="$slots.actions"
            class="mt-4 flex flex-wrap items-center justify-center gap-2"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
