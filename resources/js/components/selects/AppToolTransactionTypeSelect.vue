<script setup lang="ts">
import { onMounted, ref } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select transaction type...',
        searchable: false,
        clearable: false,
        disabled: false,
        error: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    change: [value: string | null];
}>();

const options = ref<SelectOption<string>[]>([]);

const {
    loading,
    enumOptions,
} = useLookup();

const loadOptions = async () => {
    options.value =
        await enumOptions<string>(
            'tool_transaction_type',
        );
};

onMounted(loadOptions);
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
                $event as string | null,
            )
        "
        @change="
            emit(
                'change',
                $event as string | null,
            )
        "
    />
</template>
