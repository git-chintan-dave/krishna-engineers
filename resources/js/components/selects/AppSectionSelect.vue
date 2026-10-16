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
        modelValue?: number | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select section...',
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

const {loading, sections} = useLookup();

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async () => {
    options.value = await sections();
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
        @change="emit('change', $event as number | null)"
        @update:model-value="emit('update:modelValue', $event as number | null)"
    />
</template>
