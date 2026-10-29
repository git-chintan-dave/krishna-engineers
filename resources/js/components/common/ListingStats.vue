<script lang="ts" setup>
import type {Component} from 'vue';
import {computed} from 'vue';

import ListingStat from '@/components/common/ListingStat.vue';

export type ListingStatVariant =
    | 'default'
    | 'primary'
    | 'success'
    | 'info'
    | 'warning'
    | 'destructive';

export type ListingStatItem = {
    label: string;
    value: string | number;
    icon?: Component;
    description?: string;
    variant?: ListingStatVariant;
    trend?: {
        value: string;
        direction: 'up' | 'down' | 'neutral';
        label?: string;
    };
    clickable?: boolean;
    active?: boolean;
    valueLabel?: string;
};

const props = withDefaults(
    defineProps<{
        items?: ListingStatItem[];
        columns?: 1 | 2 | 3 | 4 | 5 | 6;
        loading?: boolean;
        skeletonCount?: number;
    }>(),
    {
        items: () => [],
        columns: 4,
        loading: false,
        skeletonCount: 4,
    },
);

const emit = defineEmits<{
    statClick: [item: ListingStatItem, index: number];
}>();

const columnClasses: Record<NonNullable<typeof props.columns>, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-2',
    3: 'grid-cols-2 sm:grid-cols-3',
    4: 'grid-cols-2 sm:grid-cols-3 xl:grid-cols-4',
    5: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
    6: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
};

const skeletonItems = computed(() =>
    Array.from(
        { length: Math.max(0, props.skeletonCount) },
        (_, index) => index,
    ),
);
</script>

<template>
    <div
        class="grid min-w-0 gap-3"
        :class="columnClasses[columns]"
        role="group"
        aria-label="Listing statistics"
        :aria-busy="loading || undefined"
    >
        <template v-if="loading">
            <ListingStat
                v-for="index in skeletonItems"
                :key="`skeleton-${index}`"
                :label="`Statistic ${index + 1}`"
                :value="0"
                :loading="true"
            />
        </template>

        <template v-else>
            <ListingStat
                v-for="(item, index) in items"
                :key="`${item.label}-${index}`"
                v-bind="item"
                @click="emit('statClick', item, index)"
            />
        </template>
    </div>
</template>
