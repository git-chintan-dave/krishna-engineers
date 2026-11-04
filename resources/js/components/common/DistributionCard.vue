<script lang="ts" setup>
import {computed} from 'vue';

export type DistributionItem = {
    key: string;
    label: string;
    count: number;
    color: string;
    dotColor: string;
    textColor: string;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        total: number;
        totalLabel?: string;
        items: DistributionItem[];
    }>(),
    {
        description: undefined,
        totalLabel: 'total items',
    },
);

const safeTotal = computed(() => Math.max(0, props.total));

const gridColumns = computed(() =>
    props.items.length >= 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2',
);

function percentage(count: number): number {
    if (safeTotal.value <= 0) return 0;

    return (Math.max(0, count) / safeTotal.value) * 100;
}

function displayPercentage(count: number): string {
    return percentage(count).toFixed(0);
}
</script>

<template>
    <section class="rounded-xl border border-border bg-card">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border p-4">
            <div class="min-w-0">
                <h3 class="font-semibold">
                    {{ title }}
                </h3>

                <p
                    v-if="description"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <span class="shrink-0 text-xs text-muted-foreground tabular-nums">
                {{ safeTotal }} {{ totalLabel }}
            </span>
        </div>

        <div class="space-y-5 p-4">
            <div
                class="flex h-3 overflow-hidden rounded-full bg-muted"
                role="img"
                :aria-label="`${title}: ${items.map(item => `${item.label} ${displayPercentage(item.count)}%`).join(', ')}`"
            >
                <div
                    v-for="item in items"
                    :key="item.key"
                    class="h-full transition-all duration-300"
                    :class="item.color"
                    :style="{ width: `${percentage(item.count)}%` }"
                    :title="`${item.label}: ${item.count}`"
                />
            </div>

            <div
                class="grid grid-cols-1 gap-4"
                :class="gridColumns"
            >
                <div
                    v-for="item in items"
                    :key="item.key"
                    class="flex min-w-0 items-start gap-2"
                >
                    <span
                        class="mt-1 size-2.5 shrink-0 rounded-full"
                        :class="item.dotColor"
                        aria-hidden="true"
                    />

                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            {{ item.label }}
                        </p>

                        <div class="mt-1 flex flex-wrap items-baseline gap-x-2">
                            <span class="text-lg font-semibold tabular-nums">
                                {{ item.count }}
                            </span>

                            <span
                                class="text-xs font-medium tabular-nums"
                                :class="item.textColor"
                            >
                                {{ displayPercentage(item.count) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
