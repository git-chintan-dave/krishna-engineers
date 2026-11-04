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
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <div
                v-if="icon"
                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
                <component :is="icon" class="size-5" />
            </div>

            <div class="min-w-0">
                <h2 class="text-lg font-semibold tracking-tight">
                    {{ title }}
                </h2>

                <p
                    v-if="description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </div>

        <div
            v-if="$slots.actions"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
