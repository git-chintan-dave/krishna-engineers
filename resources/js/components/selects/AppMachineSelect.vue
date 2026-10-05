<script setup lang="ts">
import { onMounted, ref } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
import { useLookup } from '@/composables/useLookup';
import type { SelectOption } from '@/types/common';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type EmployeeRole =
    | 'super_admin'
    | 'supervisor'
    | 'operator'
    | 'bandsaw'
    | 'deburring_packaging';

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
        role?: EmployeeRole | null;
    }>(),
    {
        modelValue: null,
        placeholder: 'Select employee...',
        searchable: true,
        clearable: false,
        disabled: false,
        error: '',
        role: null,
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

const {
    loading,
    employees,
} = useLookup();

/*
|--------------------------------------------------------------------------
| Load Options
|--------------------------------------------------------------------------
*/

const loadOptions = async () => {
    options.value = await employees(
        props.role ?? undefined,
    );
};

onMounted(loadOptions);
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
