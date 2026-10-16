<script setup lang="ts">
import { ArrowLeft, LoaderCircle, Save } from '@lucide/vue';

import { Button } from '@/components/ui/button';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        submitLabel?: string;
        cancelLabel?: string;
        processing?: boolean;
        disabled?: boolean;
    }>(),
    {
        submitLabel: 'Save',
        cancelLabel: 'Cancel',
        processing: false,
        disabled: false,
    },
);

/*
|--------------------------------------------------------------------------
| Events
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    cancel: [];
}>();
</script>

<template>
    <div class="flex min-w-0 flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <!--
        |--------------------------------------------------------------------------
        | Cancel
        |--------------------------------------------------------------------------
        -->

        <Button
            type="button"
            variant="outline"
            class="w-full shrink-0 cursor-pointer sm:w-auto"
            :disabled="processing"
            @click="emit('cancel')"
        >
            <ArrowLeft class="size-4" aria-hidden="true" />

            {{ cancelLabel }}
        </Button>

        <!--
        |--------------------------------------------------------------------------
        | Submit
        |--------------------------------------------------------------------------
        -->

        <Button
            type="submit"
            class="w-full shrink-0 cursor-pointer sm:w-auto"
            :disabled="processing || disabled"
        >
            <LoaderCircle
                v-if="processing"
                class="size-4 animate-spin"
                aria-hidden="true"
            />

            <Save v-else class="size-4" aria-hidden="true" />

            {{ submitLabel }}
        </Button>
    </div>
</template>
