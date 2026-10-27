<script lang="ts" setup>
import {computed} from 'vue';
import type {TrendPoint} from '@/types/dashboard';

const props = defineProps<{
    title: string;
    description?: string;
    points: TrendPoint[];
}>();

const maxQuantity = computed(() => Math.max(
    1,
    ...props.points.map((point) => point.totalQuantity),
));

function width(value: number): string {
    return `${Math.min(100, Math.max(0, (value / maxQuantity.value) * 100))}%`;
}

function formatNumber(value: number): string {
    return new Intl.NumberFormat('en-IN').format(value);
}
</script>

<template>
    <section class="min-w-0 overflow-hidden rounded-lg border border-border bg-card">
        <header class="border-b border-border px-4 py-3">
            <h2 class="text-sm font-semibold">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-xs text-muted-foreground">
                {{ description }}
            </p>
        </header>

        <div v-if="points.length" class="space-y-4 p-4">
            <div v-for="point in points" :key="point.label" class="space-y-2">
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="truncate text-muted-foreground">{{ point.label }}</span>
                    <span class="shrink-0 font-medium tabular-nums">
                        {{ formatNumber(point.totalQuantity) }}
                    </span>
                </div>

                <div
                    class="flex h-2.5 overflow-hidden rounded-sm bg-muted"
                    :aria-label="`${point.label}: ${point.goodQuantity} good, ${point.rejectedQuantity} rejected`"
                >
                    <div
                        class="h-full bg-foreground"
                        :style="{ width: width(point.goodQuantity) }"
                        :title="`Good: ${point.goodQuantity}`"
                    />
                    <div
                        class="h-full border border-dashed border-foreground/70 bg-background"
                        :style="{ width: width(point.rejectedQuantity) }"
                        :title="`Rejected: ${point.rejectedQuantity}`"
                    />
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span>Good: {{ formatNumber(point.goodQuantity) }}</span>
                    <span>Rejected: {{ formatNumber(point.rejectedQuantity) }}</span>
                </div>
            </div>
        </div>

        <p v-else class="p-4 text-sm text-muted-foreground">
            No production trend data available for this period.
        </p>

        <footer class="flex flex-wrap gap-x-4 gap-y-2 border-t border-border px-4 py-3 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-2">
                <span class="size-2 rounded-sm bg-foreground" />
                Good pieces
            </span>
            <span class="inline-flex items-center gap-2">
                <span class="size-2 border border-dashed border-foreground" />
                Rejected pieces
            </span>
        </footer>
    </section>
</template>
