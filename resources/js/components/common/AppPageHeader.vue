<script lang="ts" setup>
import {RotateCcw} from '@lucide/vue';
import {Button} from "@/components/ui/button";

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        showResetPreferences?: boolean;
    }>(),
    {
        description: '',
        showResetPreferences: true,
    },
);

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    resetPreferences: [];
}>();
</script>

<template>
    <header
        class="flex w-full min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <!-- Content -->

        <div class="min-w-0 flex-1">
            <h1
                class="text-2xl leading-tight font-semibold tracking-tight wrap-break-word text-foreground"
            >
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="mt-1 max-w-3xl text-sm leading-5 wrap-break-word text-muted-foreground"
            >
                {{ description }}
            </p>
        </div>

        <!-- Actions -->

        <div
            class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto sm:max-w-[50%] sm:justify-end"
        >
            <slot name="actions" />

            <Button
                v-if="showResetPreferences"
                type="button"
                variant="outline"
                size="sm"
                @click="emit('resetPreferences')"
            >
                <RotateCcw class="mr-2 size-4" />
                Reset preferences
            </Button>
        </div>
    </header>
</template>
