<script lang="ts" setup>
export type StatusVariant =
    | 'default'
    | 'secondary'
    | 'success'
    | 'warning'
    | 'destructive'
    | 'info';

withDefaults(
    defineProps<{
        variant?: StatusVariant;
        dot?: boolean;
    }>(),
    {
        variant: 'default',
        dot: false,
    },
);

const variantClasses: Record<StatusVariant, string> = {
    default: 'bg-primary/10 text-primary',
    secondary: 'bg-muted text-muted-foreground',
    success: 'bg-green-500/10 text-green-700 dark:text-green-400',
    warning: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
    destructive: 'bg-red-500/10 text-red-700 dark:text-red-400',
    info: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
};

const dotClasses: Record<StatusVariant, string> = {
    default: 'bg-primary',
    secondary: 'bg-muted-foreground',
    success: 'bg-green-500',
    warning: 'bg-amber-500',
    destructive: 'bg-red-500',
    info: 'bg-blue-500',
};
</script>

<template>
    <span
        class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
        :class="variantClasses[variant]"
    >
        <span
            v-if="dot"
            class="size-1.5 shrink-0 rounded-full"
            :class="dotClasses[variant]"
            aria-hidden="true"
        />

        <slot />
    </span>
</template>
