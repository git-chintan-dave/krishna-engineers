<script lang="ts" setup>
import {Check, Monitor, Moon, Sun} from '@lucide/vue';
import {useAppearance} from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            :class="[
                'group relative rounded-xl border p-3 text-left transition-all duration-200',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                appearance === value
                    ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                    : 'border-border hover:border-primary/40 hover:bg-muted/40',
            ]"
            @click="updateAppearance(value)"
        >
            <div
                class="mb-3 overflow-hidden rounded-lg border border-border"
                :class="
                    value === 'dark'
                        ? 'bg-zinc-950'
                        : value === 'light'
                          ? 'bg-white'
                          : 'bg-gradient-to-r from-white from-50% to-zinc-950 to-50%'
                "
                aria-hidden="true"
            >
                <div
                    class="flex h-7 items-center gap-1.5 border-b px-2"
                    :class="
                        value === 'dark'
                            ? 'border-zinc-800 bg-zinc-900'
                            : value === 'light'
                              ? 'border-zinc-200 bg-zinc-50'
                              : 'border-zinc-300/60 bg-white/90'
                    "
                >
                    <span class="size-1.5 rounded-full bg-red-400" />
                    <span class="size-1.5 rounded-full bg-amber-400" />
                    <span class="size-1.5 rounded-full bg-green-400" />
                </div>

                <div class="flex h-20 gap-2 p-2">
                    <div
                        class="w-1/4 rounded-sm"
                        :class="
                            value === 'dark'
                                ? 'bg-zinc-800'
                                : value === 'light'
                                  ? 'bg-zinc-100'
                                  : 'bg-zinc-200'
                        "
                    />

                    <div
                        class="flex-1 space-y-2 rounded-sm p-2"
                        :class="
                            value === 'dark'
                                ? 'bg-zinc-900'
                                : value === 'light'
                                  ? 'bg-zinc-50'
                                  : 'bg-white/90'
                        "
                    >
                        <div
                            class="h-2 w-2/3 rounded-full"
                            :class="
                                value === 'dark'
                                    ? 'bg-zinc-700'
                                    : 'bg-zinc-300'
                            "
                        />
                        <div class="flex gap-1.5">
                            <div
                                class="h-8 flex-1 rounded-sm"
                                :class="
                                    value === 'dark'
                                        ? 'bg-zinc-800'
                                        : 'bg-zinc-200'
                                "
                            />
                            <div
                                class="h-8 flex-1 rounded-sm"
                                :class="
                                    value === 'dark'
                                        ? 'bg-zinc-800'
                                        : 'bg-zinc-200'
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Icon
                        class="size-4"
                        :class="
                            appearance === value
                                ? 'text-primary'
                                : 'text-muted-foreground'
                        "
                    />
                    <span class="text-sm font-medium">{{ label }}</span>
                </div>

                <span
                    v-if="appearance === value"
                    class="flex size-5 items-center justify-center rounded-full bg-primary text-primary-foreground"
                >
                    <Check class="size-3" />
                </span>
            </div>
        </button>
    </div>
</template>
