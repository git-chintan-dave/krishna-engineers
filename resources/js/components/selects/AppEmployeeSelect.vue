<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue';

import AppSelect from '@/components/common/AppSelect.vue';
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

type AppSelectValue = string | number | null | (string | number)[];

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
        placeholder: 'Select employee',
        searchable: false,
        clearable: false,
        disabled: false,
        error: undefined,
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
const loading = ref(false);

/*
|--------------------------------------------------------------------------
| Fetch Employees
|--------------------------------------------------------------------------
*/

const fetchEmployees = async () => {
    loading.value = true;

    try {
        const params = new URLSearchParams();

        if (props.role) {
            params.set('role', props.role);
        }

        const query = params.toString();

        const response = await fetch(
            `/lookups/employees${query ? `?${query}` : ''}`,
        );

        if (!response.ok) {
            throw new Error('Failed to fetch employees.');
        }

        const data = await response.json();

        options.value = Array.isArray(data)
            ? data
            : Array.isArray(data.data)
              ? data.data
              : [];
    } catch (error) {
        console.error('Failed to load employees:', error);

        options.value = [];
    } finally {
        loading.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Value Handling
|--------------------------------------------------------------------------
*/

const handleUpdate = (value: AppSelectValue) => {
    if (value === null || typeof value === 'number') {
        emit('update:modelValue', value);
        emit('change', value);
    }
};

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(fetchEmployees);

watch(() => props.role, fetchEmployees);
</script>

<template>
    <AppSelect
        :clearable="clearable"
        :disabled="disabled || loading"
        :error="error"
        :model-value="modelValue"
        :options="options"
        :placeholder="placeholder"
        :searchable="searchable"
        @update:model-value="handleUpdate"
    />
</template>
