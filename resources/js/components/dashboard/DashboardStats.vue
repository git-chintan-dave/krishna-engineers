<script lang="ts" setup>
import type {DashboardStat} from '@/types/dashboard';

defineProps<{
    stats: DashboardStat[];
}>();
</script>

<template>
    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="stat in stats"
            :key="stat.label"
            class="min-w-0 rounded-lg border border-border bg-card p-4"
        >
            <p class="text-sm text-muted-foreground">{{ stat.label }}</p>

            <p class="mt-2 break-words text-2xl font-semibold tracking-tight tabular-nums">
                {{ stat.value }}
            </p>

            <p class="mt-1 text-xs leading-5 text-muted-foreground">
                {{ stat.description }}
            </p>
        </article>
    </section>
</template>
