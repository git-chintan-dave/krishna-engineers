<script lang="ts" setup>
import type {Component} from 'vue';
import {computed} from 'vue';
import {ArrowDownRight, ArrowUpRight, Minus} from '@lucide/vue';

type StatVariant =
    | 'default'
    | 'primary'
    | 'success'
    | 'info'
    | 'warning'
    | 'destructive';

type TrendDirection = 'up' | 'down' | 'neutral';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        icon?: Component;
        description?: string;
        variant?: StatVariant;
        trend?: {
            value: string;
            direction: TrendDirection;
            label?: string;
        };
        loading?: boolean;
        clickable?: boolean;
        active?: boolean;
        valueLabel?: string;
    }>(),
    {
        icon: undefined,
        description: undefined,
        variant: 'default',
        trend: undefined,
        loading: false,
        clickable: false,
        active: false,
        valueLabel: undefined,
    },
);

const emit = defineEmits<{
    click: [];
}>();

const variantClasses: Record<StatVariant, string> = {
    default: 'bg-muted text-muted-foreground',
    primary: 'bg-primary/10 text-primary',
    success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    info: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    destructive: 'bg-red-500/10 text-red-600 dark:text-red-400',
};

const trendIcon = computed(() => {
    switch (props.trend?.direction) {
        case 'up':
            return ArrowUpRight;
        case 'down':
            return ArrowDownRight;
        default:
            return Minus;
    }
});

const trendClasses = computed(() => {
    switch (props.trend?.direction) {
        case 'up':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'down':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-muted-foreground';
    }
});

function handleClick() {
    if (props.clickable && !props.loading) {
        emit('click');
    }
}
</script>

<template>
    <component
        :is="clickable ? 'button' : 'div'"
        :type="clickable ? 'button' : undefined"
        :aria-pressed="clickable ? active : undefined"
        :aria-label="clickable ? `${label}: ${value}` : valueLabel"
        :aria-busy="loading || undefined"
        :disabled="clickable && loading"
        class="flex min-w-0 items-start justify-between gap-2.5 rounded-lg border border-border bg-card p-3 text-left transition-colors"
        :class="[
            clickable
                ? 'cursor-pointer hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2'
                : '',
            active
                ? 'border-primary/50 bg-primary/5 ring-1 ring-primary/20'
                : '',
        ]"
        @click="handleClick"
    >
        <div class="min-w-0 flex-1">
            <template v-if="loading">
                <div class="h-3.5 w-20 max-w-full animate-pulse rounded bg-muted" />
                <div class="mt-2.5 h-6 w-14 animate-pulse rounded bg-muted" />
                <div class="mt-2 h-3 w-24 max-w-full animate-pulse rounded bg-muted" />
            </template>

            <template v-else>
                <p class="truncate text-xs font-medium text-muted-foreground">
                    {{ label }}
                </p>

                <p
                    class="mt-1 text-xl font-semibold leading-7 tracking-tight tabular-nums"
                    :aria-label="valueLabel"
                >
                    {{ value }}
                </p>

                <div
                    v-if="description || trend"
                    class="mt-1.5 flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1"
                >
                    <p
                        v-if="description"
                        class="text-xs leading-4 text-muted-foreground"
                    >
                        {{ description }}
                    </p>

                    <span
                        v-if="trend"
                        class="inline-flex items-center gap-0.5 text-xs font-medium"
                        :class="trendClasses"
                    >
                        <component
                            :is="trendIcon"
                            class="size-3.5 shrink-0"
                        />
                        {{ trend.value }}

                        <span
                            v-if="trend.label"
                            class="font-normal text-muted-foreground"
                        >
                            {{ trend.label }}
                        </span>
                    </span>
                </div>
            </template>
        </div>

        <div
            v-if="icon"
            class="flex size-8 shrink-0 items-center justify-center rounded-md"
            :class="variantClasses[variant]"
            aria-hidden="true"
        >
            <component :is="icon" class="size-4" />
        </div>
    </component>
</template>
