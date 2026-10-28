
<script lang="ts" setup>
import {ArrowLeft, LoaderCircle, Save} from '@lucide/vue';
import {ref} from 'vue';

import {Button} from '@/components/ui/button';
import {ACTION_SHORTCUTS, useActionShortcuts,} from '@/composables/useActionShortcuts';

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
        cancelLabel: 'Back',
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

/*
|--------------------------------------------------------------------------
| Refs
|--------------------------------------------------------------------------
*/

const container = ref<HTMLDivElement | null>(null);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const handleBack = (): void => {
    if (!props.processing) {
        emit('cancel');
    }
};

const handleSave = (): void => {
    if (props.processing || props.disabled) {
        return;
    }

    container.value?.closest('form')?.requestSubmit();
};

/*
|--------------------------------------------------------------------------
| Keyboard Shortcuts
|--------------------------------------------------------------------------
*/

useActionShortcuts(() => ({
    back: {
        handler: handleBack,
        enabled: () => !props.processing,
    },

    save: {
        handler: handleSave,
        enabled: () => !props.processing && !props.disabled,
    },
}));
</script>

<template>
    <div
        ref="container"
        class="flex min-w-0 flex-col-reverse gap-2 sm:flex-row sm:justify-end"
    >
        <!-- Back -->
        <Button
            :disabled="processing"
            class="w-full shrink-0 cursor-pointer gap-1.5 sm:w-auto"
            type="button"
            variant="outline"
            @click="handleBack"
        >
            <ArrowLeft
                aria-hidden="true"
                class="size-4"
            />

            <span>{{ cancelLabel }}</span>

            <kbd
                class="ml-1 hidden font-mono text-[10px] font-normal leading-none text-muted-foreground md:inline"
                aria-hidden="true"
            >
                {{ ACTION_SHORTCUTS.back.label }}
            </kbd>
        </Button>

        <!-- Save -->
        <Button
            :disabled="processing || disabled"
            class="w-full shrink-0 cursor-pointer gap-1.5 sm:w-auto"
            type="submit"
        >
            <LoaderCircle
                v-if="processing"
                aria-hidden="true"
                class="size-4 animate-spin"
            />

            <Save
                v-else
                aria-hidden="true"
                class="size-4"
            />

            <span>{{ submitLabel }}</span>

            <kbd
                class="ml-1 hidden font-mono text-[10px] font-normal leading-none text-primary-foreground/75 md:inline"
                aria-hidden="true"
            >
                {{ ACTION_SHORTCUTS.save.label }}
            </kbd>
        </Button>
    </div>
</template>
