<script setup lang="ts">
import { computed, useId } from 'vue';

import { Switch } from '@/components/ui/switch';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        title?: string;
        label: string;
        description?: string;
        activeText?: string;
        inactiveText?: string;
        error?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: false,
        title: 'Configuration',
        description:
            'Manage this setting and control how it behaves across the platform.',
        activeText: 'Currently enabled.',
        inactiveText: 'Currently disabled.',
        error: '',
        disabled: false,
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

/*
|--------------------------------------------------------------------------
| IDs
|--------------------------------------------------------------------------
*/

const id = useId();
const descriptionId = `${id}-description`;
const errorId = `${id}-error`;

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const stateText = computed(() =>
    props.modelValue ? props.activeText : props.inactiveText,
);

const describedBy = computed(() => {
    const ids: string[] = [];

    if (props.description) {
        ids.push(descriptionId);
    }

    if (props.error) {
        ids.push(errorId);
    }

    return ids.length ? ids.join(' ') : undefined;
});

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const handleUpdate = (value: boolean | 'indeterminate') => {
    if (value === 'indeterminate') {
        return;
    }

    emit('update:modelValue', value);
};
</script>

<template>
    <section
        class="w-full min-w-0 rounded-md border border-input bg-background p-4"
        :aria-disabled="disabled"
    >
        <div class="min-w-0 space-y-4">
            <!--
            |--------------------------------------------------------------------------
            | Header
            |--------------------------------------------------------------------------
            -->

            <div v-if="title || description" class="min-w-0 space-y-1">
                <h3
                    v-if="title"
                    class="text-sm leading-5 font-medium text-foreground"
                >
                    {{ title }}
                </h3>

                <p
                    v-if="description"
                    :id="descriptionId"
                    class="text-sm leading-5 text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <!--
            |--------------------------------------------------------------------------
            | Toggle
            |--------------------------------------------------------------------------
            -->

            <div class="flex min-w-0 items-center justify-between gap-4">
                <div class="min-w-0 flex-1 space-y-1">
                    <p
                        class="text-sm leading-5 font-medium break-words text-foreground"
                    >
                        {{ label }}
                    </p>

                    <p
                        class="text-xs leading-4 break-words text-muted-foreground"
                    >
                        {{ stateText }}
                    </p>
                </div>

                <div class="flex size-10 shrink-0 items-center justify-center">
                    <Switch
                        :id="id"
                        :model-value="modelValue"
                        :disabled="disabled"
                        :aria-label="label"
                        :aria-describedby="describedBy"
                        :aria-invalid="error ? true : undefined"
                        class="cursor-pointer"
                        @update:model-value="handleUpdate"
                    />
                </div>
            </div>

            <!--
            |--------------------------------------------------------------------------
            | Error
            |--------------------------------------------------------------------------
            -->

            <p
                v-if="error"
                :id="errorId"
                role="alert"
                class="text-xs leading-4 text-destructive"
            >
                {{ error }}
            </p>
        </div>
    </section>
</template>
