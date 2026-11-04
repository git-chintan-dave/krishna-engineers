<script lang="ts" setup>
import {computed} from "vue";

export type ProgressSummaryItem = {
    key: string;
    label: string;
    value: string | number;
    color: string;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        percentage: number;
        items?: ProgressSummaryItem[];
        percentageColor?: string;
        barColor?: string;
        suffix?: string;
        decimals?: number;
    }>(),
    {
        description: undefined,
        items: () => [],
        percentageColor: 'text-primary',
        barColor: 'bg-primary',
        suffix: '%',
        decimals: 1,
    },
);

const progressWidth = computed(() =>
    Math.min(100, Math.max(0, props.percentage)),
);
</script>

<template>
    <section class="rounded-xl border border-border bg-card p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h3 class="font-semibold">
                    {{ title }}
                </h3>

                <p
                    v-if="description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <p
                class="shrink-0 text-xl font-bold tabular-nums"
                :class="percentageColor"
            >
                {{ percentage.toFixed(decimals) }}{{ suffix }}
            </p>
        </div>

        <div
            class="mt-5 h-3 overflow-hidden rounded-full bg-muted"
            role="progressbar"
            :aria-label="title"
            :aria-valuenow="progressWidth"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="h-full rounded-full transition-all duration-500"
                :class="barColor"
                :style="{ width: `${progressWidth}%` }"
            />
        </div>

        <div
            v-if="items.length"
            class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2"
        >
            <div
                v-for="item in items"
                :key="item.key"
                class="flex items-center gap-1.5 text-xs text-muted-foreground"
            >
                <span
                    class="size-2 shrink-0 rounded-full"
                    :class="item.color"
                    aria-hidden="true"
                />

                <span>{{ item.label }}:</span>

                <span class="font-medium tabular-nums text-foreground">
                    {{ item.value }}
                </span>
            </div>
        </div>
    </section>
</template>
