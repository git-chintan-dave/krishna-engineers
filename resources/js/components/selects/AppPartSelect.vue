<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type PartSelectOption = SelectOption<number> & {
    cutting_size: string | null;
    section_id: number | null;
};

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select part...',
        searchable: true,
        clearable: false,
        disabled: false,
        error: '',
    },
);

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    'update:modelValue': [
        value: number | null,
    ];

    change: [
        value: number | null,
    ];

    part: [
        part: PartSelectOption | null,
    ];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options =
    ref<PartSelectOption[]>([]);

const {
    loading,
    parts,
} = useLookup();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const getPart = (
    value: number | null,
): PartSelectOption | null => {
    if (value === null) {
        return null;
    }

    return (
        options.value.find(
            (option) =>
                option.value === value,
        ) ?? null
    );
};

const emitSelectedPart = (
    value: number | null,
): void => {
    emit(
        'part',
        getPart(value),
    );
};

/*
|--------------------------------------------------------------------------
| Value Changes
|--------------------------------------------------------------------------
*/

const normalizeValue = (
    value: unknown,
): number | null => {
    if (typeof value === 'number') {
        return value;
    }

    if (
        typeof value === 'string' &&
        value !== ''
    ) {
        const numberValue = Number(value);

        return Number.isNaN(numberValue)
            ? null
            : numberValue;
    }

    return null;
};

const handleModelValue = (
    value: unknown,
): void => {
    const partId =
        normalizeValue(value);

    emit(
        'update:modelValue',
        partId,
    );

    emitSelectedPart(partId);
};

const handleChange = (
    value: unknown,
): void => {
    const partId =
        normalizeValue(value);

    emit(
        'change',
        partId,
    );

    emitSelectedPart(partId);
};

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async (): Promise<void> => {
    options.value =
        await parts() as PartSelectOption[];

    /*
     * Important for:
     * - Edit
     * - Last report autofill
     *
     * modelValue may already contain a Part ID
     * before lookup options finish loading.
     */
    emitSelectedPart(
        props.modelValue,
    );
};

onMounted(loadOptions);

watch(
    () => props.modelValue,
    (value) => {
        if (options.value.length > 0) {
            emitSelectedPart(value);
        }
    },
);

onMounted(loadOptions);

/*
|--------------------------------------------------------------------------
| Programmatic Model Value Changes
|--------------------------------------------------------------------------
*/

watch(
    () => props.modelValue,
    (value) => {
        /*
         * If the lookup has already loaded, immediately
         * resolve the selected Part.
         *
         * This handles last-report autofill.
         */
        if (options.value.length > 0) {
            emitSelectedPart(value);
        }
    },
);
</script>

<template>
    <AppSelect
        :model-value="modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        :clearable="clearable"
        :disabled="disabled"
        :loading="loading"
        :error="error"
        @update:model-value="
            handleModelValue
        "
        @change="
            handleChange
        "
    />
</template>
