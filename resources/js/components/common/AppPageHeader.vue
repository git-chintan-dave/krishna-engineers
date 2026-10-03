<script setup lang="ts">
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
        class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <!-- Content -->
        <div class="min-w-0 flex-1">
            <h1
                class="break-words text-2xl leading-tight font-semibold tracking-tight text-foreground"
            >
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="mt-1 max-w-3xl break-words text-sm leading-5 text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <!-- Actions -->
        <div
            v-if="$slots.actions"
            class="flex min-w-0 w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </header>
</template>
