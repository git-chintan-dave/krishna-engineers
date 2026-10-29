<script lang="ts" setup>
import AppSelect from '@/components/common/AppSelect.vue';
import type {SelectOption} from '@/types/common';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

export type StockFilter = 'low_stock' | 'out_of_stock';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        modelValue?: StockFilter | null;
        placeholder?: string;
        searchable?: boolean;
        clearable?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select stock level...',
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
    'update:modelValue': [value: StockFilter | null];
    change: [value: StockFilter | null];
}>();

/*
|--------------------------------------------------------------------------
| Options
|--------------------------------------------------------------------------
*/

const options: SelectOption<StockFilter>[] = [
    {
        label: 'Low Stock',
        value: 'low_stock',
    },
    {
        label: 'Out of Stock',
        value: 'out_of_stock',
    },
];
</script>

<template>
    <AppSelect
        :clearable="clearable"
        :disabled="disabled"
        :error="error"
        :loading="false"
        :model-value="props.modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        @change="emit('change', $event as StockFilter | null)"
        @update:model-value="emit('update:modelValue', $event as StockFilter | null)"
    />
</template>
