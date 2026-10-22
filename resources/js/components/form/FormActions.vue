<script lang="ts" setup>
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
            :disabled="processing"
            class="w-full shrink-0 cursor-pointer sm:w-auto"
            type="button"
            variant="outline"
            @click="emit('cancel')"
        >
            <ArrowLeft aria-hidden="true" class="size-4" />

            {{ cancelLabel }}
        </Button>

        <!--
        |--------------------------------------------------------------------------
        | Submit
        |--------------------------------------------------------------------------
        -->

        <Button
            :disabled="processing || disabled"
            class="w-full shrink-0 cursor-pointer sm:w-auto"
            type="submit"
        >
            <LoaderCircle
                v-if="processing"
                aria-hidden="true"
                class="size-4 animate-spin"
            />

            <Save v-else aria-hidden="true" class="size-4" />

            {{ submitLabel }}
        </Button>
    </div>
</template>
