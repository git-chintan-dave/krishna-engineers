<script lang="ts" setup>
defineProps<{
    title: string;
    description?: string;
    emptyMessage?: string;
    itemsCount?: number;
}>();
</script>

<template>
    <section class="flex min-h-0 min-w-0 flex-col overflow-hidden rounded-lg border border-border bg-card">
        <header class="flex shrink-0 items-start justify-between gap-3 border-b border-border px-4 py-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <slot name="actions" />
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <slot>
                <p class="p-4 text-sm text-muted-foreground">
                    {{ emptyMessage ?? 'No data available.' }}
                </p>
            </slot>
        </div>
    </section>
</template>
