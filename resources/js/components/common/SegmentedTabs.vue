<script lang="ts" setup generic="T extends string">
import type {Component} from 'vue';

export type SegmentedTabItem<T extends string = string> = {
    value: T;
    label: string;
    icon?: Component;
    disabled?: boolean;
};

type SegmentedTabsVariant = 'default' | 'primary' | 'outline' | 'ghost';
type SegmentedTabsSize = 'sm' | 'md' | 'lg';

withDefaults(
    defineProps<{
        items: readonly SegmentedTabItem<T>[];
        ariaLabel?: string;
        variant?: SegmentedTabsVariant;
        size?: SegmentedTabsSize;
        fullWidth?: boolean;
        disabled?: boolean;
    }>(),
    {
        ariaLabel: 'View selection',
        variant: 'default',
        size: 'md',
        fullWidth: false,
        disabled: false,
    },
);

const model = defineModel<T>({
    required: true,
});

const containerClasses: Record<SegmentedTabsVariant, string> = {
    default: 'rounded-xl border border-border bg-muted/40 p-1',
    primary: 'rounded-xl border border-border bg-muted/40 p-1',
    outline: 'rounded-xl border border-border bg-background p-1',
    ghost: 'rounded-xl bg-transparent p-0',
};

const sizeClasses: Record<SegmentedTabsSize, string> = {
    sm: 'min-h-8 px-2.5 py-1.5 text-xs gap-1.5',
    md: 'min-h-9 px-3 py-2 text-xs gap-2',
    lg: 'min-h-10 px-4 py-2.5 text-sm gap-2',
};

const selectedClasses: Record<SegmentedTabsVariant, string> = {
    default: 'bg-card text-foreground shadow-sm ring-1 ring-border',
    primary: 'bg-primary text-primary-foreground shadow-sm',
    outline: 'border border-primary bg-primary/5 text-primary shadow-sm',
    ghost: 'bg-muted text-foreground',
};

const unselectedClasses: Record<SegmentedTabsVariant, string> = {
    default: 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
    primary: 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
    outline: 'border border-transparent text-muted-foreground hover:bg-muted hover:text-foreground',
    ghost: 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
};
</script>

<template>
    <div
        class="inline-flex max-w-full items-center gap-1 overflow-x-auto"
        :class="[
            containerClasses[variant],
            fullWidth ? 'flex w-full' : 'w-fit',
            disabled ? 'pointer-events-none opacity-50' : '',
        ]"
        role="group"
        :aria-label="ariaLabel"
        :aria-disabled="disabled || undefined"
    >
        <button
            v-for="item in items"
            :key="item.value"
            type="button"
            :disabled="disabled || item.disabled"
            :aria-pressed="model === item.value"
            class="inline-flex shrink-0 items-center justify-center rounded-lg font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50"
            :class="[
                sizeClasses[size],
                fullWidth ? 'flex-1' : '',
                model === item.value
                    ? selectedClasses[variant]
                    : unselectedClasses[variant],
            ]"
            @click="model = item.value"
        >
            <component
                v-if="item.icon"
                :is="item.icon"
                class="size-3.5 shrink-0"
                aria-hidden="true"
            />

            {{ item.label }}
        </button>
    </div>
</template>
