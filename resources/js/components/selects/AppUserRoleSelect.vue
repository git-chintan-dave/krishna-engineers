<script lang="ts" setup>
import {onMounted, ref} from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import {useLookup} from '@/composables/useLookup';
import type {SelectOption} from '@/types/common';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

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
        placeholder: 'Select role...',
        searchable: false,
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
    'update:modelValue': [value: string | null];
    change: [value: string | null];
}>();

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const options = ref<SelectOption<string>[]>([]);

const {loading, enumOptions} = useLookup();

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async () => {
    options.value = await enumOptions<string>('user_role');
};

onMounted(loadOptions);
</script>

<template>
    <AppSelect
        :clearable="clearable"
        :disabled="disabled"
        :error="error"
        :loading="loading"
        :model-value="modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        @change="emit('change', $event as string | null)"
        @update:model-value="emit('update:modelValue', $event as string | null)"
    />
</template>
