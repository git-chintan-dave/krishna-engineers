<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        sectionId?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        sectionId: null,
        placeholder: 'Select section size...',
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
    'update:modelValue': [value: number | null];
    change: [value: number | null];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<number>[]>([]);

const { loading, sectionSizes } = useLookup();

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async () => {
    options.value = [];

    if (!props.sectionId) {
        return;
    }

    options.value = await sectionSizes(
        props.sectionId,
    );
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(loadOptions);

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(
    () => props.sectionId,
    async (sectionId) => {
        emit('update:modelValue', null);

        // Section was cleared.
        if (!sectionId) {
            options.value = [];
            return;
        }

        await loadOptions();
    },
);
</script>

<template>
    <AppSelect
        :model-value="props.modelValue"
        :options="options"
        :placeholder="props.placeholder"
        :searchable="props.searchable"
        :clearable="props.clearable"
        :disabled="props.disabled"
        :loading="loading"
        :error="props.error"
        @update:model-value="
            emit(
                'update:modelValue',
                $event as number | null,
            )
        "
        @change="
            emit(
                'change',
                $event as number | null,
            )
        "
    />
</template>
