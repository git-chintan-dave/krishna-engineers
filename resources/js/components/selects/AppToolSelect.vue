<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        type?: string | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        type: null,
        placeholder: 'Select tool...',
        searchable: true,
        clearable: false,
        disabled: false,
        error: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    change: [value: number | null];
}>();

const options = ref<SelectOption<number>[]>([]);

const {
    loading,
    tools,
} = useLookup();

const loadOptions = async () => {
    options.value = [];

    options.value = await tools(
        props.type,
    );
};

onMounted(loadOptions);

watch(
    () => props.type,
    async () => {
        emit('update:modelValue', null);

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
